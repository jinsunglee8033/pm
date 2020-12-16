<?php
/**
 * Created by Royce.
 * Date: 5/18/18
 * Time: 11:26 AM
 */

namespace App\Http\Controllers\FreeUP;

use App\Http\Controllers\Controller;
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
        $product    = Product::find('WFRUPR');

        $denoms = Denom::join('vendor_denom', function($join) {
            $join->on('denomination.product_id', 'vendor_denom.product_id');
            $join->on('denomination.id', 'vendor_denom.denom_id');
        })->where('denomination.product_id', $product->id)
            ->where('vendor_code', $product->vendor_code)
            ->where('vendor_denom.status', 'A')
            ->where('denomination.status', 'A')
            ->whereRaw("ifnull(vendor_denom.rtr_pid, '') != '' ")
            ->get();

        return view('freeup.recharge')->with([
            'denoms' => $denoms
        ]);
    }

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'rtr_month' => 'required|numeric|min:1,max:12',
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
            if (empty($denom)) {
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

            $fee = 0;
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

            $msg = ' - account id : ' . $account->id . '<br/>';
            $msg .= ' - name : ' . $account->name . '<br/>';
            $msg .= ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'FreeUp RTR Failed', $msg);

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


            ### process vendor API - first month ###
            $vendor_tx_id = '';
            switch ($product->vendor_code) {
                case 'EPY':
                    $ret = epay::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $trans->denom, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '';
                    break;
                case 'EMD':
                    $ret = emida2::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $trans->denom, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '' ;
                    break;
                case 'GSS':
                    $ret = gss::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $trans->denom);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    break;
                default:
                    $ret = [
                      'error_code' => -1,
                      'error_msg' => 'Vendor [' . $product->vendor_code .'] is not supported yet!'
                    ];
                    break;
            }

            if (!empty($ret['error_msg'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                ### Send email When RTR vendor API failed. ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                $msg .= ' - product : ' . $product->id . '<br/>';
                $msg .= ' - denom : ' . $trans->denom . '<br/>';
                $msg .= ' - error : ' . $trans->note;
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] FreeUp RTR - failed', $msg);

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
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] FreeUp RTR - applyRTR 1st month failed', $msg);
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
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] FreeUp RTR - applyRTR remaining month failed', $msg);
                }
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
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

            Helper::send_mail('it@perfectmobileinc.com', 'FreeUp RTR Failed', $msg);

            return ;
        }
    }

}