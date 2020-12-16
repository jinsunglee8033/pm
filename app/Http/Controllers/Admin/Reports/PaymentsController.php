<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/19/17
 * Time: 7:47 PM
 */

namespace App\Http\Controllers\Admin\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\Payment;
use App\Model\VWPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;


class PaymentsController extends Controller
{

    public function show(Request $request) {
        $sdate = $request->get('sdate', Carbon::today()->startOfWeek()->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }


        $method = $request->get('method');
        $category = $request->get('category');

        $user = Auth::user();

        $query = Payment::where('account_id', $user->account_id);

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($method)) {
            $query = $query->where('method', $method);
        }

        if (!empty($category)) {
            $query = $query->where('category', $category);
        }

        $deposit_amt = $query->sum('deposit_amt');
        $fee = $query->sum('fee');
        $amt = $query->sum('amt');

        $data = $query->orderBy('cdate', 'desc')->paginate();

        $balance = 0;
        switch ($user->account_type) {
            case 'M':
                $balance = PaymentProcessor::get_master_limit($user->account_id);
                break;
            case 'D':
                $balance = PaymentProcessor::get_dist_limit($user->account_id);
                break;
        }

        $account = Account::find($user->account_id);
        $credit_limit = 0;
        if (!empty($account)) {
            $credit_limit = $account->credit_limit;
        }

