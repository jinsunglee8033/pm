<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/21/19
 * Time: 10:46 AM
 */

namespace App\Http\Controllers\SubAgent\RTR;


use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\emida;
use App\Lib\epay;
use App\Lib\h2o_rtr;
use App\Lib\DollarPhone;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\reup;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\boom;
use App\Lib\telestar;
use App\Lib\gss;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\GenFee;
use App\Model\Product;
use App\Model\Promotion;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BoomController extends Controller
{
    public function show(Request $request) {

        return view('sub-agent.rtr.boom')->with([

        ]);
    }

    public function check_mdn(Request $request) {

        $res = boom::getCustomerInfo($request->mdn, $request->network);

        Helper::log('##### GET BOOM CUSTOMER INFO ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
              'code'  => '-1',
              'msg' => $res['error_msg']
            ]);
        }

        $account = Account::find(Auth::user()->account_id);

        if($request->network == "RED"){
            $network = " and c.id in ('WBMRAR') ";
        }else if($request->network == 'BLUE'){
            $network = " and c.id in ('WBMBAR') ";
        }else if($request->network == 'PURPLE'){
            $network = " and c.id in ('WBMPAR', 'WBMPOAR') ";
        }

        ### denominations ###
        $denoms = DB::select("
            select 
                c.id,
                b.id as denom_id,
                b.denom,
                b.name,
                v.rtr_pid
            from rate_detail a 
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom v on c.vendor_code = v.vendor_code and c.id = v.product_id and b.id = v.denom_id
                    and ifnull(v.rtr_pid, '') != ''
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'RTR'
            {$network}
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
            order by 3 asc, 1 asc, 2 asc
        ", [
            'rate_plan_id'  => $account->rate_plan_id
        ]);

        $plan_code = $res['plan_code'];

        $reload_date = $res['reload_date'];
        $date = date_create($reload_date);

        if($reload_date == ''){
            $renew_now = 'Y';
            $expired_date = '';
        }else {
            $now = Carbon::today();
            if ($date <= $now) {
                // renow now! Y
                $renew_now = 'Y';
            } else {
                // show option!
                $renew_now = 'N';
            }
            $expired_date = date_format($date,"Y-m-d");
        }



        return response()->json([
            'code'  => '0',
            'plancode'      => $plan_code,
            'denoms'        => $denoms,
            'reload_date'   => $expired_date,
            'renow_now'     => $renew_now,
            'msg'   => ''
        ]);
    }

    public function get_processing_fee(Request $request) {

        try {

            $denom = Denom::find($request->denom_id);
            $product_id = $denom->product_id;
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            $product_fee        = 0;
            $pm_fee             = 0;

            if($vendor_denom->fee == null || $vendor_denom->fee == 0){
                $product_fee = 0;
            }else{
                $product_fee = $vendor_denom->fee;
            }
            if($vendor_denom->pm_fee == null || $vendor_denom->pm_fee == 0){
                $pm_fee = 0;
            }else{
                $pm_fee = $vendor_denom->pm_fee;
            }

            $processing_fee = $product_fee + $pm_fee;

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'denom'             => $vendor_denom->denom,
                    'processing_fee'    => $processing_fee,
                    'plan_id'           => $vendor_denom->rtr_pid
                ]
            ]);

        }catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }

    }

    public function post(Request $request) {

        $trans = null;

        try {
            if (Helper::is_login_as()) {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Login as is not allowed to do recharge.'
                  ]
                ]);
            }

            $v = Validator::make($request->all(), [
                'denom_id'  => 'required',
                'plan_code' => 'required',
                'mdn' => 'required|regex:/^\d{10}$/'
            ], [
                'denom_id.required' => 'Please select plan first',
                'mdn.regex' => 'Please enter valid phone number. 10 digits only.'
            ]);

            if ($v->fails()) {
                $errors = Array();
                foreach ($v->errors()->messages() as $key => $value) {
                    $errors[] = [
                      'fld'   => $key,
                      'msg'   => $value[0]
                    ];
                };

                return response()->json([
                  'code' => '-1',
                  'data' => $errors
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom) || $denom->status != 'A') {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'The plan is not active. Please contact our customer care.'
                  ]
                ]);
            }

            ### check product ###
            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Product is not active. Please contact our customer care.'
                  ]
                ]);
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
              ->where('product_id', $product->id)
              ->where('denom_id', $denom->id)
              ->where('status', 'A')
              ->first();

            if (empty($vendor_denom) || empty($vendor_denom->rtr_pid)) {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Plan is not active. Please contact our customer care. [Vendor]'
                  ]
                ]);
            }

            ### check sales limit ###
            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Logged in user account is invalid. Please contact our customer care.'
                  ]
                ]);
            }

