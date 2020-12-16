<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/21/17
 * Time: 10:43 AM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Billing;
use App\Model\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class InvoicesController extends Controller
{

    public function show(Request $request) {

        $sdate = $request->get('sdate', Carbon::today()->subDays(30)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->subDays(30)->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->format('Y-m-d');
        }

        $query = Billing::where('account_id', Auth::user()->account_id);


        if (!empty($sdate)) {
            $query = $query->where('bill_date', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('bill_date', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('bill_date', 'desc')->get();
            Excel::create('invoices', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'Invoice.No' => $a->id,
                            'Invoice.Date' => $a->bill_date,
                            'From' => $a->period_from,
                            'To' => $a->period_to,
                            'Gross' => $a->gross,
                            'Extra Earning' => $a->extra,
                            'Billed' => $a->bill_amt,
                            'Paid' => $a->paid_total,
                            'Ending.Balance' => $a->ending_balance,
                            'Ending.Deposit' => $a->ending_deposit
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->orderBy('bill_date', 'desc')->paginate(20);

        return view('sub-agent.reports.invoices', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate
        ]);
    }

    public function detail($id, Request $request) {
        try {

            $bill = Billing::find($id);
            if (empty($bill)) {
                throw new \Exception('Invalid invoice number provided');
            }

            $query = Transaction::leftJoin('vw_spiff_trans', function($join) {
                    $join->on('vw_spiff_trans.trans_id', 'transaction.id');
                    $join->on('vw_spiff_trans.account_id', 'transaction.account_id');
                    $join->where('vw_spiff_trans.spiff_month', 1);
                })->leftJoin('rebate_trans', function($join) {
                    $join->on('rebate_trans.trans_id', 'transaction.id');
                    $join->on('rebate_trans.account_id', 'transaction.account_id');
                    $join->where('rebate_trans.rebate_month', 1);
                })->where('transaction.account_id', $bill->account_id)
                ->where('transaction.status', '!=', 'F')
                ->where('transaction.cdate', '>=', Carbon::parse($bill->period_from . ' 00:00:00'))
                ->where('transaction.cdate', '<', Carbon::parse($bill->period_to . ' 23:59:59'));

            $collection_amt = $query->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt, -transaction.collection_amt)"));
            $net_revenue = $query->sum(DB::raw("if(transaction.type = 'S', transaction.net_revenue, -transaction.net_revenue)"));
            $fee = $query->sum(DB::raw("if(transaction.type = 'S', transaction.fee + transaction.pm_fee, -(transaction.fee + transaction.pm_fee))"));
            $spiff = $query->sum(DB::raw("if(transaction.type = 'S', ifnull(vw_spiff_trans.spiff_amt, 0), -(ifnull(vw_spiff_trans.spiff_amt, 0)))"));
            $rebate = $query->sum(DB::raw("if(transaction.type = 'S', ifnull(rebate_trans.rebate_amt, 0), -(ifnull(rebate_trans.rebate_amt, 0)))"));

            if ($request->export == 'Y') {
                $data = $query->select('transaction.*', 'vw_spiff_trans.spiff_amt')->orderBy('transaction.cdate', 'desc')->get();
                Excel::create('transactions', function($excel) use($data) {

                    $excel->sheet('reports', function($sheet) use($data) {

                        $new_data = [];

                        $collection_amt_total = 0;
                        $net_revenue_total = 0;
                        $fee_total = 0;
                        $spiff_total = 0;
                        $rebate_total = 0;
                        foreach ($data as $o) {
                            if($o->type_name == 'Void'){
                                $collection_amt_total -= $o->collection_amt;
                                $net_revenue_total -= $o->net_revenue;
                                $fee_total -= ($o->fee + $o->pm_fee);
                                $spiff_total -= $o->spiff_amt;
                                $rebate_total -= $o->rebate_total;
                            }else{
                                $collection_amt_total += $o->collection_amt;
                                $net_revenue_total += $o->net_revenue;
                                $fee_total += $o->fee + $o->pm_fee;
                                $spiff_total += $o->spiff_amt;
                                $rebate_total += $o->rebate_total;
                            }

                            $row = [
                                'Tx.ID' => $o->id,
                                'Type' => $o->type_name,
                                'Carrier' => $o->carrier(),
                                'Product' => $o->product_name(),
                                'Action' => $o->action,
                                'Phone' => $o->action == 'PIN' ? $o->pin : $o->phone,
                                'Denom($)' => $o->denom,
                                'RTR.M' => $o->rtr_month,
                                'Gross($)' => $o->type_name == 'Void' ? '$-'.number_format($o->collection_amt, 2) : '$'.number_format($o->collection_amt, 2),
                                'Net($)' => $o->type_name == 'Void' ? '$-'.number_format($o->net_revenue, 2) : '$'.number_format($o->net_revenue, 2),
                                'Vendor.Fee($)' => $o->type_name == 'Void' ? '$-'.number_format($o->fee + $o->pm_fee, 2) : '$'.number_format($o->fee + $o->pm_fee, 2),
                                'Spiff($)' => $o->type_name == 'Void' ? '$-'.number_format($o->spiff_amt, 2) : '$'.number_format($o->spiff_amt, 2),
                                'Rebate($)' => $o->type_name == 'Void' ? '$-' . number_format($o->rebate_amt, 2) : '$'.number_format($o->rebate_amt, 2),
                                'Date' => $o->cdate
                            ];

                            $new_data[] = $row;

                        }

                        $new_data[] = [
                            'Tx.ID' => '',
                            'Type' => '',
                            'Carrier' => '',
                            'Product' => '',
                            'Action' => '',
                            'Phone' => '',
                            'Denom($)' => '',
                            'RTR.M' => 'Total:',
                            'Gross($)' => '$' . number_format($collection_amt_total, 2),
                            'Net($)' => '$' . number_format($net_revenue_total,2),
                            'Vendor.Fee($)' => '$' . number_format($fee_total,2),
                            'Spiff($)' => '$' . number_format($spiff_total,2),
                            'Rebate($)' => '$' . number_format($rebate_total,2),
                            'Date' => ''
                        ];

                        $sheet->fromArray($new_data);

                    });

                })->export('xlsx');
            }

            $data = $query->select('transaction.*', 'vw_spiff_trans.spiff_amt', 'rebate_trans.rebate_amt')->orderBy('transaction.cdate', 'desc')->paginate(10);

            return view('sub-agent.reports.invoice-detail', [
                'bill' => $bill,
                'data' => $data,
                'collection_amt' => $collection_amt,
                'net_revenue' => $net_revenue,
                'fee' => $fee,
                'spiff' => $spiff,
                'rebate' => $rebate
            ]);


        } catch (\Exception $ex) {
            return back()->withInput()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }
}