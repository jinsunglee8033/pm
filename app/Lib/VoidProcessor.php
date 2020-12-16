<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 10/18/17
 * Time: 11:37 AM
 */

namespace App\Lib;

use App\Model\Promotion;
use App\Model\StockESN;
use App\Model\StockPin;
use App\Model\StockSim;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoidProcessor
{

    public static function void($orig_id) {

        $step = 0;
        DB::beginTransaction();

        try {

            $trans = Transaction::find($orig_id);
            if (empty($trans)) {
                throw new \Exception('Invalid transaction ID to void', -1);
            }
            Helper::log('### Void of Original Trans ID ###', [
                'trans' => $trans
            ]);

            $user = Auth::user();

            ### 1. make void transaction ###

            $void_trans = new Transaction;
            $void_trans->type = 'V';
            $void_trans->account_id = $trans->account_id;
            $void_trans->product_id = $trans->product_id;
            $void_trans->action = $trans->action;
            $void_trans->denom = $trans->denom;
            $void_trans->denom_id = $trans->denom_id;
            $void_trans->sim = $trans->sim;
            $void_trans->esn = $trans->esn;
            $void_trans->npa = $trans->npa;
            $void_trans->address1 = $trans->address1;
            $void_trans->address2 = $trans->address2;
            $void_trans->city = $trans->city;
            $void_trans->state = $trans->state;
            $void_trans->zip = $trans->zip;
            $void_trans->phone = $trans->phone;
            $void_trans->current_carrier = $trans->current_carrier;
            $void_trans->carrier_contract = $trans->carrier_contract;
            $void_trans->account_no = $trans->account_no;
            $void_trans->account_pin = $trans->account_pin;
            $void_trans->first_name = $trans->first_name;
            $void_trans->last_name = $trans->last_name;
            $void_trans->call_back_phone = $trans->call_back_phone;
            $void_trans->email = $trans->email;
            $void_trans->pref_pin = $trans->pref_pin;
            $void_trans->status = 'C';
            $void_trans->note = '[Void] '. $trans->note;
            $void_trans->vendor_tx_id = $trans->vendor_tx_id;
            $void_trans->created_by = $user->user_id;
            $void_trans->cdate = Carbon::now();
            $void_trans->modified_by = $user->user_id;
            $void_trans->mdate = Carbon::now();
            $void_trans->api = $trans->api;
            $void_trans->allowed_activity = $trans->allowed_activity;
            $void_trans->portable = $trans->portable;
            $void_trans->portable_reason = $trans->portable_reason;
            $void_trans->portstatus = $trans->portstatus;
            $void_trans->dc = ''; //$trans->dc'';
            $void_trans->dp = ''; //$trans->dp;
            $void_trans->collection_amt = $trans->collection_amt;
            $void_trans->fee = $trans->fee;
            $void_trans->pm_fee = $trans->pm_fee;
            $void_trans->rtr_month = $trans->rtr_month;
            $void_trans->net_revenue = $trans->net_revenue;
            $void_trans->vendor_code = $trans->vendor_code;
            $void_trans->orig_id = $trans->id;
            $void_trans->invoice_number = '[V' . $trans->id . ']' . $trans->invoice_number;
            $void_trans->customer_id = $trans->customer_id;
            $void_trans->plan_code = $trans->plan_code;
            $void_trans->renew_now = $trans->renew_now;
            $void_trans->network = $trans->network;

            $void_trans->save();

            $trans->void_date = Carbon::now();
            $trans->note = '[Voided] '.$trans->note;
            $trans->save();


            ### 2. make void commission ###
            $ret = CommissionProcessor::void($trans->id);
            if (!empty($ret['error_code'])) {
//                throw new \Exception($ret['error_msg'], $ret['error_code']);
                DB::rollback();
                return $ret;
            }

            ### 3. make void spiff ###
            $ret = SpiffProcessor::void_spiff($trans->id);
            if (!empty($ret['error_code'])) {
//                throw new \Exception($ret['error_msg'], $ret['error_code']);
                DB::rollback();
                return $ret;
            }

            ### 4. make void rebate ###
            $ret = RebateProcessor::void_rebate($trans->id);
            if (!empty($ret['error_code'])) {
//                throw new \Exception($ret['error_msg'], $ret['error_code']);
                DB::rollback();
                return $ret;
            }


            ### 5. make void SIM charge, rebate ###
            $ret = Promotion::void_sim($trans->id);
            if(!empty($ret['error_code'])){
                DB::rollback();
                return $ret;
            }

            ### 6. make void ESN charge, rebate ###
            $ret = Promotion::void_esn($trans->id);
            if(!empty($ret['error_code'])){
                DB::rollback();
                return $ret;
            }

            ### 7. make void PIN ###
            $ret = StockPin::void_pin($trans->id);
            if(!empty($ret['error_code'])){
                DB::rollback();
                return $ret;
            }

            DB::commit();


            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {

            DB::rollback();

//            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Failed to void transaction', ' - Tx.ID: ' . $orig_id);
            Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] Failed to void transaction', ' - Tx.ID: ' . $orig_id . '[STEP:' . $step . ']' . '::' . $ex->getMessage());

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];

        }

    }

}