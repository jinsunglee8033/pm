<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/14/17
 * Time: 3:25 PM
 */

namespace App\Http\Controllers\Admin\Account;


use App\Http\Controllers\Controller;
use App\Lib\PaymentProcessor;
use App\Lib\Permission;
use App\Model\Account;
use App\Model\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Lib\Helper;


class PaymentController extends Controller
{

    public function add(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'method' => 'required',
                'category' => 'required_if:method,D',
                'deposit_amt' => 'required|numeric',
                'fee' => 'required_if:method,D|numeric',
                'amt' => 'required|numeric|min:100',
                'comments' => 'required'
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

            ### posting limit check ###
            $this_week_total = Payment::where('account_id', $account->id)
                ->where('cdate', '>=', Carbon::today()->startOfWeek())
                ->sum('amt');

            /*
             * TODO: open when we have ACH prepay posting
            if ($account->type == 'S' && ($this_week_total + $request->amt) > $account->posting_limit) {
                return response()->json([
                    'msg' => 'You have exceeded the account\'s weekly limit'
                ]);
            }*/

            ### Allow cash limit check###
            if($request->method == 'H') {

                ### check Sub-Agent limit check###
                if($account->type == 'S' && ($this_week_total + $request->amt) >$account->allow_cash_limit){
                    return response()->json([
                        'msg' => 'You have exceeded the Sub Agent\'s Allowed cash limit'
                    ]);
                }

                ### check Distributor limit check###
                $current_type = Auth::user()->account_type;
                if($current_type == 'D'){ // D -> S
                    $current_id = Auth::user()->account_id;
                    $this_week_distributor_total = Payment::where('collection_id', $current_id)
                        ->where('cdate', '>=', Carbon::today()->startOfWeek())
                        ->sum('amt');
                    $distributor_info = Account::find($current_id);
                    if($this_week_distributor_total + $request->amt > $distributor_info->allow_cash_limit){
                        return response()->json([
                            'msg' => 'You have exceeded the Distributor\'s Allowed cash limit'
                        ]);
                    }
                }
                ### check Master limit check###
                if($current_type == 'M'){ // M->D->S or M->S
                    if($account->master_id != $account->parent_id){ // M->D->S
                        $this_week_distributor_total = Payment::where('collection_id', $account->parent_id)
                            ->where('cdate', '>=', Carbon::today()->startOfWeek())
                            ->sum('amt');
                        $distributor_info = Account::find($account->parent_id);
                        if($this_week_distributor_total + $request->amt > $distributor_info->allow_cash_limit){
                            return response()->json([
                                'msg' => 'You have exceeded the Distributor\'s Allowed cash limit'
                            ]);
                        }
                    }
                    $current_id = Auth::user()->account_id; // M->S
                    $this_week_master_total = Payment::where('collection_id', $current_id)
                        ->where('cdate', '>=', Carbon::today()->startOfWeek())
                        ->sum('amt');
                    $master_info = Account::find($current_id);
                    if($this_week_master_total + $request->amt > $master_info->allow_cash_limit){
                        return response()->json([
                            'msg' => 'You have exceeded the Master\'s Allowed cash limit'
                        ]);
                    }
                }
            }

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
            $payment->method = $request->get('method');
            if($payment->method == 'H'){
                $payment->category = 'Cash Pickup';
                $payment->collection_id = $current_id;
            }else{
                $payment->category = $request->category;
            }
            $payment->deposit_amt = $request->deposit_amt;
            $payment->fee = $request->fee;
            $payment->amt = $request->amt;
            $payment->comments = $request->comments;
            $payment->created_by = Auth::user()->user_id;
            $payment->cdate = Carbon::now();
            $payment->save();


            # Send payment success email to payment@softpayplus.com
            $subject = "Success Payment (Acct.ID : " . $payment->account_id . ", Amount : $" . $payment->amt . ")";
            $msg = "<b>Success Payment</b> <br/><br/>";
            $msg .= "Acct.ID - " . $payment->account_id . "<br/>";
            $msg .= "Type - " . $payment->getTypeNameAttribute() . "<br/>";
            $msg .= "Method - " . $payment->getMethodNameAttribute() . "<br/>";
            $msg .= "Category - " . $payment->category . "<br/>";
            $msg .= "Deposit.Amt - $" . $payment->deposit_amt . "<br/>";
            $msg .= "Fee - $" . $payment->fee . "<br/>";
            $msg .= "Amount - $" . $payment->amt . "<br/>";
            $msg .= "Comment - " . $payment->comments . "<br/>";
            $msg .= "Created.By - " . $payment->created_by . "<br/>";
            $msg .= "Date - " . $payment->cdate . "<br/>";


            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('payment@softpayplus.com', $subject, $msg);
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

    public function getList(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'sdate' => 'required|date',
                'edate' => 'required|date',
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

            $payments = Payment::where('account_id', $request->account_id)
                ->where('cdate', '>=', Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00'))
                ->where('cdate', '<', Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 00:00:00')->addDay());

            if (!empty($request->type)) {
                $payments = $payments->where('type', $request->type);
            }

            if (!empty($request->get('method'))) {
                $payments = $payments->where('method', $request->get('method'));
            }

            if (!empty($request->get('comments'))) {
                $payments = $payments->where(DB::raw("lower(comments)"), 'like',  '%' . strtolower($request->get('comments')) . '%' );
            }

            if (!empty($request->get('paypal_id'))) {
                $payments = $payments->where(DB::raw("lower(paypal_txn_id)"), 'like',  '%' . strtolower($request->get('paypal_id')) . '%' );
            }

            if (!empty($request->get('invoice_id'))) {
                $payments = $payments->where(DB::raw("lower(invoice_number)"), 'like',  '%' . strtolower($request->get('invoice_id')) . '%' );
            }

            $payments = $payments->orderBy('cdate', 'desc')->get();

            if (empty($payments)) {
                $payments = [];
            }

            switch ($account->type) {
                case 'M':
                    $balance = PaymentProcessor::get_master_limit($account->id);
                    break;
                case 'D':
                    $balance = PaymentProcessor::get_dist_limit($account->id);
                    break;
                case 'S':
                    $balance = PaymentProcessor::get_limit($account->id);
                    break;
                default:
                    $balance = 0;
                    break;
            }

            return response()->json([
                'msg' => '',
                'payments' => $payments,
                'balance' => $balance
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

    public function test(){

    }

}