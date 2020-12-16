<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/27/17
 * Time: 3:05 PM
 */

namespace App\Lib;


use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\RebateTrans;
use App\Model\StockESN;
use App\Model\SpiffSetup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RebateProcessor
{


    public static function void_rebate($trans_id) {
        try {
            $cnt = RebateTrans::where('trans_id', $trans_id)
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
                insert into rebate_trans (
                    type, trans_id, phone, account_id, product_id, denom, account_type, 
                    rebate_month, rebate_amt, cdate, created_by, orig_rebate_id
                )
                select
                    'V' as type, 
                    trans_id,
                    phone,
                    account_id,
                    product_id,
                    denom,
                    account_type,
                    rebate_month,
                    rebate_amt,
                    current_timestamp,
                    :user_id as created_by,
                    id as orig_rebate_id
                from rebate_trans
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
                update rebate_trans 
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
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }

    public static function give_rebate($rebate_type, $account_id, $product_id, $denom, $month, $phone, $rebate_max, $trans_id = null, $user_id = null, $divide_qty = 1, $esn = null, $denom_id = null) {

        try {

            if (empty($user_id) && Auth::check()) {
                $user_id = Auth::user()->user_id;
            }

            ### TODO : 이전에 줬는지 확인할것 ###
            $old_rebate_record = RebateTrans::where('esn', $esn)->where('product_id', $product_id)->first();
            if (!empty($old_rebate_record)) {
                return [
                    'error_code' => '',
                    'error_msg' => ''
                ];
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
//                ->where('denom', $denom)
                ->where('id', $denom_id)
                ->where('status', 'A')
                ->first();

            if (empty($denom_obj)) {
                throw new \Exception('Invalid denomination provided', -3);
            }

            ### sub agent ###
            $ret = self::give_account_rebate($rebate_type, $sub_agent->id, $sub_agent->type, $product_id, $denom, $month, $phone, $rebate_max, $trans_id, $user_id, $divide_qty, $esn, 0);
            if (!empty($ret['error_code'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }
            $paid_rebate = $ret['rebate_amt'];

            ### distributor ###
            $dist = Account::where('id', $sub_agent->parent_id)
                ->where('type', 'D')
                #->where('status', 'A')
                ->first();
            if (!empty($dist)) {
                $ret = self::give_account_rebate($rebate_type, $dist->id, $dist->type, $product_id, $denom, $month, $phone, 999, $trans_id, $user_id, $divide_qty, $esn, $paid_rebate);
                if (!empty($ret['error_code'])) {
                    throw new \Exception($ret['error_msg'], $ret['error_code']);
                }
                $paid_rebate += $ret['rebate_amt'];
            }

            ### master ###
            $master = Account::where('id', $sub_agent->master_id)
                ->where('type', 'M')
                #->where('status', 'A')
                ->first();
            if (empty($master)) {
                throw new \Exception('Invalid master ID found', -8);
            }

            $ret = self::give_account_rebate($rebate_type, $master->id, $master->type, $product_id, $denom, $month, $phone, 999, $trans_id, $user_id, $divide_qty, $esn, $paid_rebate);
            if (!empty($ret['error_code'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {
            Helper::log( '### ' . $ex->getCode() . ' ### ' . $ex->getMessage());

            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }

    }

    public static function get_account_rebate_amt($rebate_type, $account_obj, $product_id, $denom, $month, $divide_qty = 1, $esn = null) {
        $rebate_setup = SpiffSetup::where('product_id', $product_id)
            ->where('denom', $denom)
            ->where('template', $account_obj->spiff_template)
            ->first();

        /*** Todo change to denom_id
        $rebate_setup = SpiffSetup::where('product_id', $product_id)
            ->where('denom_id', 'denom_id')
            ->where('account_type', $account_type)
            ->first();
        ***/

        ### no spiff found but nothing to report ###
        if (empty($rebate_setup)) {
            return [
                'rebate_amt' => 0,
                'orig_rebate_amt' => 0,
                'override_msg' => ''
            ];
        }

        $rebate_amt = self::get_rebate_amt($rebate_type, $rebate_setup, $month) / $divide_qty;

        $override_obj = null;
        $override_msg = '';
        $override_obj = StockESN::get_override_amt($account_obj->type, $esn, $product_id);

        $orig_rebate_amt = $rebate_amt;
        if (!empty($override_obj)) {
            $temp_override_amt = $override_obj['override_amt'];
            if ($temp_override_amt !== null) {
                $rebate_amt = $override_obj['override_amt'];
                $override_msg = $override_obj['override_msg'];
            }
        }

        return [
            'rebate_amt' => $rebate_amt,
            'orig_rebate_amt' => $orig_rebate_amt,
            'override_msg' => $override_msg
        ];
    }

    private static function give_account_rebate($rebate_type, $account_id, $account_type, $product_id, $denom, $month, $phone, $rebate_max, $trans_id = null, $user_id = null, $divide_qty = 1, $esn = null, $paid_rebate = null) {

        $account_obj = Account::where('id', $account_id)->first();

        $ret = self::get_account_rebate_amt($rebate_type, $account_obj, $product_id, $denom, $month, $divide_qty, $esn);
        $rebate_amt = $ret['rebate_amt'] - $paid_rebate;
        $orig_rebate_amt = $ret['orig_rebate_amt'];
        $override_msg = $ret['override_msg'];

        if ($rebate_amt <= 0) {
            return [
                'error_code' => '',
                'error_msg' => '',
                'rebate_amt' => 0
            ];
        }

        $override_rebate_amt = $rebate_amt;
        if ($account_type == 'S') {
            $account = Account::find($account_id);
            if ($account->rebates_eligibility != 'Y') {
                $rebate_amt = 0;
                $override_msg = 'Rebates Eligibility is No. ' . $override_msg;
            } else {
                if (!empty($rebate_max)) {
                    $rebate_amt = $rebate_amt > $rebate_max ? $rebate_max : $rebate_amt;
                }
            }
        }

        ### give spiff ###
        $rebate_trans = new RebateTrans;
        $rebate_trans->esn = $esn;
        $rebate_trans->trans_id = $trans_id;
        $rebate_trans->phone = $phone;
        $rebate_trans->type = 'S';
        $rebate_trans->account_id = $account_id;
        $rebate_trans->product_id = $product_id;
        $rebate_trans->denom = $denom;
        $rebate_trans->account_type = $account_type;
        $rebate_trans->rebate_type = $rebate_type;
        $rebate_trans->rebate_month = $month;
        $rebate_trans->rebate_amt = $rebate_amt;
        $rebate_trans->orig_rebate_amt = $orig_rebate_amt;
        $rebate_trans->override_rebate_amt = $override_rebate_amt;
        $rebate_trans->created_by = $user_id;
        $rebate_trans->cdate = Carbon::now();
        $rebate_trans->description = $override_msg;
        $rebate_trans->save();

        return [
            'error_code' => '',
            'error_msg' => '',
            'rebate_amt' => $rebate_amt
        ];
    }

    private static function get_rebate_amt($rebate_type, SpiffSetup $rebate_setup, $month) {
        $rebate_amt = 0;
        switch ($month) {
            case 1:
                $rebate_amt = $rebate_type == 'R' ? $rebate_setup->regular_rebate_1st : $rebate_setup->byod_rebate_1st;
                break;
            case 2:
                $rebate_amt = $rebate_type == 'R' ? $rebate_setup->regular_rebate_2nd : $rebate_setup->byod_rebate_2nd;
                break;
            case 3:
                $rebate_amt = $rebate_type == 'R' ? $rebate_setup->regular_rebate_3rd : $rebate_setup->byod_rebate_3rd;
                break;
        }

        return $rebate_amt;
    }
}