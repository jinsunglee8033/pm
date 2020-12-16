<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ATTBatch extends Model
{
    protected $table = 'att_batch';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getStatusNameAttribute() {
        switch ($this->status) {
            case 'N':
                return 'New';
            case 'C':
                return 'Finished';
            case 'F':
                return 'Failed';
            case 'X':
                return 'Deleted';
            default:
                return $this->status;
        }
    }

    public function getForSimSwapStatusNameAttribute() {
        switch ($this->for_sim_swap_status) {
            case 'N':
                return 'New';
            case 'C':
                return 'Finished';
            case 'F':
                return 'Failed';
            default:
                return $this->for_sim_swap_status;
        }
    }

    public function getForPlanChangeStatusNameAttribute() {
        switch ($this->for_plan_change_status) {
            case 'N':
                return 'New';
            case 'C':
                return 'Finished';
            case 'F':
                return 'Failed';
            default:
                return $this->for_plan_change_status;
        }
    }

    public function getForRtrStatusNameAttribute() {
        switch ($this->for_rtr_status) {
            case 'N':
                return 'New';
            case 'C':
                return 'Finished';
            case 'F':
                return 'Failed';
            default:
                return $this->for_rtr_status;
        }
    }

    public static function check_max_rtr($authority, $account_id, $date) {
        ### Daily Max
        $daily = ATTBatch::where('account_id', $account_id)
            ->where('process_date', $date)
            ->where('for_rtr', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($daily) && $daily >= $authority->for_rtr_daily) {
            return [
                'code'  => '-1',
                'msg'   => 'You have reached daily limit [RTR] ' . $authority->for_rtr_daily
            ];
        }

        ### Weekly Max
        $weekly = ATTBatch::where('account_id', $account_id)
            ->where('process_date', '<=', $date)
            ->where('process_date', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $date)->addDays(-6))
            ->where('for_rtr', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($weekly) && $weekly >= $authority->for_rtr_weekly) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached weekly limit [RTR] ' . $authority->for_rtr_weekly
            ];
        }

        ### Monthly Max
        $monthly = ATTBatch::where('account_id', $account_id)
            ->where('process_date', '<=', $date)
            ->where('process_date', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $date)->addDays(-29))
            ->where('for_rtr', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($monthly) && $monthly >= $authority->for_rtr_monthly) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached monthly limit [RTR] ' . $authority->for_rtr_monthly
            ];
        }

        return [
            'code'  => '0'
        ];
    }

    public static function check_max_sim_swap($authority, $account_id, $date) {
        ### Daily Max
        $daily = ATTBatch::where('account_id', $account_id)
            ->where('process_date', $date)
            ->where('for_sim_swap', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($daily) && $daily >= $authority->for_sim_swap_daily) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached daily limit [SIM SWAP] ' . $authority->for_sim_swap_daily
            ];
        }

        ### Weekly Max
        $weekly = ATTBatch::where('account_id', $account_id)
            ->where('process_date', '<=', $date)
            ->where('process_date', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $date)->addDays(-6))
            ->where('for_sim_swap', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($weekly) && $weekly >= $authority->for_sim_swap_weekly) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached weekly limit [SIM SWAP] ' . $authority->for_sim_swap_weekly
            ];
        }

        ### Monthly Max
        $monthly = ATTBatch::where('account_id', $account_id)
            ->where('process_date', '<=', $date)
            ->where('process_date', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $date)->addDays(-29))
            ->where('for_sim_swap', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($monthly) && $monthly >= $authority->for_sim_swap_monthly) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached monthly limit [SIM SWAP] ' . $authority->for_sim_swap_monthly
            ];
        }

        return [
          'code'  => '0'
        ];
    }

    public static function check_max_plan_change($authority, $account_id, $date) {
        ### Daily Max
        $daily = ATTBatch::where('account_id', $account_id)
            ->where('process_date', $date)
            ->where('for_plan_change', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($daily) && $daily >= $authority->for_plan_change_daily) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached daily limit [PLAN CHANGE] ' . $authority->for_plan_change_daily
            ];
        }

        ### Weekly Max
        $weekly = ATTBatch::where('account_id', $account_id)
            ->where('process_date', '<=', $date)
            ->where('process_date', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $date)->addDays(-6))
            ->where('for_plan_change', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($weekly) && $weekly >= $authority->for_plan_change_weekly) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached weekly limit [PLAN CHANGE] ' . $authority->for_plan_change_weekly
            ];
        }

        ### Monthly Max
        $monthly = ATTBatch::where('account_id', $account_id)
            ->where('process_date', '<=', $date)
            ->where('process_date', '>=', \Carbon\Carbon::createFromFormat('Y-m-d', $date)->addDays(-29))
            ->where('for_plan_change', 'Y')
            ->where('status', '<>', 'X')
            ->count();
        if (!empty($monthly) && $monthly >= $authority->for_plan_change_monthly) {
            return [
              'code'  => '-1',
              'msg'   => 'You have reached monthly limit [PLAN CHANGE] ' . $authority->for_plan_change_monthly
            ];
        }

        return [
          'code'  => '0'
        ];
    }
}