        return view('admin.reports.payments', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'method' => $method,
            'category' => $category,
            'deposit_amt' => $deposit_amt,
            'fee' => $fee,
            'amt' => $amt,
            'balance' => $balance,
            'credit_limit' => $credit_limit,
            'is_login_as' => Helper::is_login_as() ? 'Y' : 'N'
        ]);
    }

    public function addPayPal(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'deposit_amt' => 'required|numeric',
                'fee' => 'required|numeric',
                'amt' => 'required|numeric',
                'payer_id' => 'required',
                'payment_id' => 'required',
                'payment_token' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $account = Account::find($request->account_id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid account ID provided'
                ]);
            }

            /* TODO: open when we have ACH prepay posting
            ### posting limit check ###
            $this_week_total = Payment::where('account_id', $account->id)
                ->where('cdate', '>=', Carbon::today()->startOfWeek())
                ->sum('amt');

            if ($account->type == 'S' && ($this_week_total + $request->amt) > $account->posting_limit) {
                return response()->json([
                    'msg' => 'You have exceeded the account\'s weekly limit'
                ]);
            }*/

            if ($request->deposit_amt < $request->fee) {
                return response()->json([
                    'msg' => 'Fee amount is greater than deposit amount'
                ]);
            }

            if ($request->amt != ($request->deposit_amt - $request->fee)) {
                return response()->json([
                    'msg' => 'Applied amount calculation is broken'
                ]);
            }

            $payment = new Payment;
            $payment->account_id = $request->account_id;
            $payment->type = 'P'; # prepay always for now.
            $payment->method = 'P';
            $payment->category = '';
            $payment->deposit_amt = $request->deposit_amt;
            $payment->fee = $request->fee;
            $payment->amt = $request->amt;
            $payment->comments = $request->comments;

            $payment->payer_id = $request->payer_id;
            $payment->payment_id = $request->payment_id;
            $payment->payment_token = $request->payment_token;

            $payment->created_by = Auth::user()->user_id;
            $payment->cdate = Carbon::now();
            $payment->save();


            # Send payment success email to balance@softpayplus.com
            $subject = "Success Payment (Acct.ID : " . $payment->account_id . ", Amount : $" . $payment->amt . ")";
            $msg = "<b>Success Payment</b> <br/><br/>";
            $msg .= "Acct.ID - " . $payment->account_id . "<br/>";
            $msg .= "Type - " . $payment->getTypeNameAttribute() . "<br/>";
            $msg .= "Method - " . $payment->getMethodNameAttribute() . "<br/>";
            $msg .= "Deposit.Amt - $" . $payment->deposit_amt . "<br/>";
            $msg .= "Fee - $" . $payment->fee . "<br/>";
            $msg .= "Amount - $" . $payment->amt . "<br/>";
            $msg .= "Comment - " . $payment->comments . "<br/>";
            $msg .= "Payer.ID - " . $payment->payer_id . "<br/>";
            $msg .= "Payment.ID - " . $payment->payment_id . "<br/>";
            $msg .= "Payment.Token - " . $payment->payment_token . "<br/>";
            $msg .= "Created.By - " . $payment->created_by . "<br/>";
            $msg .= "Date - " . $payment->cdate . "<br/>";


            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('balance@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);


            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function root(Request $request) {

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

        $query1 =
            "
            select account_id,
             type,
             method,
             case category when 'Credit' then 'Payment Credit' else category end category,
             deposit_amt,
             fee,
             invoice_number,
             paypal_txn_id, 
             comments,
             ach_posting_id,
             cdate,
             created_by,
             amt
             from vw_payment
            where cdate >= '$sdate'
            and cdate <= '$edate 23:59:59'
        ";

        $query4 = "
        select account_id, 
            'Manual Credit / Debit' as type, 
            'Manual Credit / Debit' as method, 
            Case type When 'C' then 'Manual Credit' else 'Manual Debit' end as category, 
            '-' as deposit_amt, 
            '-' as fee, 
            '-' as invoice_number, 
            '-' as paypal_txn_id,
            comments as comments, 
            '-' as ach_posting_id, 
            convert(cdate, DATETIME) as cdate, 
            '-' as created_by,
            sum(amt) as amt
        from credit
        where type in ('C','D')
        and cdate >= '$sdate'
        and cdate <= '$edate 23:59:59'
        ";

        if (!empty($request->acct_ids)) {
            $request->acct_id = '';
            $acct_ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
            $acct_ids = implode (", ", $acct_ids);
            $query1 .= " and account_id in ($acct_ids) ";
            $query4 .= " and account_id in ($acct_ids) ";
        }

        if (!empty($request->acct_id)) {
            $request->acct_ids = '';
            $query1 .= " and account_id = '$request->acct_id' ";
            $query4 .= " and account_id = '$request->acct_id' ";
        }

        $query4 .= " 
            group by 1,2,3,4,5,6,7,8,9,10,11,12
        ";

        if ($request->method == 'M') {
            $request->paypal_id = '';
            $request->invoice_id = '';
            $query4 .= " order by cdate desc ";
            $query = $query4 ;
        }
        elseif ($request->method == ''){

            if (!empty($request->paypal_id) || !empty($request->invoice_id)){
                if(!empty($request->paypal_id)) {
                    $query1 .= " and upper(paypal_txn_id) like '%" . strtoupper($request->paypal_id) . "%' ";
                }
                if(!empty($request->invoice_id)){
                    $query1 .= " and upper(invoice_number) like '%" . strtoupper($request->invoice_id) . "%' ";
                }
                $query1 .= " order by cdate desc ";
                $query = $query1;
            } else {
                $query = $query1 . " union all " . $query4;
                $query .= " order by cdate desc ";
            }

        }else{
            if (!empty($request->method)) {
                $query1 .= " and method = '$request->method' ";
            }
            if (!empty($request->paypal_id)){
                $query1 .= " and upper(paypal_txn_id) like '%" . strtoupper($request->paypal_id) . "%' ";
            }
            if(!empty($request->invoice_id)){
                $query1 .= " and upper(invoice_number) like '%" . strtoupper($request->invoice_id) . "%' ";
            }
            $query1 .= " order by cdate desc ";
            $query = $query1;
        }

        if ($request->excel == 'Y') {
            $payments = DB::select($query);
            Excel::create('Payments', function($excel) use($payments) {
                ini_set('memory_limit', '2048M');
                $excel->sheet('reports', function($sheet) use($payments) {
                    $data = [];
                    foreach ($payments as $o) {
                        $row = [
                            'Create.At' => $o->cdate,
                            'Account' => $o->account_id,
                            'Type' => $o->type == 'P' ? 'Prepay'
                                : ($o->type == 'B' ? 'Weekly Billing'
                                : ($o->type == 'A' ? 'Post Pay'
                                : ($o->type == 'W' ? 'Weekday ACH'
                                : $o->type ))),

                            'Method' =>  $o->method == 'P' ? 'PayPal'
                                        : ($o->method == 'D' ? 'Direct Deposit'
                                        : ($o->method == 'C' ? 'Credit Card'
                                        : ($o->method == 'A' ? 'ACH'
                                        : ($o->method == 'B' ? 'Weekly Bill'
                                        : ($o->method == 'H' ? 'Cash Pickup'
                                        : $o->method ))))),
                            'Deposit.Amt' => $o->deposit_amt,
                            'Fee' => $o->fee,
                            'Applied.Amt' => $o->amt,
                            'Paypal.Id' => $o->paypal_txn_id,
                            'Invoice.Id' => $o->invoice_number,
                            'Comments' => $o->comments,
                            'Created.By' => $o->created_by
                        ];
                        $data[] = $row;
                    }
                    $sheet->fromArray($data);
                });
            })->export('xlsx');
        }

        $data = DB::select($query);

        if(!empty($data)) {
            $amt = 0;
            $fee = 0;
            $deposit_amt = 0;
            foreach ($data as $d) {
                $amt += $d->amt;
                if($d->fee != '-'){
                    $fee += $d->fee;
                }
                if($d->deposit_amt != '-'){
                    $deposit_amt += $d->deposit_amt;
                }
            }
        }

        $data = Helper::arrayPaginator_20($data, $request);

        return view('admin.reports.root-payments', [
            'data'      => $data,
            'sdate'     => $sdate,
            'edate'     => $edate,
            'quick'     => $request->quick,
            'method'    => $request->method,
            'category'  => $request->category,
            'acct_ids'  => $request->acct_ids,
            'deposit_amt'   => isset($deposit_amt) ? $deposit_amt : 0,
            'fee'       => isset($fee) ? $fee : 0,
            'amt'       => isset($amt) ? $amt : 0,
            'acct_id'   => $request->acct_id,
            'paypal_id' => $request->paypal_id,
            'invoice_id'    => $request->invoice_id
        ]);
    }

}