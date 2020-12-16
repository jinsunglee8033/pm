<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 10/18/18
 * Time: 2:19 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\Lib\Helper;

class ATTBatchFee extends Model
{
    protected $table = 'att_batch_fee';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function get_batch_fee($account_id) {
        $batch_fee = ATTBatchFee::where('account_id', $account_id)->first();
        return $batch_fee;
    }

    public static function get_for_rtr_fee($batch_fee, $base_fee, $tier_fees) {
        if (empty($batch_fee)) {
            return $base_fee->for_rtr;
        }

        $today = \Carbon\Carbon::today()->format('Y-m-d');

        if ($today >= $batch_fee->for_rtr_sdate && $today <= $batch_fee->for_rtr_edate) {
            return empty($batch_fee->for_rtr) ? 0 : $batch_fee->for_rtr;
        }

        if (!empty($batch_fee->for_rtr_tier)) {
            $tier_fee = $tier_fees[$batch_fee->for_rtr_tier];
            return empty($tier_fee) ? 0 : $tier_fee->for_rtr;
        }

        return $base_fee->for_rtr;
    }

    public static function get_for_sim_swap_fee($batch_fee, $base_fee, $tier_fees) {
        if (empty($batch_fee)) {
            return $base_fee->for_sim_swap;
        }

        $today = \Carbon\Carbon::today()->format('Y-m-d');

        if ($today >= $batch_fee->for_sim_swap_sdate && $today <= $batch_fee->for_sim_swap_edate) {
            return empty($batch_fee->for_sim_swap) ? 0 : $batch_fee->for_sim_swap;
        }

        if (!empty($batch_fee->for_sim_swap_tier)) {
            $tier_fee = $tier_fees[$batch_fee->for_sim_swap_tier];
            return empty($tier_fee) ? 0 : $tier_fee->for_sim_swap;
        }

        return $base_fee->for_sim_swap;
    }

    public static function get_for_plan_change_fee($batch_fee, $base_fee, $tier_fees) {
        if (empty($batch_fee)) {
            return $base_fee->for_plan_change;
        }

        $today = \Carbon\Carbon::today()->format('Y-m-d');

        if ($today >= $batch_fee->for_plan_change_sdate && $today <= $batch_fee->for_plan_change_edate) {
            return empty($batch_fee->for_plan_change) ? 0 : $batch_fee->for_plan_change;
        }

        if (!empty($batch_fee->for_plan_change_tier)) {
            $tier_fee = $tier_fees[$batch_fee->for_plan_change_tier];
            return empty($tier_fee) ? 0 : $tier_fee->for_plan_change;
        }

        return $base_fee->for_plan_change;
    }
}
