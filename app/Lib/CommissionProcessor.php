<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/14/17
 * Time: 8:07 PM
 */

namespace App\Lib;


use App\Model\Account;
use App\Model\Commission;
use App\Model\Denom;
use App\Model\RateDetail;
use App\Model\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CommissionProcessor
{

    public static function create($trans_id, $regen = false, $multi_line_denom = null) {

        try {

            $trans = Transaction::find($trans_id);
            if (empty($trans)) {
                return [
                    'error_code' => -1,
                    'error_msg' => '[CP:create] Invalid transaction ID provided'
                ];
            }

            $denom = Denom::where('product_id', $trans->product_id)
                ->where('min_denom', '<=', isset($multi_line_denom) ? $multi_line_denom : $trans->denom)
                ->where('max_denom', '>=', isset($multi_line_denom) ? $multi_line_denom : $trans->denom)
                ->first();
            if (empty($denom)) {
                return [
                    'error_code' => -6,
                    'error_msg' => 'Unable to find denomination for : ' . $trans_id
                ];
            }

            ### 2. get all account related ###
            $account = Account::find($trans->account_id);
            if (empty($account)) {
                return [
                    'error_code' => -3,
                    'error_msg' => '[CP:create] Failed to get transaction account'
                ];
            }

            ### clear commission if needed ###
            if ($regen) {
                DB::statement("
                    delete from commission
                    where trans_id = :trans_id
                    and type = 'S'
                ", [
                    'trans_id' => $trans_id
                ]);
            }

            ### 1. check collection amount ###
            # 0 => do nothing
            if ($trans->collection_amt == 0) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'net_revenue' => $trans->collection_amt
                ];
            }

            if (!in_array($trans->action, ['RTR', 'PIN'])) {
                return [
                    'error_code' => '',
                    'error_msg' => '',
                    'net_revenue' => $trans->collection_amt
                ];
            }

            $accounts = DB::select("
                select 
                    b.id,
                    length(b.path) / 6 as depth,
                    b.rate_plan_id,
                    b.parent_id
                from accounts a
                    inner join accounts b on a.path like concat(b.path, '%')  
                where a.id = :account_id
                order by b.path asc
            ", [
                'account_id' => $trans->account_id
            ]);

            $parent_rates = null;
            $parent_id = null;
            $parent_plan_id = null;

            $user = Auth::user();
            if (empty($user)) {
                $user = User::find($trans->created_by);
            }

            $net_revenue = 0;

            if (count($accounts) > 0) {
                foreach ($accounts as $o) {
                    $rate_plan = RateDetail::where('rate_plan_id', $o->rate_plan_id)
                        ->where('denom_id', $denom->id)
                        ->where('action', $trans->action)
                        ->first();

                    if (empty($rate_plan)) {
                        return [
                            'error_code' => -2,
                            'error_msg' => '[CP:create] Invalid rate plan : ' . $o->rate_plan_id
                        ];
                    }

                    if ($o->depth == 1) {
                        $parent_rates = 100; //$rate_plan->rates;
                        $parent_id = $o->id;
                        $parent_plan_id = $o->rate_plan_id;

                        continue;
                    }

                    ### now give parent rates ###
                    $comm_rates = $parent_rates - $rate_plan->rates;
                    $comm_amt = $trans->collection_amt * $comm_rates / 100;

                    $comm = new Commission;
                    $comm->type = 'S';
                    $comm->trans_id = $trans_id;
                    $comm->account_id = $parent_id;
                    $comm->rate_plan_id = $parent_plan_id;
                    $comm->denom_id = $denom->id;
                    $comm->comm_rates = $comm_rates;
                    $comm->comm_amt = $comm_amt;
                    $comm->net_revenue = $trans->net_revenue;
                    $comm->cdate = $regen ? $trans->cdate : Carbon::now();
                    $comm->created_by = $user->user_id;
                    $comm->save();

                    ### we are at leaf node ###
                    if ((strlen($account->path) / 6) == $o->depth) {
                        $comm = new Commission;
                        $comm->type = 'S';
                        $comm->trans_id = $trans_id;
                        $comm->account_id = $o->id;
                        $comm->rate_plan_id = $o->rate_plan_id;
                        $comm->denom_id = $denom->id;
                        $comm->comm_rates = $rate_plan->rates;
                        $comm->comm_amt = $trans->collection_amt * $rate_plan->rates / 100;
                        $comm->net_revenue = $trans->net_revenue;
                        $comm->cdate = $regen ? $trans->cdate : Carbon::now();
                        $comm->created_by = $user->user_id;
                        $comm->save();

                        $net_revenue = $trans->collection_amt - $comm->comm_amt;
                        if ($net_revenue != $trans->net_revenue) {

                            $trans->net_revenue = $net_revenue;
                            $trans->save();

                            /*return [
                                'error_code' => -4,
                                'error_msg' => 'Calculated net revenue has issue for : ' . $trans_id . ' => ' . $net_revenue . ' / ' . $trans->net_revenue
                            ];*/
                        }
                    }

                    $parent_rates = $rate_plan->rates;
                    $parent_id = $o->id;
                    $parent_plan_id = $o->rate_plan_id;
                }
            } else {
                return [
                    'error_code' => -5,
                    'error_msg' => 'No account found to give commission for : ' . $trans_id
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => '',
                'net_revenue' => $net_revenue
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => '[CP:create] ' . $ex->getMessage() . ' : ' . $ex->getTraceAsString()
            ];
        }
    }

    public static function void($trans_id, $regen = false) {
        try {

            $cnt = Commission::where('trans_id', $trans_id)
                ->where('type', 'S')->count();
            if ($cnt < 1) {
                ### nothing to do
                return [
                    'error_code' => '',
                    'error_msg' => ''
                ];
            }

            if ($regen) {
                DB::statement("
                    delete from commission
                    where trans_id = :trans_id
                    and type = 'V'
                ", [
                    'trans_id' => $trans_id
                ]);
            }

            $ret = DB::statement("
                insert into commission(
                    trans_id,
                    type,
                    account_id,
                    rate_plan_id,
                    denom_id,
                    comm_rates,
                    comm_amt,
                    net_revenue,
                    cdate, 
                    created_by
                )
                select 
                    trans_id,
                    'V' as type,
                    account_id,
                    rate_plan_id,
                    denom_id,
                    comm_rates,
                    comm_amt,
                    net_revenue,
                    current_timestamp, 
                    :created_by as created_by
                from commission 
                where trans_id = :trans_id
                and type = 'S'
            ", [
              'created_by' => 'system',
              'trans_id' => $trans_id
            ]);

            if ($ret < 1) {
                return [
                    'error_code' => -1,
                    'error_msg' => '[CP:void] Failed to void commission: ' . $trans_id
                ];
            }

            return [
                'error_code' => '',
                'error_msg' => ''
            ];

        } catch (\Exception $ex) {
            return [
                'error_code' => 'E:' .$ex->getCode(),
                'error_msg' => '[CP:create] ' . $ex->getMessage()
            ];
        }
    }

}