//            $rtr_month = empty($request->rtr_month) ? 1 : $request->rtr_month;

            $rtr_month = 1;

            $fee = $vendor_denom->fee * $rtr_month ;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            $collection_amt = $denom->denom * $rtr_month;

            $net_revenue = 0;
            if ($collection_amt > 0) {
                $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                      'code' => '-5',
                      'data' => [
                        'fld'   => 'exception',
                        'msg'   => $ret['error_msg']
                      ]
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            ### Duplicate transaction check ###
            $now = Carbon::now();
            $gap = Carbon::now()->subSeconds(10);

            $ret = Transaction::where('account_id', '=', $account->id)
                        ->where('product_id', '=', $product->id)
                        ->where('denom_id', '=', $denom->id)
                        ->where('phone', '=', $request->mdn)
                        ->where('status', '!=', 'F')
                        ->where('cdate', '<=', $now)
                        ->where('cdate', '>', $gap)
                        ->count();

            if($ret > 0){
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'Failed',
                        'msg'   => 'You already have another transaction with same phone number'
                    ]
                ]);
            }

            // Optional. Blank, use last 4 digit phone number.
//            $pin = empty($request->pin) ? substr($request->mdn, -4) : $request->pin;

            // Updated 6/1/20 : pin fixed to 9999
            $pin = '9999';

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = $product->id;
            $trans->action = 'RTR';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->phone = $request->mdn;
            $trans->status = 'I';
            $trans->cdate = Carbon::now();
            $trans->created_by = $user->user_id;
            $trans->api = 'Y';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->net_revenue = $net_revenue;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->vendor_code = $product->vendor_code;
            $trans->renew_now = $request->renew;
            $trans->pref_pin = $request->pin;
            $trans->save();

            ### process vendor API - first month ###
            $vendor_tx_id = '';

            $renew  = $request->renew == 'Y' ? 'TRUE' : 'FALSE';

            $ret = boom::updatePlan($request->mdn, $request->plan_code, $renew, $request->network, $pin);

            $vendor_tx_id = $ret['request_number'];
            $cust_nbr = $ret['cust_nbr'];

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->vendor_tx_id = $vendor_tx_id;
                $trans->save();

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
                $denom->denom,
                $user->user_id,
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
                $msg .= ' - denom : ' . $denom->denom . '<br/>';
                $msg .= ' - error : ' . $error_msg;
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - applyRTR 1st month failed', $msg);
            }

            ### processor remaining month ###

            Helper::log('#### RTR Month ###', $trans->rtr_month);

            if ($trans->rtr_month > 1) {
                $error_msg = RTRProcessor::applyRTR(
                    $trans->rtr_month,
                    'Refill' ,
                    $trans->id,
                    'Refill',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $denom->denom,
                    $user->user_id,
                    true,
                    null,
                    2,
                    $vendor_denom->fee
                );

                if (!empty($error_msg)) {
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                    $msg .= ' - product : ' . $product->id . '<br/>';
                    $msg .= ' - denom : ' . $denom->denom . '<br/>';
                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                    $msg .= ' - error : ' . $error_msg;
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - applyRTR remaining month failed', $msg);
                }
            }

            ### commission ###
            if ($collection_amt > 0) {
                $ret = CommissionProcessor::create($trans->id);
                if (!empty($ret['error_msg'])) {
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                    $msg .= ' - product : ' . $product->id . '<br/>';
                    $msg .= ' - denom : ' . $denom->denom . '<br/>';
                    $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - create commission failed', $msg);
                }
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
            $trans->note = $cust_nbr;
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### refresh balance ###
            Helper::update_balance();

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

            return back()->withInput()->withErrors([
              'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }


    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();

        return view('sub-agent.rtr.boom')->with([
              'trans' => $trans
        ]);

    }


}