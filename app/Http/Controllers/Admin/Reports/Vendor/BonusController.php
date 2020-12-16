<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/25/19
 * Time: 11:15 AM
 */

namespace App\Http\Controllers\Admin\Reports\Vendor;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\BonusException;
use App\Model\BonusRule;
use App\Model\Denom;
use App\Model\Product;
use App\Model\Residual;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use App\Model\CommissionUpload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class BonusController extends Controller
{

    public function show(Request $request) {
        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        $sdate = Carbon::now()->copy()->subDays(90);
        $edate = Carbon::now();

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $carrier = empty($request->carrier) ? 'AT&T' : $request->carrier;

        $query = BonusRule::where('carrier', $carrier)
            ->where('sdate', '>=', $sdate)
            ->where('sdate', '<=', $edate)
            ->where('edate', '>=', $sdate)
            ->where('edate', '<=', $edate);

        if (!empty($request->product_id)) {
            $query->where('product_id', $request->product_id);
        }

        if (!empty($request->denom)) {
            $query->where('denom', $request->denom);
        }

        if (!empty($request->status)) {
            $query->where('status', '=', $request->status);
        }
//            $query->where('status', '=', 'P');

//        $data = $query->orderBy('status', 'asc')->orderBy('product_id', 'asc')->orderBy('denom', 'asc')->get();

        $rules = $query->groupBy('carrier', 'product_id', 'denom', 'action', 'sdate', 'edate', 'status', 'version')
            ->get([
                'carrier', 'product_id', 'denom', 'action', 'sdate', 'edate', 'status', 'version',
                DB::raw('sum(1) as qty'),
                DB::raw('max(mdate) as mdate')
            ]);


//
//        dd($rules);
//        exit;


        foreach ($rules as $r) {
            $r->data = BonusRule::where('carrier', $r->carrier)->where('product_id', $r->product_id)->where('denom', $r->denom)
                ->where('action', $r->action)
                ->where('sdate', $r->sdate)
                ->where('edate', $r->edate)
                ->where('status', $r->status)
                ->where('version', $r->version)
                ->orderBy('qty_max', 'asc')
                ->get();
        }

        $products = Product::where('carrier', $carrier)->where('activation', 'Y')->where('status', 'A')->get();
        $denoms = null;
        if (!empty($request->product_id)) {
            $denoms = Denom::where('product_id', $request->product_id)->where('status', 'A')->orderBy('denom', 'asc')->get();
        }

        if ($request->calculate == 'Y') {
            foreach ($rules as $r) {
                $exps = BonusException::where('carrier', $r->carrier)->get();
                $exps_accts = [];
                foreach ($exps as $exp) {
                    $exps_accts[] = $exp->account_id;
                }

                if (empty($r->action)) {
                    $action_query = " and t.action in ('Activation', 'Port-In')";
                } else {
                    $action_query = " and t.action = '$r->action'";
                }

                $product_query = '';
                if (!empty($r->product_id)) {
                    $product_query = " and t.product_id = '$r->product_id'";
                }

                $denom_query = '';
                if (!empty($r->denom)) {
                    $denom_query = " and t.denom = $r->denom";
                }

                $r->cal_data = DB::select("
                    select t.account_id, a.name as account_name, a.parent_id, a.master_id, a.state, sum(1) qty
                      from `transaction` t 
                      join accounts a on t.account_id = a.id
                     where t.status = 'C'
                       and t.product_id in (select id from product where carrier = :carrier)
                       and (case when t.action = 'Activation' then t.cdate else t.mdate end) >= :sdate
                       and (case when t.action = 'Activation' then t.cdate else t.mdate end) <= :edate
                       $action_query $product_query $denom_query
                     group by 1,2,3,4,5
                     order by a.name
                ", [
                    'carrier' => $r->carrier,
                    'sdate' => $r->sdate,
                    'edate' => $r->edate
                ]);

                foreach ($r->cal_data as $cal_d) {
                    foreach ($r->data as $d) {
                        $cal_d->bonus_amt = 0;
                        if ($cal_d->qty >= $d->qty_min && $cal_d->qty <= $d->qty_max) {
                            $cal_d->type = $d->type;
                            $cal_d->bonus_amt =
                                $d->type == 'T' ? $d->bonus_amt : $d->bonus_amt * $cal_d->qty;
                            break;
                        }

                        if (empty($cal_d->type)) {
                            $cal_d->type = $d->type;
                            $cal_d->bonus_amt =
                                $d->type == 'T' ? $d->bonus_amt : $d->bonus_amt * $cal_d->qty;
                        }

                    }

                    if (count($exps_accts) > 0 && in_array($cal_d->account_id, $exps_accts)) {
                        $cal_d->pay_status = 'N';
                    } else {
                        $cal_d->pay_status = 'Y';
                    }
                }
            }
        }

        return view('admin.reports.vendor.bonus', [
            'rules' => $rules,
            'products' => $products,
            'denoms'  => $denoms,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'carrier' => $carrier,
            'product_id' => $request->product_id,
            'denom' => $request->denom,
            'status' => $request->status,
            'calculate' => $request->calculate
        ]);
    }

    public function add_rule(Request $request) {
        $rule = new BonusRule();
        $rule->carrier = $request->carrier;
        $rule->product_id = $request->product_id;
        $rule->denom = $request->denom;
        $rule->action = $request->action;
        $rule->version = $request->version;
        $rule->qty_min = $request->qty_min;
        $rule->qty_max = $request->qty_max;
        $rule->sdate = $request->sdate;
        $rule->edate = $request->edate;
        $rule->type = $request->type;
        $rule->bonus_amt = $request->bonus_amt;
        $rule->status = 'A';
        $rule->cdate = Carbon::now();
        $rule->save();

        return response()->json([
            'code' => '0'
        ]);
    }

    public function remove_rule(Request $request) {
        BonusRule::where('id', $request->id)->delete();

        return response()->json([
          'code' => '0'
        ]);
    }

    public function add_exception(Request $request) {
        $excep = BonusException::where('carrier', $request->carrier)->where('account_id', $request->account_id)->first();

        if (empty($excep)) {
            $excep = new BonusException();
            $excep->carrier = $request->carrier;
            $excep->account_id = $request->account_id;
            $excep->status = 'A';
            $excep->cdate = Carbon::now();
            $excep->save();
        }

        return response()->json([
          'code' => '0'
        ]);
    }

    public function pay_out(Request $request) {
        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return response()->json([
              'code' => '-1'
            ]);
        }

