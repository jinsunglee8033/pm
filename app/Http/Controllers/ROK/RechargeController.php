<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/21/18
 * Time: 4:46 PM
 */

namespace App\Http\Controllers\ROK;


use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\DollarPhone;
use App\Lib\emida;
use App\Lib\emida2;
use App\Lib\epay;
use App\Lib\h2o_rtr;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\reup;
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
        return view('rok.recharge');
    }

    public function lookup(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'phone' =>  'required|regex:/^\d{10}$/'
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

            $ret = reup::get_mdn_info($request->phone);
            if (!empty($ret['error_code'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            $denom = $ret['denom'];
            if (empty($denom)) {
                throw new \Exception('Unable to find proper denomination', -1);
            }

            $product = Product::find($denom->product_id);
            if (empty($product)) {
                throw new \Exception('Unable to find proper product', -2);
            }

            $vendor_denom = VendorDenom::where('product_id', $product->id)
                ->where('vendor_code', $product->vendor_code)
                ->where('denom_id', $denom->id)
                ->first();
            if (empty($vendor_denom)) {
                throw new \Exception('Unable to find proper vendor configuration', -3);
            }

            return response()->json([
                'msg' => '',
                'denom' => $denom,
                'product' => $product,
                'vendor_denom' => $vendor_denom
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                'denom_id' => 'required',
                'rtr_month' => 'required|numeric|min:1,max:12',
                'phone' => 'required|regex:/^\d{10}$/',
                'payer_id' => 'required',
                'payment_id' => 'required',
                'payment_token' => 'required'
            ], [
                'product_id.required' => 'Please select product first',
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

            ### check product ###
            $product = Product::find($request->product_id);
            if (empty($product)) {
                throw new \Exception('Please select product first', -1);
            }

            if ($product->status != 'A') {
                throw new \Exception('Product is not active. Please contact our customer care.', -2);
            }

            ### check denomination ###
            if (empty($request->denom_id)) {
                throw new \Exception('Please select amount.', -3);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                throw new \Exception('Please provide valid amount.', -4);
            }

            $face_value = $denom->denom;
            if ($denom->status != 'A') {
                throw new \Exception('$' . number_format($face_value) . ' is not active. Please contact our customer care.', -5);
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
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

            $fee = $vendor_denom->fee * $request->rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $request->rtr_month;

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
            $trans->save();

            ### process vendor API - first month ###
            $vendor_tx_id = '';
            switch ($product->vendor_code) {
                case 'LOC': ### H2O ###
                    $ret = h2o_rtr::recharge($vendor_denom->rtr_pid, $trans->phone, $face_value, $trans->id);
                    $vendor_tx_id = isset($ret['sales_confirmation_id']) ? $ret['sales_confirmation_id'] : '';
                    break;
                case 'EPY':
                    $ret = epay::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '';
                    break;
                case 'EMD':
                    $ret = emida2::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '' ;
                    break;
                case 'DLP':
                    $ret = DollarPhone::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    break;
                case 'RUP':
                    $ret = reup::rtr($vendor_denom->rtr_pid, $trans->phone);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    break;
                case 'TST':
                    $ret = telestar::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    break;
                default:
                    $ret = [
                        'error_code' => -1,
                        'error_msg' => 'Vendor [' . $product->vendor_code .'] is not supported yet!'
                    ];
                    break;
            }

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                throw new \Exception($trans->note, -8);
            }

            if (empty($vendor_tx_id)) {
                $trans->status = 'F';
                $trans->note = 'Unable to retrieve vendor Tx.ID';
                $trans->save();

                throw new \Exception($trans->note, -9);
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
                $face_value,
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
                $msg .= ' - denom : ' . $face_value . '<br/>';
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
                    $face_value,
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
                    $msg .= ' - denom : ' . $face_value . '<br/>';
                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                    $msg .= ' - error : ' . $error_msg;
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - applyRTR remaining month failed', $msg);
                }
            }

            ### commission ###
            /*if ($collection_amt > 0) {
                $ret = CommissionProcessor::create($trans->id);
                if (!empty($ret['error_msg'])) {
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                    $msg .= ' - product : ' . $product->id . '<br/>';
                    $msg .= ' - denom : ' . $face_value . '<br/>';
                    $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - create commission failed', $msg);
                }
            }*/

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
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
                'sub_total' => $trans->rtr_month * $trans->denom,
                'fee' => $fee + $pm_fee,
                'total' => $trans->rtr_month * $trans->denom + $fee + $pm_fee
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