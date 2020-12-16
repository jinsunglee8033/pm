<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/25/19
 * Time: 7:36 AM
 */

namespace App\Http\Controllers\GEN;

use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\emida;
use App\Lib\epay;
use App\Lib\gen;
use App\Lib\gss;

use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RTRProcessor;
use App\Lib\telestar;

use App\Model\Account;
use App\Model\Denom;
use App\Model\GenFee;
use App\Model\Product;
use App\Model\RTRPayment;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RechargeController extends Controller
{

    public function show() {

        $denoms = DB::select("
            select
                c.id product_id,
                b.id as id,
                b.denom,
                b.name,
                v.rtr_pid,
                v.fee
            from denomination b
                inner join product c on b.product_id = c.id
                inner join vendor_denom v on c.vendor_code = v.vendor_code and c.id = v.product_id and b.id = v.denom_id
            where c.id in ('WGENR', 'WGENOR')
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
            order by 3 asc
        ");

        return view('gen.recharge')->with([
            'denoms' => $denoms
        ]);
    }

    public function phone(Request $request){

        $res = gen::GetCustomerInfo($request->phone);

        Helper::log('##### GET CUSTOMER INFO ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }

        $plan_code = $res['plancode'];

        switch ($plan_code) {
            case '36':
                $plan_code = '35';
                break;
            case '38':
                $plan_code = '37';
                break;
            case '40':
                $plan_code = '39';
                break;
            case '42':
                $plan_code = '41';
                break;
            case '44':
                $plan_code = '43';
                break;
            case '64':
                $plan_code = '63';
                break;
        }

        return response()->json([
            'code'  => '0',
            'customer_id'   => $res['customer_id'],
            'plan_code'      => $plan_code,
            'balance_wallet' => $res['balance_wallet'],
            'balance'       => $res['balance'],
            'databalance'   => $res['databalance'],
            'expirydate'    => $res['expirydate'],
            'smsbalance'    => $res['smsbalance'],
            'msg'   => ''
        ]);
    }

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
//                'rtr_month' => 'required|numeric|min:1,max:12',
                'phone' => 'required|regex:/^\d{10}$/',
                'payer_id' => 'required',
                'payment_id' => 'required',
                'payment_token' => 'required'
            ], [
                'denom_id.required' => 'Please select amount first',
                'phone.regex' => 'Please enter valid phone number. 10 digits only.'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            ### check denomination ###
            if (empty($request->denom_id)) {
                throw new \Exception('Please select amount.', -3);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom || $denom->status != 'A')) {
                throw new \Exception('Please provide valid amount.', -4);
            }

            $face_value = $denom->denom;
            if ($denom->status != 'A') {
                throw new \Exception('$' . number_format($face_value) . ' is not active. Please contact our customer care.', -5);
            }

            ### check product ###
            $product = Product::find($denom->product_id);
            if (empty($product)) {
                throw new \Exception('Please select product first', -1);
            }

            if ($product->status != 'A') {
                throw new \Exception('Product is not active. Please contact our customer care.', -2);
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $request->denom_id)
                ->where('status', 'A')
                ->first();

            if (empty($vendor_denom)) {
                throw new \Exception('$' . number_format($face_value) . ' is not supported by the vendor [' . $product->vendor_code . ']', -6);
            }

            ### check sales limit ###
            $c_store_rtr_account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            $account = Account::find($c_store_rtr_account_id);
            if (empty($account)) {
                throw new \Exception('Logged in user account is invalid. Please contact our customer care.', -7);
            }

            $fee = 0;
            $pm_fee = $vendor_denom->pm_fee;

            $collection_amt = 0;//$face_value * $request->rtr_month;
            $net_revenue = 0;
            /*if ($collection_amt > 0) {
                $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true);
                if (!empty($ret['error_msg'])) {
                    return back()->withInput()->withErrors([
                        'exception' => $ret['error_msg']
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }*/

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = $product->id;
            $trans->action = 'RTR';
            $trans->denom = $face_value;
            $trans->denom_id = $denom->id;
            $trans->phone = $request->phone;
            $trans->status = 'I';
            $trans->cdate = Carbon::now();
            $trans->created_by = 'cstore';
            $trans->api = 'Y';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $request->rtr_month;
            $trans->net_revenue = $net_revenue;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->invoice_number  = $request->invoice_number;
            $trans->customer_id = $request->customer_id;
            $trans->plan_code = $request->plan_code;
            $trans->vendor_code = $product->vendor_code;
            $trans->save();

            ### log payment ###
            $pmt = new RTRPayment;
            $pmt->trans_id = $trans->id;
            $pmt->payer_id = $request->payer_id;
            $pmt->payment_id = $request->payment_id;
            $pmt->payment_token = $request->payment_token;
            $pmt->cdate = Carbon::now();
            $pmt->save();

            ### refresh balance ###
            //Helper::update_balance();

            return response()->json([
                'msg' => '',
                'invoice_no' => $trans->id,
                'carrier' => $request->carrier,
                'product' => $product->name,
                'amount' => $trans->denom,
                'rtr_month' => $trans->rtr_month,
                'phone' => $trans->phone,
                'sub_total' => $trans->denom,
                'fee' => $fee + $pm_fee,
                'total' => $trans->denom + $fee + $pm_fee
            ]);


        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - account id : ' . $account->id . '<br/>';
            $msg .= ' - name : ' . $account->name . '<br/>';
            $msg .= ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Gen Mobile RTR Failed', $msg);

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public static function process_after_pay($invoice_number) {
        Helper::log('##### process_after_pay (GEN) ###' , $invoice_number );
        $trans = null;

        try {
            $trans = Transaction::where('status', 'I')->where('invoice_number', $invoice_number)->first();

            if (empty($trans)) return;

            ### check product ###
            $product = Product::find($trans->product_id);
            if (empty($product)) {
                $trans->status = 'F';
                $trans->note = 'Please select product first';
                $trans->update();
                return;
            }

            if ($product->status != 'A') {
                $trans->status = 'F';
                $trans->note = 'Product is not active. Please contact our customer care.';
                $trans->update();
                return;
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $trans->denom_id)
                ->where('status', 'A')
                ->first();

            if (empty($vendor_denom)) {
                $trans->status = 'F';
                $trans->note = '$' . number_format($trans->denom) . ' is not supported by the vendor [' . $product->vendor_code . ']';
                $trans->update();
                return;
            }

            $rtr_pid = $vendor_denom->rtr_pid;
            switch ($rtr_pid) {
                case '36':
                    $rtr_pid = '35';
                    break;
                case '38':
                    $rtr_pid = '37';
                    break;
                case '40':
                    $rtr_pid = '39';
                    break;
                case '42':
                    $rtr_pid = '41';
                    break;
                case '44':
                    $rtr_pid = '43';
                    break;
                case '64':
                    $rtr_pid = '63';
                    break;
            }

            Helper::log('##### Start Change Plan to (GEN) ###', $rtr_pid);
            $ret = gen::ChangePlan($trans, $trans->customer_id, $trans->plan_code, $rtr_pid);
            Helper::log('##### Finish Change Plan to (GEN) ###', $ret);

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Gen Ecommerce RTR failed - Please be sure to refund paypal', $trans->note );

                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => $trans->note
                    ]
                ]);
            }

            ### add rtr-q for first month just for show up ###
            $error_msg = RTRProcessor::applyRTR(
                1,
                'Refill',
                $trans->id,
                'Refill',
                $trans->phone,
                $trans->product_id,
                $vendor_denom->vendor_code,
                $vendor_denom->rtr_pid,
                $trans->denom,
                'cstore',
                false,
                null,
                1,
                $vendor_denom->fee,
                $trans->rtr_month
            );
            if (!empty($error_msg)) {
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                $msg .= ' - product : ' . $product->id . '<br/>';
                $msg .= ' - denom : ' . $trans->denom . '<br/>';
                $msg .= ' - error : ' . $error_msg;
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Gen Mobile RTR - applyRTR 1st month failed', $msg);
            }

            ### processor remaining month ###

            Helper::log('#### RTR Month ###', 1);

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = '';
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### refresh balance ###
            //Helper::update_balance();

            return;


        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Gen Mobile RTR Failed', $msg);

            return ;
        }
    }

}