//        $r_data_query = BonusRule::where('carrier', $request->carrier);
//        if (!empty($request->product_id)) {
//            $r_data_query->where('product_id', $request->product_id);
//        }
//        if (!empty($request->denom)) {
//            $r_data_query->where('denom', $request->denom);
//        }
//        if (!empty($request->action)) {
//            $r_data_query->where('action', $request->action);
//        }
        $r_data = BonusRule::where('carrier', $request->carrier)
            ->where('product_id', $request->product_id)
            ->where('denom', $request->denom)
            ->where('action', $request->action)
            ->where('sdate', $request->sdate)
            ->where('edate', $request->edate)
            ->where('version', $request->version)
            ->where('status', 'A')
            ->orderBy('qty_max', 'asc')
            ->get();


//        return response()->json([
//          'code' => '0',
//          'r_date' => $r_data
//        ]);

        $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');

        $exps = BonusException::where('carrier', $request->carrier)->get();
        $exps_accts = [];
        foreach ($exps as $exp) {
            $exps_accts[] = $exp->account_id;
        }

        if (empty($request->action)) {
            $action_query = " and t.action in ('Activation', 'Port-In')";
        } else {
            $action_query = " and t.action = '$request->action'";
        }

        $product_query = '';
        if (!empty($request->product_id)) {
            $product_query = " and t.product_id = '$request->product_id'";
        }

        $denom_query = '';
        if (!empty($request->denom)) {
            $denom_query = " and t.denom = $request->denom";
        }

        $cal_data = DB::select("
            select t.account_id, sum(1) qty
              from `transaction` t 
             where t.status = 'C'
               and t.product_id in (select id from product where carrier = :carrier)
               and (case when t.action = 'Activation' then t.cdate else t.mdate end) >= :sdate
               and (case when t.action = 'Activation' then t.cdate else t.mdate end) <= :edate
               $action_query $product_query $denom_query
             group by 1
        ", [
          'carrier' => $request->carrier,
          'sdate' => $sdate,
          'edate' => $edate
        ]);

        foreach ($cal_data as $cal_d) {
            foreach ($r_data as $d) {
                $cal_d->type = $d->type;
                if ($d->type == 'T') {
                    $cal_d->bonus_amt = $d->bonus_amt / $cal_d->qty;
                } else {
                    $cal_d->bonus_amt = $d->bonus_amt;
                }

                if ($cal_d->qty >= $d->qty_min && $cal_d->qty <= $d->qty_max) {
                    break;
                }
            }

            if (count($exps_accts) > 0 && in_array($cal_d->account_id, $exps_accts)) {
            } else {
                ## PAY BONUS ##
                $detail_data = DB::select("
                    select t.*
                      from `transaction` t 
                     where t.status = 'C'
                       and t.product_id in (select id from product where carrier = :carrier)
                       and t.account_id = :account_id
                       and (case when t.action = 'Activation' then t.cdate else t.mdate end) >= :sdate
                       and (case when t.action = 'Activation' then t.cdate else t.mdate end) <= :edate
                       $action_query $product_query $denom_query
                ", [
                    'carrier' => $request->carrier,
                    'account_id' => $cal_d->account_id,
                    'sdate' => $request->sdate,
                    'edate' => $request->edate
                ]);

                foreach ($detail_data as $dd) {
                    if ($cal_d->bonus_amt > 0) {
                        $strans = new SpiffTrans();
                        $strans->type = 'S';
                        $strans->trans_id = $dd->id;
                        $strans->phone = $dd->phone;
                        $strans->account_id = $dd->account_id;
                        $strans->product_id = $dd->product_id;
                        $strans->denom = $dd->denom;
                        $strans->account_type = 'S';
                        $strans->spiff_month = 0;
                        $strans->spiff_amt = $cal_d->bonus_amt;
                        $strans->orig_spiff_amt = $cal_d->bonus_amt;
                        $strans->cdate = Carbon::now();
                        $strans->created_by = 'system';
                        $strans->note = 'Bonus Spiff';
                        $strans->save();
                    }
                }
            }
        }

        BonusRule::where('carrier', $request->carrier)
          ->where('product_id', $request->product_id)
          ->where('denom', $request->denom)
          ->where('action', $request->action)
          ->where('sdate', $request->sdate)
          ->where('edate', $request->edate)
          ->where('status', 'A')
          ->update([
              'status' => 'P',
              'mdate'  => Carbon::now()
          ]);

        return response()->json([
            'code' => '0'
        ]);

    }

    public function remove_exception(Request $request) {
        BonusException::where('carrier', $request->carrier)->where('account_id', $request->account_id)->delete();

        return response()->json([
          'code' => '0'
        ]);
    }

    private function output_error($msg) {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.myApp.showError(\"" . str_replace("\"", "'", $msg) . "\");";
        echo "</script>";
        exit;
    }

}