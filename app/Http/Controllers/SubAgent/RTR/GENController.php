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
use App\Lib\gen;
use App\Lib\telestar;
use App\Lib\gss;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\GenFee;
use App\Model\GenPinTransaction;
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

class GENController extends Controller
{

    public function show(Request $request) {
        $processing_fee = 2;

//        gen::ChangePlan(null);

        return view('sub-agent.rtr.gen')->with([
            'processing_fee' => $processing_fee
        ]);
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

        $account = Account::find(Auth::user()->account_id);

        ### denominations ###
        if($res['network'] == 'SPR'){
            $rtr = " and c.id in ('WGENR', 'WGENOR') ";
            $addon = " and c.id in ('WGENADDON', 'WGENILD', 'WGENDATA') ";
        }elseif ($res['network'] == 'TMB'){
            $rtr = " and c.id in ('WGENTR', 'WGENTOR') ";
            $addon = " and c.id in ('WGENTADDON', 'WGENTILD', 'WGENTDATA') ";
        }

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
            {$rtr}
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
            order by 3 asc, 1 desc, 2 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id
        ]);

        ### denominations ###
        $addon_denoms = DB::select("
            select 
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
            {$addon}
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
            order by 2 asc, 1 asc
        ", [
          'rate_plan_id' => $account->rate_plan_id
        ]);

        //$processing_fee = GenFee::get_total_fee($account->id, 'R', 0);
        $processing_fee = 0;

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
            'plancode'      => $plan_code,
            'balance_wallet' => $res['balance_wallet'],
            'balance'       => $res['balance'],
            'databalance'   => $res['databalance'],
            'expirydate'    => $res['expirydate'],
            'smsbalance'    => $res['smsbalance'],
            'denoms'        => $denoms,
            'addon_denoms'  => $addon_denoms,
            'processing_fee' => $processing_fee,
            'network'       => $res['network'],
            'msg'   => ''
        ]);
    }

    public function check_mdn_wallet(Request $request) {

        $res = gen::GetCustomerInfo($request->mdn);

        Helper::log('##### GET CUSTOMER INFO ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }

        $account = Account::find(Auth::user()->account_id);

        if($res['network'] == 'SPR'){
            $rtr = " and c.id in ('WGENR', 'WGENOR') ";
            $addon = " and c.id in ('WGENADDON', 'WGENILD', 'WGENDATA') ";
        }elseif ($res['network'] == 'TMB'){
            $rtr = " and c.id in ('WGENTR', 'WGENTOR') ";
            $addon = " and c.id in ('WGENTADDON', 'WGENTILD', 'WGENTDATA') ";
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
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'RTR'
            {$rtr}
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
            order by 1 desc, 2 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id
        ]);

        ### denominations ###
        $addon_denoms = DB::select("
            select 
                b.id as denom_id,
                b.denom,
                b.name,
                v.rtr_pid
            from rate_detail a 
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom v on c.vendor_code = v.vendor_code and c.id = v.product_id and b.id = v.denom_id
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'RTR'
            {$addon}
            and c.status = 'A'
            and b.status = 'A'
            and v.status = 'A'
            order by 1 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id
        ]);

        //$processing_fee = GenFee::get_total_fee($account->id, 'R', 0);

        $vendor_denom = VendorDenom::where('vendor_code', 'GEN')
            ->where('denom', 0)
            ->first();

        if($vendor_denom->fee == null || $vendor_denom->fee == 0){
            $processing_fee = 0;
        }else{
            $processing_fee = $vendor_denom->fee;
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
            'plancode'      => $plan_code,
            'balance_wallet' => $res['balance_wallet'],
            'balance'       => $res['balance'],
            'databalance'   => $res['databalance'],
            'expirydate'    => $res['expirydate'],
            'smsbalance'    => $res['smsbalance'],
            'denoms'        => $denoms,
            'addon_denoms'  => $addon_denoms,
            'processing_fee' => $processing_fee,
            'network'       => $res['network'],
            'msg'   => ''
        ]);
    }

    public function get_processing_fee(Request $request) {

        try {

            $denom = Denom::find($request->denom_id);
            $product_id = $denom->product_id;
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('denom_id', $request->denom_id)
                ->where('status', 'A')
                ->first();

            if($vendor_denom->fee == null || $vendor_denom->fee == 0){
                $processing_fee = 0;
            }else{
                $processing_fee = $vendor_denom->fee;
            }

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'denom'             => $vendor_denom->denom,
                    'processing_fee'    => $processing_fee
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

            $rtr_month = empty($request->rtr_month) ? 1 : $request->rtr_month;


            //$processing_fee = GenFee::get_total_fee($account->id, 'R', 0);
            //$processing_fee = $processing_fee * $rtr_month;

            //$fee = $vendor_denom->fee * $rtr_month + $processing_fee;
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
            $trans->save();

            ### process vendor API - first month ###
            $vendor_tx_id = '';

            $ret = gen::ChangePlan($trans, $request->customer_id, $request->plan_code, $vendor_denom->rtr_pid);

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
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### PAY GEN Processing FEE ###
            ### Act/Recharge Fee by products, not by accounts for Gen 7/24/19) ###
            //GenFee::pay_fee($trans->account_id, 'R', $trans->id, $account);

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

    public function esn_swap(Request $request) {

        $account = Account::where('id',Auth::user()->account_id)->first();
        $esn_swap = $account->esn_swap;
        $esn_swap_num = $account->esn_swap_num;
        $num_try = GenPinTransaction::where('account_id', $account->id)
            ->where('result', 'S')
            ->where('swap_date', '>=', Carbon::today()->addDays(-7)->format('Y-m-d') . ' 00:00:00')
            ->where('swap_date', '<=', Carbon::today()->format('Y-m-d') . ' 23:59:59')
            ->count();

        return view('sub-agent.rtr.gen_esn_swap',[
            'esn_swap' => $esn_swap,
            'esn_swap_num' => $esn_swap_num,
            'num_try' => $num_try
        ]);
    }

    public function check_mdn_for_esn_swap(Request $request) {

        $res = gen::getCustomerInfo_for_esn($request->mdn);

        Helper::log('##### GET CUSTOMER INFO FOR ESN SWAP ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }else{

            $gen_trans = new GenPinTransaction();
            $gen_trans->account_id = Auth::user()->account_id;
            $gen_trans->type = 'ESN Swap';
            $gen_trans->mdn = $request->mdn;
            $gen_trans->customer_id = $res['customer_id'];
            $gen_trans->esn = $res['esn_number'];
            $gen_trans->uiccid = $res['uiccid'];
            $gen_trans->account_password = $res['account_password'];
            $gen_trans->cdate = Carbon::now();
            $gen_trans->save();

        }

        return response()->json([
            'code'          => '0',
            'customer_id'   => $res['customer_id'],
            'esn_number'    => $res['esn_number'],
            'telephone_number' => $res['telephone_number'],
            'uiccid'        => $res['uiccid'],
            'account_password' => $res['account_password'],
            'msg'           => ''
        ]);
    }

    public function check_mdn_for_mdn_swap(Request $request) {

        $res = gen::getCustomerInfo_for_esn($request->mdn);

        Helper::log('##### GET CUSTOMER INFO FOR MDN SWAP ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }else{

            $gen_trans = new GenPinTransaction();
            $gen_trans->account_id = Auth::user()->account_id;
            $gen_trans->type = 'MDN Swap';
            $gen_trans->mdn = $request->mdn;
            $gen_trans->customer_id = $res['customer_id'];
            $gen_trans->esn = $res['esn_number'];
            $gen_trans->uiccid = $res['uiccid'];
            $gen_trans->account_password = $res['account_password'];
            $gen_trans->cdate = Carbon::now();
            $gen_trans->save();

        }

        return response()->json([
            'code'          => '0',
            'customer_id'   => $res['customer_id'],
            'esn_number'    => $res['esn_number'],
            'telephone_number' => $res['telephone_number'],
            'uiccid'        => $res['uiccid'],
            'account_password' => $res['account_password'],
            'msg'           => ''
        ]);
    }

    public function send_text_pin(Request $request) {

        $res = gen::send_notification($request->mdn, $request->pin);

        Helper::log('##### SEND TEXT GEN PIN ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }

        return response()->json([
            'code'          => '0',
            'msg'           => ''
        ]);
    }

    public function check_pin(Request $request) {

        $pin = $request->pin;
        $mdn = $request->mdn;

        $ret = GenPinTransaction::where('mdn', $mdn)->orderBy('id', 'desc')->first();

        if(empty($ret)) {
            return response()->json([
                'code'  => '-1',
                'msg' => 'Not exist in System'
            ]);
        }

        if($ret->account_password != $pin) {
            return response()->json([
                'code'  => '-1',
                'msg' => 'Not Matched PIN Number'
            ]);
        }

        return response()->json([
            'code'          => '0',
            'msg'           => ''
        ]);
    }

    public function esn_swap_post(Request $request) {

        $pin = $request->pin;
        $mdn = $request->mdn;
        $customer_id = $request->customer_id;
        $old_esn = $request->old_esn;
        $olduiccid = $request->olduiccid;
        $new_esn = $request->new_esn;

        $genPinTran = GenPinTransaction::where('mdn', $mdn)->orderBy('id', 'desc')->first();

        if(empty($genPinTran)) {
            return response()->json([
                'code'  => '-1',
                'msg' => 'Not exist in System'
            ]);
        }

        if($genPinTran->account_password != $pin) {
            return response()->json([
                'code'  => '-1',
                'msg' => 'Not Matched PIN Number'
            ]);
        }

        $res = gen::swap_esn($customer_id, $mdn, $old_esn, $olduiccid, $new_esn);

        Helper::log('##### ESN SWAP POST ###', $res);

        if (!empty($res['error_code'])) {
//            $genPinTran->type = 'ESN Swap';
            $genPinTran->new_esn = $new_esn;
            $genPinTran->result = 'F';
            $genPinTran->error_msg = $res['error_msg'];
            $genPinTran->swap_date = Carbon::now();
            $genPinTran->save();

            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }
//        $genPinTran->type = 'ESN Swap';
        $genPinTran->new_esn = $new_esn;
        $genPinTran->result = 'S';
        $genPinTran->swap_date = Carbon::now();
        $genPinTran->save();

        return response()->json([
            'code'          => '0',
            'msg'           => ''
        ]);
    }

    public function mdn_swap(Request $request) {

        $account = Account::where('id',Auth::user()->account_id)->first();
        $esn_swap = $account->esn_swap;
        $esn_swap_num = $account->esn_swap_num;
        $num_try = GenPinTransaction::where('account_id', $account->id)
            ->where('result', 'S')
            ->where('swap_date', '>=', Carbon::today()->addDays(-7)->format('Y-m-d') . ' 00:00:00')
            ->where('swap_date', '<=', Carbon::today()->format('Y-m-d') . ' 23:59:59')
            ->count();

        return view('sub-agent.rtr.gen_mdn_swap',[
            'esn_swap' => $esn_swap,
            'esn_swap_num' => $esn_swap_num,
            'num_try' => $num_try
        ]);
    }

    public function mdn_swap_post(Request $request) {

        $pin = $request->pin;
        $mdn = $request->mdn;
        $customer_id = $request->customer_id;
        $old_esn = $request->old_esn;
        $zip = $request->zip;

        $genPinTran = GenPinTransaction::where('mdn', $mdn)->orderBy('id', 'desc')->first();

        if(empty($genPinTran)) {
            return response()->json([
                'code'  => '-1',
                'msg' => 'Not exist in System'
            ]);
        }

        if($genPinTran->account_password != $pin) {
            return response()->json([
                'code'  => '-1',
                'msg' => 'Not Matched PIN Number'
            ]);
        }

        $res = gen::swap_mdn($customer_id, $old_esn, $mdn, $zip);

        Helper::log('##### ESN SWAP POST ###', $res);

        if (!empty($res['error_code'])) {
//            $genPinTran->type = 'MDN Swap';
            $genPinTran->result = 'F';
            $genPinTran->error_msg = $res['error_msg'];
            $genPinTran->swap_date = Carbon::now();
            $genPinTran->save();

            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }

//        $genPinTran->type = 'MDN Swap';
        $genPinTran->result = 'S';
        $genPinTran->swap_date = Carbon::now();
        $genPinTran->save();

        return response()->json([
            'code'          => '0',
            'msg'           => 'MDN Swap Success!'
        ]);
    }

    public function addon(Request $request) {

        return view('sub-agent.rtr.gen_addon');
    }

    public function addon_post(Request $request) {

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

            $rtr_month = empty($request->rtr_month) ? 1 : $request->rtr_month;

            //$processing_fee = GenFee::get_total_fee($account->id, 'R', 0);
            //$processing_fee = $processing_fee * $rtr_month;
            $fee = $vendor_denom->fee * $rtr_month ; //+ $processing_fee;
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
            $trans->save();

            ### process vendor API - first month ###
            $vendor_tx_id = '';

            $ret = gen::AddDataTopup($trans, $request->customer_id, $vendor_denom->rtr_pid);

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
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### PAY GEN Processing FEE ###
           // GenFee::pay_fee($trans->account_id, 'R', $trans->id, $account);

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

    public function wallet(Request $request) {

        return view('sub-agent.rtr.gen_wallet');
    }

    public function wallet_post(Request $request) {

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
                'amount'    => 'required',
                'mdn'       => 'required|regex:/^\d{10}$/'
            ], [
                'amount.required' => 'Please enter amount first',
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

            if($request->network == 'TMB'){
                $product_id = 'WGENTWALLET';
            }else {
                $product_id = 'WGENWALLET';
            }
            ### check product ###
            $product = Product::find($product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Product is not active. Please contact our customer care.'
                  ]
                ]);
            }

            $denom = Denom::where('product_id', $product_id)
                ->where('min_denom', '<=', $request->amount)
                ->where('max_denom', '>=', $request->amount)
                ->first();

            if (empty($denom) || $denom->status != 'A') {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'The plan is not active. Please contact our customer care.'
                  ]
                ]);
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
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

            $rtr_month = empty($request->rtr_month) ? 1 : $request->rtr_month;

            //$processing_fee = GenFee::get_total_fee($account->id, 'R', 0);
            //$processing_fee = $processing_fee * $rtr_month;
            $fee = $vendor_denom->fee * $rtr_month ; //+ $processing_fee;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            $collection_amt = $request->amount;

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

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = $product->id;
            $trans->action = 'RTR';
            $trans->denom = $request->amount;
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
            $trans->save();

            ### process vendor API - first month ###

            $ret = gen::AddWallet($trans);

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

            $vendor_tx_id = $ret['transactionid'];

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
                $msg .= ' - denom : ' . $trans->denom . '<br/>';
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
                    $trans->denom,
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
                    $msg .= ' - denom : ' . $trans->denom . '<br/>';
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
                    $msg .= ' - denom : ' . $trans->denom . '<br/>';
                    $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - create commission failed', $msg);
                }
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### PAY GEN Processing FEE ###
            //GenFee::pay_fee($trans->account_id, 'R', $trans->id, $account);

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

        return view('sub-agent.rtr.gen')->with([
              'trans' => $trans
        ]);

    }

    public function addon_success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();

        return view('sub-agent.rtr.gen_addon')->with([
          'trans' => $trans
        ]);

    }

    public function wallet_success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();

        return view('sub-agent.rtr.gen_wallet')->with([
          'trans' => $trans
        ]);

    }

}