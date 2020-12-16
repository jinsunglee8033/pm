<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 1/31/19
 * Time: 10:07 AM
 */

namespace App\Http\Controllers\Admin\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\BoomSimSwap;
use App\Model\Carrier;
use App\Model\GenPinTransaction;
use App\Model\Payment;
use App\Model\Product;
use App\Model\Vendor;
use App\Model\VWActivationRefill;
use App\Model\VWPayment;
use App\Model\VWPlanchangeSimswap;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

use PHPExcel_Helper_HTML;

class MonitorController extends Controller
{

    public function plansim(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'system',
              'admin']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $sdate = $request->get('sdate', Carbon::today()->startOfWeek()->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $query = VWPlanchangeSimswap::whereRaw("1=1");

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->category)) {
            $query = $query->where('category', $request->category);
        }

        if (!empty($request->account)) {
            $query = $query->whereRaw('account_id in (select id from accounts where id = \'' . $request->account . '\' or lower(name) like \'%' . strtolower($request->account) . '%\')');
        }

        if (!empty($request->mdn)) {
            $query = $query->where('mdn', $request->mdn);
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('cdate', 'desc')->get();
            Excel::create('planchange_simswap', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML();

                        $reports[] = [
                          'Date' => $a->id,
                          'Parent' => $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id)),
                          'Account #' => $a->account_id,
                          'Account Name' => $a->account_name,
                          'Category' => $a->category == 'S' ? 'Sim Swap' : 'Plan Change',
                          'MDN' => $a->mdn,
                          'SIM/Plan' => $a->ref
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $total = $query->count();
        $data = $query->orderBy('cdate', 'desc')->paginate(50);

        return view('admin.reports.monitor.plansim', [
          'sdate'       => $sdate,
          'edate'       => $edate,
          'quick'       => $request->quick,
          'category'    => $request->category,
          'account'     => $request->account,
          'mdn'         => $request->mdn,
          'total'       => $total,
          'data'        => $data
        ]);
    }

    public function recharge(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'system',
              'admin']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $sdate = $request->get('sdate', Carbon::today()->addDays(-150)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $acct_query = '';
        if (!empty($request->account)) {
            $acct_query = ' and (account_id = \'' . $request->account . '\' or lower(account_name) like \'%' . strtolower($request->account) . '%\')';
        }
        if (!empty($request->product)) {
            $acct_query .= ' and product_id = \'' . $request->product . '\'';
        }
        if (!empty($request->vendor)) {
            $acct_query .= ' and vendor_code = \'' . $request->vendor . '\'';
        }
        if (!empty($request->carrier)) {
            $carrier = $request->carrier;
            $prods = Product::where('carrier', $carrier)->get();
            $acct_query .= ' and ( ';
            foreach ($prods as $p){
                $acct_query .= ' product_id = \'' . $p->id . '\' or ';
            }
            $acct_query = substr($acct_query, 0, -3);
            $acct_query .= ' ) ';
        }

        $refills = DB::select("
            select account_id, account_name, qty_total, qty_1st, qty_2nd, qty_3rd, 
              round(qty_2nd / qty_total * 100, 2) refill_rates_2nd, round(qty_3rd / qty_total * 100, 2) refill_rates_3rd,
              rtr_month
              FROM
            (
            select account_id, account_name, 
                sum(1) qty_total,
                sum(spiff_1st) qty_1st, sum(spiff_2nd) qty_2nd, sum(spiff_3rd) qty_3rd,
                sum(rtr_month) rtr_month
              from vw_activation_recharge
             where cdate >= :sdate
               and cdate <= :edate
               " . $acct_query ."
             group by account_id, account_name
            ) t
        ", [
            'sdate' => Carbon::parse($sdate . ' 00:00:00'),
            'edate' => Carbon::parse($edate . ' 23:59:59')
        ]);

        $summary = DB::select("
            select qty_total, qty_1st, qty_2nd, qty_3rd, 
              round(qty_2nd / qty_total * 100, 2) refill_rates_2nd, round(qty_3rd / qty_total * 100, 2) refill_rates_3rd,
              rtr_month
              FROM
            (
            select 
                sum(1) qty_total,
                sum(spiff_1st) qty_1st, sum(spiff_2nd) qty_2nd, sum(spiff_3rd) qty_3rd,
                sum(rtr_month) rtr_month
              from vw_activation_recharge
             where cdate >= :sdate
               and cdate <= :edate
               " . $acct_query ."
            ) t
        ", [
          'sdate' => Carbon::parse($sdate . ' 00:00:00'),
          'edate' => Carbon::parse($edate . ' 23:59:59')
        ]);

        if (!empty($summary)) {
            $summary = $summary[0];
        }


        $summary_yms = DB::select("
            select dy, dm, qty_total, qty_1st, qty_2nd, qty_3rd, 
              round(qty_2nd / qty_total * 100, 2) refill_rates_2nd, round(qty_3rd / qty_total * 100, 2) refill_rates_3rd,
              rtr_month
              FROM
            (
            select 
                year(cdate) dy,
                month(cdate) dm, 
                sum(1) qty_total,
                sum(spiff_1st) qty_1st, sum(spiff_2nd) qty_2nd, sum(spiff_3rd) qty_3rd,
                sum(rtr_month) rtr_month
              from vw_activation_recharge
             where cdate >= :sdate
               and cdate <= :edate
               " . $acct_query ."
             group by 1, 2
             order by 1 asc, 2 asc
            ) t
        ", [
          'sdate' => Carbon::parse($sdate . ' 00:00:00'),
          'edate' => Carbon::parse($edate . ' 23:59:59')
        ]);

        if ($request->excel == 'Y') {
            Excel::create('recharge_rates', function($excel) use($refills, $summary_yms, $summary) {

                $excel->sheet('reports', function($sheet) use($refills) {
                    $reports = [];
                    foreach ($refills as $a) {

                        $wizard = new PHPExcel_Helper_HTML();

                        $reports[] = [
                            'Parent' => $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id)),
                            'Account #' => $a->account_id,
                            'Account Name' => $a->account_name,
                            'Qty.Total' => $a->qty_total,
                            'Qty.1st' => $a->qty_1st,
                            'Qty.2nd' => $a->qty_2nd,
                            'Rates.2nd' => $a->refill_rates_2nd,
                            'Qty.3rd' => $a->qty_3rd,
                            'Rates.3rd' => $a->refill_rates_3rd,
                            'RTR (SPP)' => $a->rtr_month
                        ];
                    }
                    $sheet->fromArray($reports);
                });

                $excel->sheet('summary', function($sheet) use($summary_yms, $summary) {

                    $reports = [];
                    foreach ($summary_yms as $s) {

                        $reports[] = [
                            'Year' => $s->dy,
                            'Month' => $s->dm,
                            'Qty.Total' => $s->qty_total,
                            'Qty.1st' => $s->qty_1st,
                            'Qty.2nd' => $s->qty_2nd,
                            'Rates.2nd' => $s->refill_rates_2nd,
                            'Qty.3rd' => $s->qty_3rd,
                            'Rates.3rd' => $s->refill_rates_3rd,
                            'RTR (SPP)' => $s->rtr_month
                        ];

                    }

                    $reports[] = [
                        'Year' => ' Total ',
                        'Month' => ' ',
                        'Qty.Total' => $summary->qty_total,
                        'Qty.1st' => $summary->qty_1st,
                        'Qty.2nd' => $summary->qty_2nd,
                        'Rates.2nd' => $summary->refill_rates_2nd,
                        'Qty.3rd' => $summary->qty_3rd,
                        'Rates.3rd' => $summary->refill_rates_3rd,
                        'RTR (SPP)' => $summary->rtr_month
                    ];
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $products   = Product::where('activation', 'Y')->where('status', 'A')->get();
        $vendors    = Vendor::where('status', 'A')->get();
        $carriers   = Carrier::where('has_activation', 'Y')->get();

        return view('admin.reports.monitor.recharge', [
            'sdate'       => $sdate,
            'edate'       => $edate,
            'quick'       => $request->quick,
            'account'     => $request->account,
            'product'     => $request->product,
            'products'    => $products,
            'vendors'     => $vendors,
            'vendor'      => $request->vendor,
            'carriers'    => $carriers,
            'carrier'     => $request->carrier,
            'refills'     => $refills,
            'summary'     => $summary,
            'summary_yms' => $summary_yms
        ]);
    }

    public function batchLookup(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'system',
                    'admin']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $sdate = $request->get('sdate', Carbon::today()->addDays(-150)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $accounts = trim($request->batch_accounts);
        $account_array = explode(PHP_EOL, $accounts);
        $acct_query = 'and account_id in (';
        foreach ($account_array as $account) {
            $acct_query .= $account .',';
        }
        $acct_query = substr($acct_query, 0, -1) . ')';

        $refills = DB::select("
            select account_id, account_name, qty_total, qty_1st, qty_2nd, qty_3rd, 
              round(qty_2nd / qty_total * 100, 2) refill_rates_2nd, round(qty_3rd / qty_total * 100, 2) refill_rates_3rd,
              rtr_month
              FROM
            (
            select account_id, account_name, 
                sum(1) qty_total,
                sum(spiff_1st) qty_1st, sum(spiff_2nd) qty_2nd, sum(spiff_3rd) qty_3rd,
                sum(rtr_month) rtr_month
              from vw_activation_recharge
             where cdate >= :sdate
               and cdate <= :edate
               " . $acct_query ."
             group by account_id, account_name
            ) t
        ", [
            'sdate' => Carbon::parse($sdate . ' 00:00:00'),
            'edate' => Carbon::parse($edate . ' 23:59:59')
        ]);

        Excel::create('recharge_rates', function($excel) use($refills) {

            $excel->sheet('reports', function($sheet) use($refills) {

                $reports = [];

                foreach ($refills as $a) {

                    $wizard = new PHPExcel_Helper_HTML();

                    $reports[] = [
                        'Parent' => $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id)),
                        'Account #' => $a->account_id,
                        'Account Name' => $a->account_name,
                        'Qty.Total' => $a->qty_total,
                        'Qty.1st' => $a->qty_1st,
                        'Qty.2nd' => $a->qty_2nd,
                        'Qty.3rd' => $a->qty_3rd,
                        'Rates.2nd' => $a->refill_rates_2nd,
                        'Rates.3rd' => $a->refill_rates_3rd
                    ];
                }
                $sheet->fromArray($reports);
            });
        })->export('xlsx');
    }

    public function esn_swap_history(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'system',
                    'admin']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $sdate = $request->get('sdate', Carbon::today()->subDays(7)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $query = GenPinTransaction::whereRaw("1=1");

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->account)) {
            $query = $query->whereRaw('account_id in (select id from accounts where id = \'' . $request->account . '\' or lower(name) like \'%' . strtolower($request->account) . '%\')');
        }

        if (!empty($request->mdn)) {
            $query = $query->where('mdn', $request->mdn);
        }

        if (!empty($request->sent)) {
            $query = $query->where('result', $request->sent);
        }

        if (!empty($request->type)) {
            $query = $query->where('type', $request->type);
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('cdate', 'desc')->get();
            Excel::create('ESN/MDN_Swap_History', function($excel) use($data) {
                $excel->sheet('reports', function($sheet) use($data) {
                    $reports = [];
                    foreach ($data as $a) {
                        $wizard = new PHPExcel_Helper_HTML();
                        $reports[] = [
                            'ID' => $a->id,
                            'Cdate' => $a->cdate,
                            'Account' => $wizard->toRichTextObject(Helper::get_account_name_html_by_id($a->account_id)),
                            'Customer ID' => $a->customer_id,
                            'MDN' => $a->mdn,
                            'ESN' => $a->esn,
                            'New ESN' => $a->new_esn,
                            'PIN' => $a->account_password,
                            'Type' => $a->type,
                            'Sent' => $a->result,
                            'Error msg' => $a->error_msg,
                            'Swap Cdate' => $a->swap_date
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $total = $query->count();
        $data = $query->orderBy('cdate', 'desc')->paginate(50);

        return view('admin.reports.monitor.esn-swap-history', [
            'sdate'       => $sdate,
            'edate'       => $edate,
            'quick'       => $request->quick,
            'category'    => $request->category,
            'account'     => $request->account,
            'mdn'         => $request->mdn,
            'sent'        => $request->sent,
            'type'        => $request->type,
            'total'       => $total,
            'data'        => $data
        ]);

    }

    public function boom_sim_swap(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'system',
                    'admin']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $sdate = $request->get('sdate', Carbon::today()->subDays(7)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $query = BoomSimSwap::whereRaw("1=1");

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->account_id)) {
            $query = $query->where('account_id', $request->account_id);
        }

        if (!empty($request->network)) {
            $query = $query->where('network', $request->network);
        }

        if (!empty($request->phone)) {
            $query = $query->where('phone', $request->phone);
        }

        if (!empty($request->sim)) {
            $query = $query->where('sim', $request->sent);
        }

        if (!empty($request->target_sim)) {
            $query = $query->where('target_sim', $request->type);
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('cdate', 'desc')->get();
            Excel::create('SIM_Swap_History_BOOM', function($excel) use($data) {
                $excel->sheet('reports', function($sheet) use($data) {
                    $reports = [];
                    foreach ($data as $a) {
                        $wizard = new PHPExcel_Helper_HTML();
                        $reports[] = [
                            'ID' => $a->id,
                            'Cdate' => $a->cdate,
                            'Account' => $a->account_id,
                            'Network' => $a->network,
                            'Phone' => $a->phone,
                            'SIM' => $a->sim,
                            'Target SIM' => $a->target_sim,
                            'Result' => $a->result,
                            'Error msg' => $a->error_msg
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $total = $query->count();
        $data = $query->orderBy('cdate', 'desc')->paginate(50);

        return view('admin.reports.monitor.boom-sim-swap', [
            'sdate'         => $sdate,
            'edate'         => $edate,
            'quick'         => $request->quick,
            'account_id'    => $request->account_id,
            'phone'         => $request->phone,
            'total'         => $total,
            'data'          => $data
        ]);

    }

}