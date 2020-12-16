<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/19/17
 * Time: 7:47 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\Payment;
use App\Model\PaymentRequest;
use App\Model\VWPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaymentsController extends Controller
{

    public function show(Request $request)
    {
        $sdate = $request->get('sdate', Carbon::today()->subDays(30)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->subDays(30)->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->format('Y-m-d');
        }

        $method = $request->get('method');
        $category = $request->get('category');

        $user = Auth::user();

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
            and account_id = $user->account_id
        ";

        $query2 = "
        select account_id,
                'Void' as type,
                'Void' as method,
                'Void' as category,
                '-' as deposit_amt,
                '-' as fee,
                '-' as invoice_number,
                '-' as paypal_txn_id,
                '-' as comments,
                '-' as ach_posting_id,
                cdate as cdate,
                '-' as created_by,
                fee + pm_fee + net_revenue as amt
        from transaction 
        where cdate >= '$sdate'
        and cdate <= '$edate 23:59:59'
        and account_id = $user->account_id
        and type = 'V'
        ";

        $query3 = "
        select 
            account_id, 
            'Commission' as type, 
            'Commission' as method, 
            'Spiff' as category, 
            '-' as deposit_amt, 
            '-' as fee, 
            '-' as invoice_number, 
            '-' as paypal_txn_id,
            '-' as comments, 
            '-' as ach_posting_id, 
            convert(cdate, DATE) as cdate, 
            '-' as created_by,
            sum( case type when 'S' then spiff_amt else -spiff_amt end ) as amt
        from spiff_trans 
        where cdate >= '$sdate'
        and cdate <= '$edate 23:59:59'
        and spiff_month != 1
        and account_id = $user->account_id
        group by 1,2,3,4,5,6,7,8,9,10,11,12
        
        union all
        
        select account_id, 
            'Commission' as type, 
            'Commission' as method, 
            'Reidual' as category, 
            '-' as deposit_amt, 
            '-' as fee,
            '-' as invoice_number, 
            '-' as paypal_txn_id,
            '-' as comments, 
            '-' as ach_posting_id, 
            convert(cdate, DATE) as cdate, 
            '-' as created_by,
            sum(amt) as amt  
        from residual 
        where cdate >= '$sdate'
        and cdate <= '$edate 23:59:59'
        and account_id = $user->account_id
        group by 1,2,3,4,5,6,7,8,9,10,11,12
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
            convert(cdate, DATE) as cdate, 
            '-' as created_by,
            sum(amt) as amt
        from credit
        where type in ('C','D')
        and cdate >= '$sdate'
        and cdate <= '$edate 23:59:59'
        and account_id = $user->account_id
        group by 1,2,3,4,5,6,7,8,9,10,11,12
        ";

        if ($method == 'V') {
            $query2 .= " order by cdate desc ";
            $query = $query2;

        } elseif ($method == 'C') {
            $query3 .= " order by cdate desc ";
            $query = $query3 ;

        } elseif ($method == 'M') {
            $query4 .= " order by cdate desc ";
            $query = $query4 ;
        }
        elseif ($method == ''){
            if (!empty($method)) {
                $query1 .= " and method = '$method' ";
            }

            if (!empty($category)) {
                $query1 .= " and category = '$category' ";
            }
            $query = $query1 . " union all " . $query2 . " union all " . $query3 . " union all " . $query4;
            $query .= " order by cdate desc ";

        }else{
            $query1 .= " order by cdate desc ";
            $query = $query1;
        }

        $data = DB::select($query);
        $data = Helper::arrayPaginator_20($data, $request);

//        $deposit_amt = $query1->sum('deposit_amt');
//        $fee = $query1->sum('fee');
//        $amt = $query1->sum('amt');

        $balance = PaymentProcessor::get_limit($user->account_id);

        $account = Account::find($user->account_id);
        $credit_limit = 0;
        if (!empty($account)) {
            $credit_limit = $account->credit_limit;
        }

        return view('sub-agent.reports.payments', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'method' => $method,
            'category' => $category,
//            'deposit_amt' => 0,
//            'fee' => 0,
//            'amt' => 0,
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
                $msg = 'Fee amount is greater than deposit amount. ' . $request->amt . '/' . $request->fee;

                Helper::log('##### PAYMENT ERROR ####', $msg);
                return response()->json([
                    'msg' => $msg
                ]);
            }

            if (round($request->amt,2) != round($request->deposit_amt - $request->fee, 2)) {
                $msg = 'Applied amount calculation is broken. ' . $request->amt . '/' . ($request->deposit_amt - $request->fee);

                Helper::log('##### PAYMENT ERROR ####', $msg);
                return response()->json([
                  'msg' => $msg
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
            $payment->invoice_number = $request->invoice_number;
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

            ### update balance ###
            Helper::update_balance();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            Helper::log('#### EXCEPTION ####', $ex->getTraceAsString());

            return response()->json([
                'msg' => 'We\'re sorry, but please be sure to contact our Customer care team with your payment.'
            ]);
        }
    }

    public function addPayPalPreSave(Request $request) {
        try {

            $payment_req = new PaymentRequest;
            $payment_req->account_id = $request->account_id;
            $payment_req->deposit       = $request->deposit_amt;
            $payment_req->amt           = $request->amt;
            $payment_req->fee           = $request->fee;
            $payment_req->comments      = $request->comments;
            $payment_req->created_by = Auth::user()->user_id;
            $payment_req->cdate = Carbon::now();
            $payment_req->save();

            return response()->json([
                'msg'   => '',
                'pr_id' => $payment_req->id
            ]);

        } catch (\Exception $ex) {
            Helper::log('#### EXCEPTION ####', $ex->getTraceAsString());

            return response()->json([
                'msg' => 'We\'re sorry, but please be sure to contact our Customer care team with your payment.'
            ]);
        }
    }

}