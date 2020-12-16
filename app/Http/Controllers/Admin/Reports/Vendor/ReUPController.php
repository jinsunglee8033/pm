<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/5/17
 * Time: 2:44 PM
 */

namespace App\Http\Controllers\Admin\Reports\Vendor;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\RebateProcessor;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\Residual;
use App\Model\ReupRTR;
use App\Model\ROKSim;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ReUPController extends Controller
{

    public function show(Request $request) {
        $sdate = Carbon::now()->copy()->subDays(90);
        $edate = Carbon::now();

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $query = ReupRTR::query();
        if (Auth::user()->account_type != 'L') {
            $account = Account::find(Auth::user()->account_id);

            $query = $query->join('accounts', 'accounts.id', 'reup_rtr.account_id')
                ->where('accounts.path', 'like', $account->path . '%');

        }

        if (!empty($request->phone)) {
            $query = $query->where('reup_rtr.mdn', 'like', '%' . $request->phone . '%');
        }

        if (!empty($sdate)) {
            $query = $query->where('reup_rtr.cdate', '>=', $sdate);
        }

        if (!empty($edate)) {
            $query = $query->where('reup_rtr.cdate', '<=', $edate);
        }

        $data = $query->select('reup_rtr.*')
            ->paginate();

        return view('admin.reports.vendor.reup', [
            'data' => $data,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'phone' => $request->phone
        ]);
    }

    public function upload(Request $request) {

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'file' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

                DB::rollback();
                $this->output($msg);
            }

            $key = 'file';

            if (Input::hasFile($key) && Input::file($key)->isValid()) {
                $path = Input::file($key)->getRealPath();

                Helper::log('### FILE ###', [
                    'key' => $key,
                    'path' => $path
                ]);

                $name = Input::file($key)->getClientOriginalName();
                if (!ends_with($name, '.csv')) {
                    DB::rollback();
                    $this->output('Please select valid .csv file from ReUP export');
                }
                $handle = fopen($path, "r");
                if ($handle) {

                    $cnt = 1;


                    while (($line = fgets($handle)) !== false) {

                        ### ignore first line - header ###
                        if ($cnt == 1) {
                            $cnt++;
                            continue;
                        }

                        Helper::log('### LINE ###', $line);

                        list(
                            $rok_id, $dealer_name, $mdn, $week_ending, $renewal_date, $plan_value,
                            $total_m2_bonus, $total_m2_renewal, $total_m3_bonus, $total_m3_renewal,
                            $total_residuals, $total_residual_payout, $total_dealer_payout
                        ) = explode(",", $line);

                        $plan_value = str_replace("$", "", $plan_value);
                        $total_m2_bonus = str_replace("$", "", $total_m2_bonus);
                        $total_m3_bonus = str_replace("$", "", $total_m3_bonus);
                        $total_residual_payout = str_replace("$", "", $total_residual_payout);
                        $total_dealer_payout = str_replace("$", "", $total_dealer_payout);

                        $rr = ReupRTR::find($rok_id);
                        if (!empty($rr)) {
                            throw new \Exception('Line ' . ($cnt + 1) . ' has been imported already - ROK.ID : ' . $rok_id , '-1');
                        }

                        $week_ending = Carbon::createFromFormat('m/j/Y H:i:s', $week_ending . ' 00:00:00');
                        $renewal_date = Carbon::createFromFormat('m/j/Y H:i:s', $renewal_date . ' 00:00:00');

                        $rr = new ReupRTR;
                        $rr->rok_id = $rok_id;
                        $rr->dealer_name = $dealer_name;
                        $rr->mdn = $mdn;
                        $rr->week_ending = $week_ending;
                        $rr->renewal_date = $renewal_date;
                        $rr->plan_value = $plan_value;
                        $rr->total_m2_bonus = $total_m2_bonus;
                        $rr->total_m2_renewal = $total_m2_renewal;
                        $rr->total_m3_bonus = $total_m3_bonus;
                        $rr->total_m3_renewal = $total_m3_renewal;
                        $rr->total_residuals = $total_residuals;
                        $rr->total_residual_payout = $total_residual_payout;
                        $rr->total_dealer_payout = $total_dealer_payout;
                        $rr->cdate = Carbon::now();

                        $user_id = Auth::user()->user_id;

                        $rr->created_by = $user_id;

                        ### find activation account ###
                        $trans = Transaction::where('type', 'S')
                            ->where('phone', $mdn)
                            ->whereIn('product_id', ['WROKC', 'WROKG', 'WROKS'])
                            ->whereIn('action', ['Activation', 'Port-In'])
                            #->where('cdate', '>=', Carbon::today()->copy()->addDays(-100))
                            ->where('status', 'C')
                            ->first();

                        $account_id = null;
                        if (!empty($trans)) {
                            $account_id = $trans->account_id;
                            $rr->account_id = $account_id;

                            $account = Account::find($account_id);
                            $account_type = '';
                            if (!empty($account)) {
                                $account_type = $account->type;
                            }

                            ### SIM / ESN 을 찾아서 type 이 B / P 이면 spiff 주지 말것 ###
                            $spiff_month = ROKSim::get_spiff_month($trans->phone_type, $trans->product_id, $trans->esn, $trans->sim);
                            $rebate_month = ROKESN::get_rebate_month($trans->esn);

                            $spiff_month_array = explode("|", $spiff_month);
                            $rebate_month_array = explode("|", $rebate_month);

                            ### 2nd month spiff ###
                            if ($total_m2_bonus > 0 && in_array(2, $spiff_month_array)) {
                                $st = new SpiffTrans;
                                $st->type = 'S';
                                $st->phone = $mdn;
                                $st->account_id = $account_id;
                                $st->product_id = $trans->product_id;
                                $st->denom = $plan_value;
                                $st->account_type = $account_type;
                                $st->spiff_month = 2;
                                $st->spiff_amt = $total_m2_bonus;
                                $st->cdate = Carbon::now();
                                $st->created_by = $user_id;
                                $st->save();

                                $rr->spiff_trans_id = DB::getPdo()->lastInsertId();;
                            }

                            ### 3rd month spiff ###
                            if ($total_m3_bonus > 0 && in_array(3, $spiff_month_array)) {
                                $st = new SpiffTrans;
                                $st->type = 'S';
                                $st->phone = $mdn;
                                $st->account_id = $account_id;
                                $st->product_id = $trans->product_id;
                                $st->denom = $plan_value;
                                $st->account_type = $account_type;
                                $st->spiff_month = 3;
                                $st->spiff_amt = $total_m3_bonus;
                                $st->cdate = Carbon::now();
                                $st->created_by = $user_id;
                                $st->save();

                                $rr->spiff_trans_id = DB::getPdo()->lastInsertId();;
                            }

                            ### rebate - 2nd month ###
                            if (in_array(2, $rebate_month_array)) {

                            }

                            ### rebate - 3rd month ###
                            if (in_array(2, $rebate_month_array)) {

                            }

                            ### give residual ###
                            if ($total_residual_payout > 0) {
                                $rs = new Residual;
                                $rs->account_id = $account_id;
                                $rs->mdn = $mdn;
                                $rs->act_date = $trans->cdate;
                                $rs->rtr_date = $renewal_date;
                                $rs->amt = $total_residual_payout;
                                $rs->comments = 'ReUP RTR Residual';
                                $rs->cdate = Carbon::now();
                                $rs->created_by = $user_id;
                                $rs->save();

                                $rr->residual_id = DB::getPdo()->lastInsertId();;
                            }



                        }


                        $rr->save();

                        $cnt++;
                    }

                    fclose($handle);
                } else {
                    // error opening the file.
                    DB::rollback();
                    $this->output('Error while opening file');
                }
            } else {
                DB::rollback();
                $this->output('Please select valid file');
            }

            DB::commit();

            $this->output('Your request has been processed successfully!', true, false);

        } catch (\Exception $ex) {
            DB::rollback();
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    private function output($msg, $close_modal = false, $is_error = true) {
        echo "<script>";

        if ($close_modal) {
            echo "parent.close_modal('div_upload');";
        }

        $msg = addslashes($msg);
        $msg = str_replace("\r\n", "\t", $msg);
        $msg = str_replace("\n", "\t", $msg);
        $msg = str_replace("\r", "\t", $msg);

        if ($is_error) {
            echo "parent.myApp.hideLoading();";
            echo "parent.myApp.showError('$msg');";
        } else {
            echo "parent.myApp.hideLoading();";
            echo "parent.myApp.showSuccess('$msg');";
        }

        echo "</script>";
        exit;
    }

}