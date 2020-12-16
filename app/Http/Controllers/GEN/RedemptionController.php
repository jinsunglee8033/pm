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
use App\Model\Product;
use App\Model\RTRPayment;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RedemptionController extends Controller
{

    public function show() {

        return view('gen.redemption');
    }

    public function check_mdn(Request $request) {

        $res = gen::GetCustomerInfo($request->mdn);

        Helper::log('##### GET CUSTOMER INFO ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
              'code'  => '-1',
              'msg' => $res['error_msg']
            ]);
        }

        ### denominations ###
        $denoms = DB::select("
            select 
                c.id,
                b.id as denom_id,
                b.denom,
                b.name,
                v.rtr_pid
            from denomination b
                inner join product c on b.product_id = c.id
                inner join vendor_denom v on c.vendor_code = v.vendor_code and c.id = v.product_id and b.denom = v.denom
            where c.id in ('WGENR', 'WGENOR')
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
        ");

        $plan_code = $res['plancode'];
        $network   = $res['network'];

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

        if($network == 'SPR'){
            $ret = VendorDenom::where('rtr_pid', $plan_code)->where('Product_id', 'WGENR')->first();
            if (empty($ret)) {
                return response()->json([
                    'code'  => '-1',
                    'msg' => 'The Plan is not exist in our System'
                ]);
            }
            $denom_id = $ret->denom_id;
            $denom = $ret->denom;
        }else{
            $ret = VendorDenom::where('rtr_pid', $plan_code)->where('Product_id', 'WGENTR')->first();
            if (empty($ret)) {
                return response()->json([
                    'code'  => '-1',
                    'msg' => 'The Plan is not exist in our System'
                ]);
            }
            $denom_id = $ret->denom_id;
            $denom = $ret->denom;
        }

        return response()->json([
          'code'  => '0',
          'customer_id' => $res['customer_id'],
          'plancode'    => $plan_code,
          'balance_wallet' => $res['balance_wallet'],
          'balance'     => $res['balance'],
          'databalance' => $res['databalance'],
          'expirydate'  => $res['expirydate'],
          'smsbalance'  => $res['smsbalance'],
          'denoms'      => $denoms,
          'denom_id'    => $denom_id,
          'denom'       => $denom,
          'msg'         => ''
        ]);
    }

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
              'mdn' => 'required|regex:/^\d{10}$/',
              'pin' => 'required'
            ], [
              'mdn.regex' => 'Please enter valid phone number. 10 digits only.',
              'pin.required' => 'Please enter pin number first'
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


            ### check sales limit ###
            $c_store_rtr_account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            $account = Account::find($c_store_rtr_account_id);
            if (empty($account)) {
                throw new \Exception('Logged in user account is invalid. Please contact our customer care.', -7);
            }

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = 'WGENR';
            $trans->action = 'RTR';
            $trans->denom = $request->denom;
            $trans->denom_id = $request->denom_id;
            $trans->phone = $request->mdn;
            $trans->pin = $request->pin;
            $trans->status = 'I';
            $trans->cdate = Carbon::now();
            $trans->created_by = 'cstore';
            $trans->api = 'Y';
            $trans->collection_amt = 0;
            $trans->rtr_month = 1;
            $trans->net_revenue = 0;
            $trans->fee = 0;
            $trans->pm_fee = 0;
            $trans->save();


            $ret = gen::RedeemPin($trans);

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => $trans->note
                  ]
                ]);
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            return response()->json([
              'code'  => '0',
              'data' => [
                'id'   => $trans->id
              ]
            ]);

        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Domestic RTR Failed', $msg);

            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}