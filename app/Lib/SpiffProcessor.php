<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/24/17
 * Time: 3:39 PM
 */

namespace App\Lib;

use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\ROKESN;
use App\Model\ROKSim;
use App\Model\StockESN;
use App\Model\StockSim;
use App\Model\SpiffSetup;
use App\Model\SpiffTrans;
use App\Model\SpiffSetupSpecial;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Lib\Helper as Helper;

class SpiffProcessor
{

    public static function void_spiff($trans_id) {
        try {
            $cnt = SpiffTrans::where('trans_id', $trans_id)
                ->where('type', 'S')
                ->whereNull('void_date')
                ->count();

            if ($cnt < 1) {
                return [
                    'error_code' => '',
                    'error_msg' => ''
                ];
            }

            $ret = DB::statement("
                insert into spiff_trans (
                    type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month
                    , spiff_amt, orig_spiff_amt, cdate, created_by, orig_spiff_id
                )
                select
                    'V' as type, 
                    trans_id,
                    phone,
                    account_id,
                    product_id,
                    denom,
                    account_type,
                    spiff_month,
                    spiff_amt,
                    orig_spiff_amt,
                    current_timestamp,
                    :user_id as created_by,
                    id as orig_spiff_id
                from spiff_trans
                where trans_id = :trans_id
                and type = 'S'
                and void_date is null
            ", [
                'trans_id' => $trans_id,
                'user_id' => Auth::user()->user_id
            ]);

            if ($ret < 1) {
                throw new \Exception('Failed to add void spiff trans record');
            }

            $ret = DB::statement("
                update spiff_trans
                set void_date = current_timestamp
                where trans_id = :trans_id
                and type = 'S'
                and void_date is null
            ", [
                'trans_id' => $trans_id
            ]);

