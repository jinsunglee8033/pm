<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/28/17
 * Time: 8:15 PM
 */

namespace App\Http\Controllers\Admin\Reports;


use App\Http\Controllers\Controller;
use App\Lib\PaymentProcessor;
use App\Model\Account;
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

        switch (Auth::user()->account_type) {
            case 'L':
                $bill = PaymentProcessor::get_root_balance(
                    Auth::user()->account_id,
                    $bill_date,
                    $period_from,
                    $period_to
                );
                break;
            case 'M':
                $bill = PaymentProcessor::get_master_balance(
                    Auth::user()->account_id,
                    $bill_date,
                    $period_from,
                    $period_to
                );
                break;
            case 'D':
                $bill = PaymentProcessor::get_distributor_balance(
                    Auth::user()->account_id,
                    $bill_date,
                    $period_from,
                    $period_to
                );
                break;
            default:
                return redirect('/admin/error')->with([
                    'error_msg' => 'You are not authorized to view this page.'
                ]);
        }

        $bill['bill_date'] = $bill_date->format('Y-m-d');
        $bill['period_from'] = $period_from->format('Y-m-d');
        $bill['period_to'] = $period_to->format('Y-m-d');

        $bill['extra'] = $bill['spiff_credit'] - $bill['spiff_debit'] + $bill['rebate_credit'] - $bill['rebate_debit'] + $bill['residual'] + $bill['adjustment'] + $bill['promotion'];
        $bill['payable'] = $bill['starting_balance'] + $bill['net_revenue'] + $bill['fee'] + $bill['pm_fee'] + $bill['consignment'];
        $bill['paid_total'] = $bill['deposit_paid_amt'] + $bill['ach_paid_amt'] + $bill['dist_paid_amt'];

        $login_account = Account::find(Auth::user()->account_id);

        $query = Transaction::leftJoin('vw_spiff_trans', function($join) use($login_account) {
                $join->on('vw_spiff_trans.trans_id', 'transaction.id');
                $join->on('vw_spiff_trans.account_id', '=', DB::raw($login_account->id));
                $join->where('vw_spiff_trans.spiff_month', 1);
            })->leftJoin('rebate_trans', function($join) use ($login_account) {
                $join->on('rebate_trans.trans_id', 'transaction.id');
                $join->on('rebate_trans.account_id', '=', DB::raw($login_account->id));
                $join->where('rebate_trans.rebate_month', 1);
            })
            ->leftJoin('commission', function($join) use ($login_account) {
                $join->on('commission.trans_id', '=', 'transaction.id');
                $join->on('commission.account_id', '=', DB::raw($login_account->id));
            })
            ->join('accounts', 'accounts.id', 'transaction.account_id')
            ->where('accounts.path', 'like', $login_account->path . '%')
            ->where('transaction.status', '!=', 'F')
            ->where('transaction.cdate', '>=', $period_from)
            ->where('transaction.cdate', '<', $period_to->copy()->addDay());

        if ($request->export == 'Y') {
            $data = $query->select(
                'transaction.*',
                'vw_spiff_trans.spiff_amt',
                'rebate_trans.rebate_amt',
               // DB::raw("if('" . $login_account->type . "' = 'S', transaction.fee , 0) as fee2"),
                //DB::raw("if('" . $login_account->type . "' = 'S', transaction.pm_fee , 0) as pm_fee2"),
                DB::raw(" case '" . $login_account->type . "' when 'S' then if(transaction.type = 'S', transaction.fee , -(transaction.fee)) " .
                    "                                     when 'L' then if(transaction.type = 'S', transaction.fee , -(transaction.fee)) " .
                    "                                     else 0 end  as fee2" ),
                DB::raw(" case '" . $login_account->type . "' when 'S' then if(transaction.type = 'S', transaction.pm_fee , -(transaction.pm_fee)) " .
                    "                                         when 'L' then if(transaction.type = 'S', transaction.pm_fee , -(transaction.pm_fee)) " .
                    "                                         else 0 end  as pm_fee2" ),

                DB::raw( "if(transaction.type = 'S', ifnull(commission.comm_amt, 0),  -ifnull(commission.comm_amt, 0))  as net_revenue2") //Net is net, do not consider fee
            )->orderBy('transaction.cdate', 'desc')->get();
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
                        $net_revenue_total += $o->net_revenue2;
                        $fee_total += ($o->fee2 + $o->pm_fee2);
                        $spiff_total += $o->spiff_amt;
                        $rebate_total += $o->rebate_amt;

                        $row = [
                            'Tx.ID' => $o->id,
                            'Type' => $o->type_name,
                            'Carrier' => $o->carrier(),
                            'Product' => $o->product_name(),
                            'Action' => $o->action,
                            'SIM.Type' => $o->sim_type_name,
                            'Phone' => $o->action == 'PIN' ? $o->pin : $o->phone,
                            'Denom($)' => $o->denom,
                            'RTR.M' => $o->rtr_month,
                            'Gross($)' => '$' . number_format($o->collection_amt, 2),
                            'Net.($)' => '$' . number_format($o->net_revenue2, 2),
                            'Vendor.Fee($)' => '$' . number_format($o->fee2 + $o->pm_fee2, 2),
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
                        'SIM.Type' => '',
                        'Phone' => '',
                        'Denom($)' => '',
                        'RTR.M' => 'Total:',
                        'Gross($)' => '$' . number_format($collection_amt_total, 2),
                        'Net($)' => '$' . number_format($net_revenue_total,2),
                        'Vendor.Fee($)' => '$' . number_format($fee_total, 2),
                        'Spiff($)' => '$' . number_format($spiff_total, 2),
                        'Rebate($)' => '$' . number_format($rebate_total, 2),
                        'Date' => ''
                    ];

                    $sheet->fromArray($new_data);

                });

            })->export('xlsx');
        }


        $collection_amt = $query->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt, transaction.collection_amt)")); //Gross
        //$net_revenue = $query->sum(DB::raw("if(transaction.type = 'S', transaction.net_revenue, -transaction.net_revenue)"));
        $net_revenue = $query->sum(DB::raw("if(transaction.type = 'S', (if('" . $login_account->type . "' = 'S' , transaction.collection_amt, 0) - ifnull(commission.comm_amt, 0)),
                                                                      -(if('" . $login_account->type . "' = 'S' , transaction.collection_amt, 0) - ifnull(commission.comm_amt, 0)))"));
        $fee = $query->sum(DB::raw("if('" . $login_account->type ."' in ( 'S','L') , if(transaction.type = 'S', transaction.fee + transaction.pm_fee, -(transaction.fee + transaction.pm_fee)), 0)"));
        $spiff = $query->sum(DB::raw("if(transaction.type = 'S', vw_spiff_trans.spiff_amt, -vw_spiff_trans.spiff_amt)"));
        $rebate = $query->sum(DB::raw("if(transaction.type = 'S', rebate_trans.rebate_amt, -rebate_trans.rebate_amt)"));

        $data = $query->select(
            'transaction.*',
            'vw_spiff_trans.spiff_amt',
            'rebate_trans.rebate_amt',
            DB::raw(" case '" . $login_account->type . "' when 'S' then if(transaction.type = 'S', transaction.fee , -(transaction.fee)) " .
                    "                                     when 'L' then if(transaction.type = 'S', transaction.fee , -(transaction.fee)) " .
                    "                                     else 0 end  as fee2" ),
            DB::raw(" case '" . $login_account->type . "' when 'S' then if(transaction.type = 'S', transaction.pm_fee , -(transaction.pm_fee)) " .
                "                                         when 'L' then if(transaction.type = 'S', transaction.pm_fee , -(transaction.pm_fee)) " .
                "                                         else 0 end  as pm_fee2" ),
            //DB::raw("if('" . $login_account->type . "' = 'S', if(transaction.type = 'S', transaction.pm_fee , -(transaction.pm_fee)), 0) as pm_fee2"),
            //DB::raw("if('" . $login_account->type . "' = 'S', if(transaction.type = 'S', transaction.fee + transaction.pm_fee - ifnull(commission.comm_amt, 0), -(transaction.fee + transaction.pm_fee - ifnull(commission.comm_amt, 0))), 0) as net_revenue")
            //DB::raw("if('" . $login_account->type . "' = 'S', if(transaction.type = 'S', ifnull(commission.comm_amt, 0), -(ifnull(commission.comm_amt, 0))), 0) as net_revenue2")
            DB::raw( "if(transaction.type = 'S', ifnull(commission.comm_amt, 0),  -ifnull(commission.comm_amt, 0))  as net_revenue2") //Net is net, do not consider fee
        )->orderBy('transaction.cdate', 'desc')->paginate(10);
        //$data = $query->select('transaction.*', 'spiff_trans.spiff_amt', 'rebate_trans.rebate_amt')->paginate(10);

        return view('admin.reports.activity', [
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