<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/28/17
 * Time: 8:15 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Lib\PaymentProcessor;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ActivityController extends Controller
{

    public function show(Request $request) {

        $bill_date = Carbon::today()->startOfWeek()->copy()->addDays(7);
        $period_from = Carbon::today()->startOfWeek();
        $period_to = Carbon::today()->startOfWeek()->copy()->addDays(6);

        $bill = PaymentProcessor::get_sub_agent_balance(
            Auth::user()->account_id,
            $bill_date,
            $period_from,
            $period_to
        );

        $bill['bill_date'] = $bill_date->format('Y-m-d');
        $bill['period_from'] = $period_from->format('Y-m-d');
        $bill['period_to'] = $period_to->format('Y-m-d');

        $bill['extra'] = $bill['spiff_credit'] - $bill['spiff_debit'] + $bill['rebate_credit'] - $bill['rebate_debit'] + $bill['residual'] + $bill['adjustment'] + $bill['promotion'];
        $bill['payable'] = $bill['starting_balance'] + $bill['net_revenue'] + $bill['fee'] + $bill['pm_fee'] + $bill['consignment'];
        $bill['paid_total'] = $bill['deposit_paid_amt'] + $bill['ach_paid_amt'] + $bill['dist_paid_amt'];

        $query = Transaction::leftJoin('vw_spiff_trans', function($join) {
                $join->on('vw_spiff_trans.trans_id', 'transaction.id');
                $join->on('vw_spiff_trans.account_id', 'transaction.account_id');
                $join->on('vw_spiff_trans.type', 'transaction.type');
                $join->where('vw_spiff_trans.spiff_month', 1);
            })->leftJoin('rebate_trans', function($join) {
                $join->on('rebate_trans.trans_id', 'transaction.id');
                $join->on('rebate_trans.account_id', 'transaction.account_id');
                $join->where('rebate_trans.rebate_month', 1);
            })->where('transaction.account_id', Auth::user()->account_id)
            ->where('transaction.status', '!=', 'F')
            ->where('transaction.cdate', '>=', $period_from)
            ->where('transaction.cdate', '<', $period_to->copy()->addDay());

        if ($request->export == 'Y') {
            $data = $query->select('transaction.*', 'vw_spiff_trans.spiff_amt', 'rebate_trans.rebate_amt')->orderBy('transaction.cdate', 'desc')->get();
            Excel::create('transactions', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $new_data = [];

                    $collection_amt_total = 0;
                    $net_revenue_total = 0;
                    $fee_total = 0;
                    $spiff_total = 0;
                    $rebate_total = 0;

                    foreach ($data as $o) {
                        $collection_amt_total += $o->collection_amt;
                        $net_revenue_total += $o->net_revenue;
                        $fee_total += ($o->fee + $o->pm_fee);
                        $spiff_total += $o->spiff_amt;
                        $rebate_total += $o->rebate_amt;

                        $row = [
                            'Tx.ID' => $o->id,
                            'Type' => $o->type_name,
                            'Carrier' => $o->carrier(),
                            'Product' => $o->product_name(),
                            'Action' => $o->action,
                            'Phone' => $o->action == 'PIN' ? $o->pin : $o->phone,
                            'Denom($)' => $o->denom,
                            'RTR.M' => $o->rtr_month,
                            'Gross($)' => '$' . number_format($o->collection_amt, 2),
                            'Net.Payable.($)' => '$' . number_format($o->net_revenue, 2),
                            'Vendor.Fee($)' => '$' . number_format($o->fee + $o->pm_fee, 2),
                            'Spiff($)' => '$' . number_format($o->spiff_amt, 2),
                            'Rebate($)' => '$' . number_format($o->rebate_amt, 2),
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
                        'Net.Payable.($)' => '$' . number_format($net_revenue_total,2),
                        'Vendor.Fee($)' => '$' . number_format($fee_total, 2),
                        'Spiff($)' => '$' . number_format($spiff_total, 2),
                        'Rebate($)' => '$' . number_format($rebate_total, 2),
                        'Date' => ''
                    ];

                    $sheet->fromArray($new_data);

                });

            })->export('xlsx');
        }



        $collection_amt = $query->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt, -transaction.collection_amt)"));
        $net_revenue = $query->sum(DB::raw("if(transaction.type = 'S', transaction.net_revenue, -transaction.net_revenue)"));
        $fee = $query->sum(DB::raw("if(transaction.type = 'S', transaction.fee + transaction.pm_fee, -(transaction.fee + transaction.pm_fee))"));
        $spiff = $query->sum(DB::raw("if(transaction.type = 'S', vw_spiff_trans.spiff_amt, -vw_spiff_trans.spiff_amt)"));
        $rebate = $query->sum(DB::raw("if(transaction.type = 'S', rebate_trans.rebate_amt, -rebate_trans.rebate_amt)"));

        $data = $query->select('transaction.*', 'vw_spiff_trans.spiff_amt', 'rebate_trans.rebate_amt')->orderBy('transaction.cdate', 'desc')->paginate(10);

        return view('sub-agent.reports.activity', [
            'bill' => $bill,
            'data' => $data,
            'collection_amt' => $collection_amt,
            'net_revenue' => $net_revenue,
            'fee' => $fee,
            'spiff' => $spiff,
            'rebate' => $rebate
        ]);
    }

}