            if ($ret < 1) {
                throw new \Exception('Failed to update original trans record');
            }

        } catch (\Exception $ex) {
            Helper::log('### Exception ### ' . $ex->getMessage() . ' . ' . $ex->getCode());

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }

    public static function give_spiff($account_id, $product_id, $denom, $month, $phone, $trans_id = null, $user_id = null, $divide_qty = 1, $phone_type = null, $sim = null, $esn = null, $denom_id = null) {

        try {
            ### Will be deleted when parameter $sim_obj is given
            $sim_obj = StockSim::where('sim_serial', $sim)->where('product', $product_id)->first();
            $esn_obj = StockESN::where('esn', $esn)->where('product', $product_id)->first();

            if (empty($user_id) && Auth::check()) {
                $user_id = Auth::user()->user_id;
            }

            ### check account ID ###
            $sub_agent = Account::find($account_id);
            if (empty($sub_agent)) {
                throw new \Exception('Invalid account ID provided', -4);
            }

            if ($sub_agent->type != 'S') {
                throw new \Exception('Account is not sub-agent', -5);
            }

            if ($sub_agent->status != 'A') {
                throw new \Exception('Account is not active', -6);
            }

            ### check product ###
            $product = Product::find($product_id);
            if (empty($product)) {
                throw new \Exception('Invalid product ID provided', -1);
            }

            if ($product->status != 'A') {
                throw new \Exception('Product is not active', -2);
            }

            ### check denom ###
            $denom_obj = Denom::where('product_id', $product->id)
                ->where('id', $denom_id)
                ->where('status', 'A')
                ->first();

            if (empty($denom_obj)) {
                throw new \Exception('Invalid denomination provided', -3);
            }

            ### sub agent ###
            $ret = self::give_account_spiff($sub_agent, $product_id, $denom, $month, $phone, $trans_id, $user_id, $divide_qty, $phone_type, $sim, $esn, 0);
            if (!empty($ret['error_code'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }
            $paid_spiff = $ret['spiff_amt'];

            ### Transaction
            $trans = Transaction::find($trans_id);

            ### Give Special Spiff to Sub-Agent
            if ($month == 1) {
                ### give_special_spiff($trans_id, $phone, $product_id, $denom, $account_type, $account_id, $sim_obj, $esn_obj)
                SpiffSetupSpecial::give_special_spiff($trans, $phone, $product_id, $denom, 'S', $sub_agent->id, $sim_obj, $esn_obj);
            }

            ### distributor ###
            $dist = Account::where('id', $sub_agent->parent_id)
              ->where('type', 'D')
              #->where('status', 'A')
              ->first();
            if (!empty($dist)) {
                $ret = self::give_account_spiff($dist, $product_id, $denom, $month, $phone, $trans_id, $user_id, $divide_qty, $phone_type, $sim, $esn, $paid_spiff);
                if (!empty($ret['error_code'])) {
                    throw new \Exception($ret['error_msg'], $ret['error_code']);
                }

                $paid_spiff += $ret['spiff_amt'];

                ### Give Special Spiff to Distributor
                if ($month == 1) {
                    ### give_special_spiff($trans_id, $phone, $product_id, $denom, $account_type, $account_id)
                    SpiffSetupSpecial::give_special_spiff($trans, $phone, $product_id, $denom, 'D', $dist->id, $sim_obj, $esn_obj);
                }

            }

            ### master ###
            $master = Account::where('id', $sub_agent->master_id)
                ->where('type', 'M')
                #->where('status', 'A')
                ->first();
            if (empty($master)) {
                throw new \Exception('Invalid master ID found', -8);
            }

            $ret = self::give_account_spiff($master, $product_id, $denom, $month, $phone, $trans_id, $user_id, $divide_qty, $phone_type, $sim, $esn, $paid_spiff);
            if (!empty($ret['error_code'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            ### Give Special Spiff to Master
            if ($month == 1) {
                ### give_special_spiff($trans_id, $phone, $product_id, $denom, $account_type, $account_id)
                SpiffSetupSpecial::give_special_spiff($trans, $phone, $product_id, $denom, 'M', $master->id, $sim_obj, $esn_obj);
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {
            Helper::log('### SpiffProcessor::give_spiff ###', [
                'Exception' => ['EX-CODE' => $ex->getCode(), 'EX-MSG' => $ex->getMessage()]
            ]);

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }

    }

    public static function get_account_spiff_amt($account, $product_id, $denom, $month, $divide_qty = 1, $phone_type = null, $sim = null, $esn = null, $sim_group = null) {

        Helper::log('### get_account_spiff_amt ###', [
            'account' => $account,
            'product_id' => $product_id,
            'sim_group' => $sim_group,
            'denom' => $denom
        ]);

        $spiff_setup = null;

        $hold_spiff_column_name= Helper::get_hold_spiff_by_product($product_id);

        $hold_spiff_check = Helper::check_account_spiff_template($account, $hold_spiff_column_name);

        if($hold_spiff_check){ // return 0 When [hold_spiff = Y] in S,D,M
            return [
                'spiff_amt' => 0,
                'orig_spiff_amt' => 0
            ];
        }

        $spiff_setup = SpiffSetup::where('product_id', $product_id)
            ->where('denom', $denom)
            ->where('template', $account->spiff_template)
            ->first();

        ### no spiff found but nothing to report ###
        if (empty($spiff_setup)) {
            Helper::log("### No Spiff Found ###", [
                'account_type' => $account->type,
                'product_id' => $product_id,
                'denom' => $denom
            ]);

            return [
                'spiff_amt' => 0,
                'orig_spiff_amt' => 0
            ];
        }

        $spiff_amt = self::get_spiff_amt($spiff_setup, $month) / $divide_qty;
        $override_amt = null;

        if(!empty($sim_group)) {
            $spiff_months = StockSim::get_spiff_month($esn, $sim, $product_id, $sim_group);
        }else{
            $spiff_months = StockSim::get_spiff_month($esn, $sim, $product_id);
        }
        $spiff_month_array = explode('|', $spiff_months);
        if (!in_array($month, $spiff_month_array)) {
            Helper::log("### Invalid Spiff Month ###", [
                'month' => $month,
                'spiff_month_array' => $spiff_month_array
            ]);
            return [
                'spiff_amt' => 0,
                'orig_spiff_amt' => 0
            ];
        }

        if ($month == 1) {

            if(!empty($sim_group)) {
                $sim_obj = StockSim::where('sim_serial', $sim)->where('sim_group', $sim_group)->first();
            }else{
                $sim_obj = StockSim::where('sim_serial', $sim)->where('product', $product_id)->first();
            }
            $spiff_amt += StockSim::get_spiff_2_amt($account->type, $sim_obj);

            if(!empty($sim_group)) {
                $override_amt = StockSim::get_override_amt($account->type, $sim, $esn, $month, $product_id, $sim_group);
            }else{
                $override_amt = StockSim::get_override_amt($account->type, $sim, $esn, $month, $product_id);
            }
        }

        $orig_spiff_amt = $spiff_amt;
        if (!is_null($override_amt) && is_numeric($override_amt)) {
            $spiff_amt = $override_amt;
        }

        Helper::log('### Spiff Amt ###', [
            'spiff_amt' => $spiff_amt,
            'override_amt' => $override_amt,
            'orig_spiff_amt' => $orig_spiff_amt
        ]);

        return [
            'spiff_amt' => $spiff_amt,
            'orig_spiff_amt' => $orig_spiff_amt
        ];
    }

    private static function give_account_spiff($account, $product_id, $denom, $month, $phone, $trans_id = null, $user_id = null, $divide_qty = 1, $phone_type = null, $sim = null, $esn = null, $paid_spiff = 0) {

        $ret = self::get_account_spiff_amt($account, $product_id, $denom, $month, $divide_qty, $phone_type, $sim, $esn);

        $spiff_amt = $ret['spiff_amt'] - $paid_spiff;
        $orig_spiff_amt = $ret['orig_spiff_amt'];

        if ($spiff_amt <= 0) {
            return [
                'error_code' => '',
                'error_msg' => '',
                'spiff_amt' => 0
            ];
        }

        ### give spiff ###
        $spiff_trans = new SpiffTrans;
        $spiff_trans->trans_id = $trans_id;
        $spiff_trans->phone = $phone;
        $spiff_trans->type = 'S';
        $spiff_trans->account_id = $account->id;
        $spiff_trans->product_id = $product_id;
        $spiff_trans->denom = $denom;
        $spiff_trans->account_type = $account->type;
        $spiff_trans->spiff_month = $month;
        $spiff_trans->spiff_amt = $spiff_amt;
        $spiff_trans->orig_spiff_amt = $orig_spiff_amt;
        $spiff_trans->created_by = $user_id;
        $spiff_trans->cdate = Carbon::now();
        $spiff_trans->save();

        return [
            'error_code' => '',
            'error_msg' => '',
            'spiff_amt' => $spiff_amt
        ];
    }

    private static function get_spiff_amt(SpiffSetup $spiff_setup, $month) {
        $spiff_amt = 0;
        switch ($month) {
            case 1:
                $spiff_amt = $spiff_setup->spiff_1st;
                break;
            case 2:
                $spiff_amt = $spiff_setup->spiff_2nd;
                break;
            case 3:
                $spiff_amt = $spiff_setup->spiff_3rd;
                break;
        }

        return $spiff_amt;
    }

}