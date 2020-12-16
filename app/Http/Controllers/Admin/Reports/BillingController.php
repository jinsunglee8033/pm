<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/19/17
 * Time: 2:28 PM
 */

namespace App\Http\Controllers\Admin\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\Billing;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BillingController extends Controller
{
    public function show(Request $request) {

//        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas',
//              'admin']) || !getenv('APP_ENV') == 'local')) {
//            return redirect('/admin');
//        }


        $sdate = $request->get('sdate', Carbon::today()->startOfWeek()->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->startOfWeek()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $account_type = $request->get('account_type');
        $account_id = $request->get('account_id');

        $account = Account::find(Auth::user()->account_id);

        $query = Billing::join('accounts', 'accounts.id', 'billing.account_id')
            ->leftJoin('ach_posting', 'ach_posting.id', 'billing.ach_id')
            ->where('accounts.path', 'like', $account->path . '%');

        if (!empty($sdate)) {
            $query = $query->where('billing.bill_date', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('billing.bill_date', '<=', Carbon::parse($edate . ' 23:59:59'));
        }


        if (!empty($account_type)) {
            $query = $query->where('accounts.type', $account_type);
        }

        if (!empty($account_id)) {

            $acct = Account::find($account_id);
            if (!empty($acct)) {
                $query = $query->whereRaw('accounts.path like ?',  [$acct->path . '%']);
            }


        }

        if (!empty($request->bounce)){
            if($request->bounce == 'Y') {
                $query = $query->whereNotNull('ach_posting.bounce_date');
            }else{
                $query = $query->whereNull('ach_posting.bounce_date');
            }
        }

        if ($request->excel == 'Y') {
            $bills = $query->orderBy('accounts.path', 'asc')->get();
            Excel::create('billing', function($excel) use($bills) {

                ini_set('memory_limit', '2048M');

                $excel->sheet('reports', function($sheet) use($bills) {

                    $data = [];

                    $wizard = new \PHPExcel_Helper_HTML;

                    foreach ($bills as $o) {
                        $row = [
                            'Bill.Date' => $o->bill_date,
                            'From' => $o->period_from,
                            'To' => $o->period_to,
                            'Parent' => $wizard->toRichTextObject(Helper::get_parent_name_html($o->account_id)),
                            'Account' => $wizard->toRichTextObject('<span>' . Helper::get_hierarchy_img($o->type) . '</span> ' . $o->name . ' (' . $o->account_id . ')'),
                            'Starting.Balance' => '$' . number_format($o->starting_balance, 2),
                            'Starting.Deposit' => '$' . number_format($o->starting_deposit, 2),
                            'New.Deposit' => '$' . number_format($o->new_deposit, 2),
                            'Deposit.Total' => '$' . number_format($o->deposit_total, 2),
                            'Sales' => '$' . number_format($o->sales, 2),
                            'Sales.Margin' => '$' . number_format($o->sales_margin, 2),
                            'Void' => '$' . number_format($o->void, 2),
                            'Void.Margin' => '$' . number_format($o->void_margin, 2),
                            'Gross' => '$' . number_format($o->gross, 2),
                            'Net.Margin' => '$' . number_format($o->net_margin, 2),
                            'Net.Revenue' => '$' . number_format($o->net_revenue, 2),
                            'Paid.For.Children' => '$' . number_format($o->children_paid_amt, 2),
                            'Spiff' => '$' . number_format($o->spiff_credit - $o->spiff_debit, 2),
                            'Rebate' => '$' . number_format($o->rebate_credit - $o->rebate_debit, 2),
                            'Residual' => '$' . number_format($o->residual, 2),
                            'Adjustment' => '$' . number_format($o->adjustment, 2),
                            'Promotion' => '$' . number_format($o->promotion, 2),
                            'Bill.Amt' => '$' . number_format($o->bill_amt, 2),
                            'Paid.By.Deposit' => '$' . number_format($o->deposit_paid_amt, 2),
                            'Paid.By.ACH' => '$' . number_format($o->ach_paid_amt, 2),
                            'Paid.By.Parent' => '$' . number_format($o->dist_paid_amt, 2),
                            'Ending.Balance' => '$' . number_format($o->ending_balance, 2),
                            'Ending.Deposit' => '$' . number_format($o->ending_deposit, 2)
                        ];

                        $data[] = $row;

                    }

                    $sheet->fromArray($data);

                });

            })->export('xlsx');

        }

        $starting_balance = $query->sum('starting_balance');
        $deposit_total = $query->sum('deposit_total');
        $sales = $query->sum('sales');
        $sales_margin = $query->sum('sales_margin');
        $adjustment = $query->sum('adjustment');
        $void = $query->sum('void');
        $void_margin = $query->sum('void_margin');
        $net_revenue = $query->sum('net_revenue');
        $bill_amt = $query->sum('bill_amt');
        $deposit_paid_amt = $query->sum('deposit_paid_amt');
        $ach_paid_amt = $query->sum('ach_paid_amt');
        $dist_paid_amt = $query->sum('dist_paid_amt');
        $ending_balance = $query->sum('ending_balance');
        $ending_deposit = $query->sum('ending_deposit');

        $bills = $query->select("billing.*", "accounts.name", "accounts.type", "accounts.parent_id", "ach_posting.bounce_date")
            ->orderBy('accounts.path', 'asc')
            ->orderBy('billing.bill_date', 'desc')
            ->paginate(20);

        return view('admin.reports.billing', [
            'bills' => $bills,
            'account_type' => $account_type,
            'account_id' => $account_id,
            'sdate' => $sdate,
            'edate' => $edate,
            'quick' => $request->quick,
            'starting_balance' => $starting_balance,
            'deposit_total' => $deposit_total,
            'sales' => $sales,
            'sales_margin' => $sales_margin,
            'adjustment' => $adjustment,
            'void' => $void,
            'void_margin' => $void_margin,
            'net_revenue' => $net_revenue,
            'bill_amt' => $bill_amt,
            'deposit_paid_amt' => $deposit_paid_amt,
            'ach_paid_amt' => $ach_paid_amt,
            'dist_paid_amt' => $dist_paid_amt,
            'ending_balance' => $ending_balance,
            'ending_deposit' => $ending_deposit,
            'bounce' => $request->bounce
        ]);
    }

    public function detail($id, Request $request) {
        try {

            $bill = Billing::find($id);
            if (empty($bill)) {
                throw new \Exception('Invalid invoice number provided');
            }

            $bill_account = Account::find($bill->account_id);
            if (empty($bill_account)) {
                throw new \Exception('Invalid account ID provided');
            }

            $query = Transaction::join('accounts', 'accounts.id', 'transaction.account_id')
                ->leftJoin('commission', function($join) use ($bill) {
                    $join->on('commission.trans_id', '=', 'transaction.id');
                    $join->on('commission.account_id', '=', DB::raw($bill->account_id));
                })
                ->where('accounts.path', 'like', $bill_account->path . '%')
                ->where('transaction.status', '!=', 'F')
                ->where('transaction.cdate', '>=', Carbon::parse($bill->period_from . ' 00:00:00'))
                ->where('transaction.cdate', '<', Carbon::parse($bill->period_to . ' 23:59:59'))
                ->select(
                                    'transaction.*'
                );
//                ->select(
//                    'transaction.*',
//                    //DB::raw("if('" . $bill_account->type . "' = 'S', transaction.collection_amt, 0) as collection_amt"),
//                    DB::raw("if('" . $bill_account->type . "' = 'S', transaction.fee + transaction.pm_fee, 0) as fee"),
//                    DB::raw("if('" . $bill_account->type . "' = 'S', transaction.fee + transaction.pm_fee, 0) - ifnull(commission.comm_amt, 0) as net_revenue")
//                );

            $collection_amt = $query->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt, -transaction.collection_amt)"));
//            $net_revenue = $query->sum(DB::raw("if(transaction.type = 'S', (if('" . $bill_account->type . "' = 'S', transaction.collection_amt, 0) - ifnull(commission.comm_amt, 0)), -(if('" . $bill_account->type . "' = 'S', transaction.collection_amt, 0) - ifnull(commission.comm_amt, 0)))"));
            $net_revenue = $query->sum(DB::raw("if(transaction.type = 'S', transaction.net_revenue, -transaction.net_revenue)"));

            $fee = $query->sum(DB::raw("if(transaction.type = 'S', if('" . $bill_account->type . "' = 'S', transaction.fee + transaction.pm_fee, 0), -if('" . $bill_account->type . "' = 'S', transaction.fee + transaction.pm_fee, 0))"));

            if ($request->export == 'Y') {
                $data = $query->get();
                Excel::create('transactions', function($excel) use($data) {

                    $excel->sheet('reports', function($sheet) use($data) {

                        $new_data = [];

                        $collection_amt_total = 0;
                        $net_revenue_total = 0;
                        $fee_total = 0;
                        foreach ($data as $o) {
                            if($o->type_name == 'Void'){
                                $collection_amt_total -= $o->collection_amt;
                                $net_revenue_total -= $o->net_revenue;
                                $fee_total -= ($o->fee + $o->pm_fee);
                            }else {
                                $collection_amt_total += $o->collection_amt;
                                $net_revenue_total += $o->net_revenue;
                                $fee_total += ($o->fee + $o->pm_fee);
                            }

                            $row = [
                                'Tx.ID' => $o->id,
                                'Type' => $o->type_name,
                                'Carrier' => $o->carrier(),
                                'Product' => $o->product_name(),
                                'Action' => $o->action,
                                'Phone' => $o->phone,
                                'Denom($)' => $o->denom,
                                'RTR.M' => $o->rtr_month,
                                'Gross($)' => $o->type_name == 'Void' ? '$-' . number_format($o->collection_amt, 2) : number_format($o->collection_amt, 2),
                                'Net($)' => $o->type_name == 'Void' ? '$-' . number_format($o->net_revenue, 2) : number_format($o->net_revenue, 2),
                                'Vendor.Fee($)' => $o->type_name == 'Void' ? '$-' . number_format($o->fee + $o->pm_fee, 2) : number_format($o->fee + $o->pm_fee, 2),
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
                            'Date' => ''
                        ];

                        $sheet->fromArray($new_data);

                    });

                })->export('xlsx');
            }

            $data = $query->paginate(10);

            return view('admin.reports.billing-detail', [
                'bill' => $bill,
                'data' => $data,
                'collection_amt' => $collection_amt,
                'net_revenue' => $net_revenue,
                'fee' => $fee
            ]);


        } catch (\Exception $ex) {
            return back()->withInput()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }
}