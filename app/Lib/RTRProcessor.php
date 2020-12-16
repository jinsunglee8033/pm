<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 6/16/17
 * Time: 3:33 PM
 */

namespace App\Lib;


use App\Jobs\ProcessRTR;
use App\Model\Product;
use App\Model\RTRQueue;
use App\Model\VendorDenom;
use Carbon\Carbon;

class RTRProcessor
{

    public static function applyManualRTR($phone, $recharge_on, $vendor_code, $rtr_pid, $denom, $user_id) {
        try {

            $run_at = $recharge_on > Carbon::now() ? $recharge_on : Carbon::now()->addMinutes(1);

            ### RTR Q ###
            $q = new RTRQueue;
            $q->trans_id = -1;
            $q->category = 'H2O';
            $q->phone = $phone;
            $q->vendor_code = $vendor_code;
            $q->vendor_pid = $rtr_pid;
            $q->amt = $denom;
            $q->run_at = $run_at;
            $q->seq = 'MANUAL-RTR';
            $q->result = 'N';
            $q->cdate = Carbon::now();
            $q->created_by = $user_id;
            $q->save();

            $job = (new ProcessRTR($q))
                ->onQueue('RTR')
                ->delay($run_at);

            Helper::log('### recharge_on ###', $recharge_on);
            Helper::log('### run_at ###', $run_at);

            dispatch($job);

            return [
                'error_msg' => '',
                'id' => $q->id
            ];

        } catch (\Exception $ex) {
            return [
                'error_msg' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ];
        }
    }

    public static function applyRTR($rtr_month, $sim_type, $trans_id, $category, $phone, $product_id, $vendor_code, $rtr_pid, $denom, $user_id, $add_to_q = true, $run_at = null, $start_month = 1, $fee = 0, $display_rtr_month = 0) {

        try {

            if (is_null($run_at)) {
                $run_at = Carbon::now();
            }

            for ($i = $start_month; $i <= $rtr_month; $i++) {

                if ($i == 1) {
                    $run_at = $run_at->addMinutes(5);
                } else {
                    $run_at = $run_at->addDays(29);
                }

                ### RTR Q ###
                $q = new RTRQueue;
                $q->trans_id = $trans_id;
                $q->category = $category;
                $q->phone = $phone;
                $q->product_id = $product_id;
                $q->vendor_code = $vendor_code;
                $q->vendor_pid = $rtr_pid;
                $q->amt = $denom;
                $q->fee = $fee;
                $q->run_at = $run_at;
                $q->seq = $i . '/' . (empty($display_rtr_month) ? $rtr_month : $display_rtr_month);
                $q->result = $add_to_q ? 'N' : 'S';
                if($q->result == 'S' && $i ==1){
                    $q->result_date = $run_at;
                }
                $q->cdate = Carbon::now();
                $q->created_by = $user_id;
                $q->save();

                if ($add_to_q) {
                    $job = (new ProcessRTR($q))
                        ->onQueue('RTR')
                        ->delay($run_at);

                    dispatch($job);
                }
            }

            return '';

        } catch (\Exception $ex) {
            return $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
        }
    }

}