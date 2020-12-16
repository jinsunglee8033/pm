<?php
/**
 * Created by Jin.
 * Date: 7/24/20
 * Time: 9:47 AM
 */

namespace App\Http\Controllers\Boom;

use App\Http\Controllers\Controller;
use App\Lib\boom;
use App\Lib\CommissionProcessor;
use App\Lib\emida;
use App\Lib\emida2;
use App\Lib\epay;
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

class RechargeController extends Controller
{

    public function show() {

        $denoms     = Denom::where('product_id', 'WBMBAR')->where('status', 'A')->orderBy('denom', 'asc')->get();
        $product    = Product::find('WBMBAR');

        foreach ($denoms as $d) {
            $d->vendor_denom = VendorDenom::where('product_id', 'WBMBAR')
                ->where('vendor_code', $product->vendor_code)
                ->where('denom_id', $d->id)
                ->first();
        }

        return view('boom.recharge')->with([
            'denoms' => $denoms
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
                c.id product_id,
                b.id as denom_id,
                b.denom,
                b.name,
                v.rtr_pid,
                v.fee
            from denomination b
                inner join product c on b.product_id = c.id
                inner join vendor_denom v on c.vendor_code = v.vendor_code 
                    and c.id = v.product_id 
                    and b.id = v.denom_id
                    and ifnull(v.rtr_pid, '') != '' 
            where c.status = 'A'
            {$network}
            and b.status = 'A'
            and v.status = 'A'
            
        ");

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

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
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
                return response()->json([
                    'msg' => 'Invalid denomination provided'
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Please provide valid amount.'
                ]);
            }

            $face_value = $denom->denom;
            if ($denom->status != 'A') {
                return response()->json([
                    'msg' => 'The amount is not active. Please contact our customer care.'
                ]);
            }

            ### check product ###
            $product = Product::find($denom->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Please select product first'
                ]);
            }

            if ($product->status != 'A') {
                return response()->json([
                    'msg' => 'Product is not active. Please contact our customer care.'
                ]);
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            if (empty($vendor_denom)) {
                return response()->json([
                    'msg' => 'vendor denom is not supported by the vendor. Please contact our customer care.'
                ]);
            }

            ### check sales limit ###
            $c_store_rtr_account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            $account = Account::find($c_store_rtr_account_id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            $rtr_month = 1;

            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            $collection_amt = 0;
            $net_revenue = 0;

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
            $trans->rtr_month = 1;
            $trans->net_revenue = $net_revenue;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->invoice_number  = $request->invoice_number;
            $trans->vendor_code = $product->vendor_code;
            $trans->renew_now = $request->renew;
            $trans->plan_code = $request->plan_code;
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
                'invoice_no' => $trans->invoice_number,
                'trans_no' => $trans->id,
                'product' => $product->name,
                'plan' => $denom->name,
                'phone' => $trans->phone,
                'rtr_month' => 1,
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

            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Domestic RTR Failed', $msg);

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public static function process_after_pay($invoice_number) {

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

            ### Renew now ###
            $renew  = $trans->renew_now == 'Y' ? 'TRUE' : 'FALSE';

            ### Network ###
            if($product->id == 'WBMBAR'){
                $network = 'BLUE';
            }elseif($product->id = 'WBMRAR'){
                $network = 'RED';
            }else{
                $network = 'PURPLE';
            }

            ### process vendor API - first month ###
            Helper::log('##### Start Change Plan to (Boom) ###');
            $ret = boom::updatePlan($trans->phone, $trans->plan_code, $renew, $network, 9999);

            $vendor_tx_id = $ret['request_number'];
            $cust_nbr = $ret['cust_nbr'];

            if ($ret['error_code'] != '') {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                ### Send email When RTR vendor API failed. ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                $msg .= ' - product : ' . $product->id . '<br/>';
                $msg .= ' - denom : ' . $trans->denom . '<br/>';
                $msg .= ' - error : ' . $trans->note;
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom RTR (eCom) - failed', $msg);

                return;
            }

            if (empty($vendor_tx_id)) {
                $trans->status = 'F';
                $trans->note = 'Unable to retrieve vendor Tx.ID';
                $trans->save();

                return;
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
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom RTR (eCom) - applyRTR 1st month failed', $msg);
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
                    'cstore',
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
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom RTR (eCom) - applyRTR remaining month failed', $msg);
                }
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
            $trans->customer_id = $cust_nbr;
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

            Helper::send_mail('it@perfectmobileinc.com', 'Boom RTR Failed (eCom)', $msg);

            return ;
        }
    }

}