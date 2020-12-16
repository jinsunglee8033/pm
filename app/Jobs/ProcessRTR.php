<?php

namespace App\Jobs;

use App\Lib\emida;
use App\Lib\emida2;
use App\Lib\epay;
use App\Lib\DollarPhone;
use App\Lib\liberty;
use App\Lib\reup;
use App\Lib\telestar;
use App\Lib\gss;
use App\Lib\gen;
use App\Model\Product;
use App\Model\Transaction;
use App\Model\TransactionBoom;
use App\Model\VendorDenom;
use App\Model\VendorFeeSetup;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Model\RTRQueue;
use Carbon\Carbon;
use App\Lib\h2o_rtr;
use App\Lib\Helper;

class ProcessRTR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $q;
    public $tries = 3 ; //Max times of the job attempted when failed.
    
    /**
     * ProcessRTR constructor.
     * @param RTRQueue $q
     */
    public function __construct(RTRQueue $q)
    {
        $this->q = $q;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $q = $this->q;

        try {
            $result = $q->result;
            if ($result != 'N') {
                ### do nothing ###
                Helper::send_mail('it@perfectmobileinc.com', '[' . getenv('APP_ENV') . '][PM] ProcessRTR error : ' . $q->trans_id, 'Non-New status job called');
                return;
            }

            $q->result = 'P';
            $q->result_msg = '';
            $q->result_date = null;
            $q->save();

            $trans_id = $q->trans_id;
            $phone = $q->phone;
            
            ### 7/1/20 get rtr_pin from Product (7/30/20 deploy again) ###
            $p_obj = Product::where('id', $q->product_id)->first();
            $vendor_code = $p_obj->vendor_code;

            ### 9/320 get denom_id from Transaction ###
            $t_obj = Transaction::where('id', $trans_id)->first();
            $denom_id = $t_obj->denom_id;

            $vd_obj = VendorDenom::where('vendor_code', $vendor_code)
                ->where('denom_id', $denom_id)
                ->where('status', 'A')
                ->first();

            // for open range (no amt)
            if(empty($vd_obj)){
                $vd_obj = VendorDenom::where('product_id', $q->product_id)
                    ->where('vendor_code', $vendor_code)
                    ->where('status', 'A')
                    ->orderBy('denom', 'asc')
                    ->first();
            }

            $vendor_pid = $vd_obj->rtr_pid;

            if( in_array($q->product_id, ['WUTR'])) {
                $fee = isset($vd_obj->fee) ? $vd_obj->fee : 0; //Vendor fee from vendor_pid, not from queue. should consider Account Fee of Boost,Simple
            }else {
                $fee = isset($q->fee) ? $q->fee : 0;
            }

            $amt = $q->amt;

            if ($trans_id == -1) {
                $trans_id = time();
            }

            switch ($vendor_code) {
                case 'GSS':
                    $ret = gss::rtr((int)$trans_id, $vendor_pid, $phone, $amt);
                    break;
                case 'LOC':
                    $ret = h2o_rtr::recharge($vendor_pid, $phone, $amt, (int)$trans_id);
                    break;
                case 'EPY':
                    $ret = epay::rtr((int)$trans_id, $vendor_pid, $phone, $amt, $fee);
                    break;
                case 'EMD':
                    $ret = emida2::rtr((int)$trans_id, $vendor_pid, $phone, $amt, $fee);
                    break;
                case 'DLP':

                    ### Check for vendor fee setup (08/12/2020) ###
                    $vfs = VendorFeeSetup::where('vendor_code', $vendor_code)
                        ->where('product_id', $q->product_id)
                        ->where('amt_and_fee', 'Y')
                        ->first();
                    $amt_and_fee = !empty($vfs) ? 'Y' : 'N';

                    ### Check for Boost for PIN (08/14/20) ###
                    $mdn_pin = '';
                    if($q->product_id == 'WBST'){
                        $t = TransactionBoom::where('id', $trans_id)->first();
                        $mdn_pin = $t->pref_pin;
                    }

                    $ret = DollarPhone::rtr((int)$trans_id, $vendor_pid, $phone, $amt, $fee, $mdn_pin, $amt_and_fee);
                    break;
                case 'RUP':
                    $ret = reup::rtr($vendor_pid, $phone);
                    break;
                case 'TST':
                    $ret = telestar::rtr((int)$trans_id, $vendor_pid, $phone, $amt);
                    break;
                case 'LBT':
                    $ret = liberty::refillByLot($phone, $vendor_pid);
                    break;
                case 'GEN':
                    $res = gen::GetCustomerInfo($phone);
                    if (!empty($res['error_code'])) {
                        $ret = [
                            'error_code' => -1,
                            'error_msg' => '[Lookup Failure]' . $res['error_msg']
                        ];
                        break;
                    }

                    $current_plancode = $res['plancode'];
                    $current_customer_id = $res['customer_id'];

                    switch ($vendor_pid) {
                        case '36':
                            $vendor_pid = '35';
                            break;
                        case '38':
                            $vendor_pid = '37';
                            break;
                        case '40':
                            $vendor_pid = '39';
                            break;
                        case '42':
                            $vendor_pid = '41';
                            break;
                        case '44':
                            $vendor_pid = '43';
                            break;
                        case '64':
                            $vendor_pid = '63';
                            break;
                    }

                    $ret = gen::ChangePlan($q, $current_customer_id, $current_plancode, $vendor_pid); //same plan, without plan change.

                    break;
                default:
                    $ret = [
                        'error_code' => -1,
                        'error_msg' => 'Unsupported vendor code: ' . $vendor_code . ($vendor_code == 'GSS' ? 'Equal to GSS' : 'Diffirent to GSS')
                    ];
                    break;
            }

            if (!empty($ret['error_msg'])) {
                $q->result = 'F';
                $q->vendor_code = $vendor_code;
                $q->vendor_pid = $vendor_pid;
                $q->result_msg = 'VENDOR: ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $q->result_date = Carbon::now();
                $q->save();

                Helper::send_mail('it@perfectmobileinc.com', '[' . getenv('APP_ENV') . '][PM] ProcessRTR error : ' . $q->trans_id, $q->result_msg);
                if (getenv('APP_ENV') == 'production') {
                    Helper::send_mail('ops@softpayplus.com', '[PM] ProcessRTR error : ' . $q->trans_id, $q->result_msg);
                }

                return;
            }

            $q->result = 'S';
            $q->vendor_code = $vendor_code;
            $q->vendor_pid = $vendor_pid;
            $q->fee = $fee;
            $q->result_date = Carbon::now();
            $q->save();

        } catch (\Exception $ex) {

            $q->result = 'F';
            $q->vendor_code = $vendor_code;
            $q->vendor_pid = $vendor_pid;
            $q->result_msg = $ex->getMessage() . ' [' . $ex->getCode() . ']';
            $q->result_date = Carbon::now();
            $q->save();

            Helper::send_mail('it@perfectmobileinc.com', '[' . getenv('APP_ENV') . '][PM] ProcessRTR error : ' . $q->trans_id, $q->result_msg);
            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('ops@softpayplus.com', '[PM] ProcessRTR error : ' . $q->trans_id, $q->result_msg);
            }

        }
    }
}
