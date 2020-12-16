<?php

namespace App\Http\Controllers\SubAgent\Activate;

use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\PaymentProcessor;
use App\Lib\SpiffProcessor;
use App\Model\VendorDenom;
use Illuminate\Http\Request;
use App\Model\Product;
use App\Model\Denom;
use App\Model\State;
use App\Model\Transaction;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use App\Lib\Helper;
use Log;
use App\Events\TransactionStatusUpdatedRoot;

/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/27/17
 * Time: 8:47 AM
 */
class VerizonController extends Controller
{

    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_verizon != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Verizon activation. Please contact your distributor'
            ]);
        }

        $products = Product::where('carrier', 'Verizon')
            ->where('status', 'A')
            ->where('activation', 'Y')
            ->get();

        $denom = Denom::find($request->denom_id);
        if (empty($denom)) {
            //$denom = $products[0]->denominations[0];
        }

        $rtr_month = 1;

        $states = State::all();

        $amt = isset($denom) ? $denom->denom : 0;
        $sub_total = $amt * $rtr_month;

        $fee = 0;

        if (!empty($denom)) {
            $vendor_denom = VendorDenom::where('product_id', $denom->product_id)
                ->where('vendor_code', 'VZN')
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            if (!empty($vendor_denom)) {
                $fee = ($vendor_denom->fee + $vendor_denom->pm_fee) * $rtr_month;
            }
        }

        $total = $sub_total + $fee;

        # get transaction list : only 3
        $transactions = Transaction::join('product', 'transaction.product_id', 'product.id')
            ->join('accounts', 'transaction.account_id', 'accounts.id')
            ->where('transaction.account_id', Auth::user()->account_id)
            ->where('product.carrier', 'Verizon')
            ->selectRaw('transaction.*, accounts.name as account_name')
            ->orderBy('transaction.cdate', 'desc')
            ->skip(0)->take(3)
            ->get();


        return view('sub-agent.activate.verizon', [
            'transactions' => $transactions,
            'products' => $products,
            'states' => $states,
            'denom' => $denom,
            'denom_id' => isset($denom->id) ? $denom->id : null,
            'rtr_month' => $rtr_month,
            'amt' => $amt,
            'sub_total' => $sub_total,
            'fee' => $fee,
            'total' => $total
        ]);
    }

    public function post(Request $request) {
        try {

            if (Helper::is_login_as()) {
                return back()->withInput()->withErrors([
                    'exception' => 'We are sorry. Login as user is not allowed to make any transaction'
                ]);
            }

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'sim' => 'nullable|regex:/^\d{10,30}$/',
                //'esn' => 'nullable|regex:/^[A-F]{1}[A-Z0-9]{13}$//',
                'npa' => 'required_if:port_in,N',
                'number_to_port' => 'required_if:port_in,Y',
                'account_no' => 'required_if:port_in,Y',
                'account_pin' => 'required_if:port_in,Y',
                'call_back_phone' => 'nullable|regex:/\d{10}$/',
                'email' => 'nullable|email'
            ], [
                'number_to_port.required_if' => 'Port-in number is required',
                'account_no.required_if' => 'Account # is required',
                'account_pin.required_if' => 'Account PIN is required',
                'npa.required' => 'Pref.Area Code is required',
                'denom_id.required' => 'Please select product'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)
                    ->withInput();
            }

            $pattern = '/^\d{10}$/';
            if ($request->port_in == 'Y' && !preg_match($pattern, $request->number_to_port)) {
                return back()->withErrors([
                    'number_to_port' => 'Please enter phone # to port-in'
                ])->withInput();
            }

            $pattern = '/\d{3}$/';
            if ($request->port_in == 'N' && !preg_match($pattern, $request->npa)) {
                return back()->withErrors([
                    'npa' => 'Please enter valid 3 digit preferred area code'
                ])->withInput();
            }

            if (empty($request->sim) && empty($request->esn)) {
                return back()->withErrors([
                    'esn' => 'Either SIM or ESN/IME should be provided'
                ])->withInput();
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return back()->withErrors([
                    'denom_id' => 'Invalid denomination provided'
                ])->withInput();
            }

            $product = Product::find($denom->product_id);
            if (empty($product)) {
                return back()->withErrors([
                    'denom_id' => 'Invalid product provided'
                ])->withInput();
            }

            $user = Auth::user();

            ### fee ###
            $fee = 0;
            $pm_fee = 0;

            ### get collection amount ###
            $collection_amt =  0;

            ### check sales limit ###
            $net_revenue = 0;
            if ($collection_amt > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $collection_amt, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return back()->withErrors([
                        'exception' => $ret['error_msg']
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            ### TODO: cost ? ###

            $trans = new Transaction;
            $trans->account_id = $user->account_id;
            $trans->product_id = $denom->product_id;
            $trans->action = $request->port_in == 'Y' ? 'Port-In' : 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn . $request->esn_16;
            $trans->npa = $request->npa;
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->address1 = $request->address1;
            $trans->address2 = $request->address2;
            $trans->city = $request->city;
            $trans->state = $request->state;
            $trans->zip = $request->zip;
            $trans->phone = $request->number_to_port;
            $trans->current_carrier = $request->current_carrier;
            $trans->account_no = $request->account_no;
            $trans->account_pin = $request->account_pin;
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;
            $trans->pref_pin = $request->pref_pin;
            $trans->status = $request->status;
            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'N';
            $trans->note = $request->note;

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = 1;
            $trans->net_revenue = $net_revenue;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            ### commission ###
            # - no commission for activation 09/25/2017
            /*
            $ret = CommissionProcessor::create($trans->id);
            if (!empty($ret['error_msg'])) {
                ### send message only ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Failed to create commission', $msg);
            }
            */

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            if ($collection_amt > 0) {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by);
                if (!empty($ret['error_code'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }
            }

            $ret = Helper::send_mail(env('ACT_NOTIFY_EMAIL'), '[Verizon][' . getenv('APP_ENV'). '] New Activation Request', ' - Tx.ID: ' . $trans->id);
            if (!empty($ret)) {
                Helper::log('### SEND MAIL ERROR ###', [
                    'msg' => $ret
                ]);
            }

            $msg = "We have new Verizon activation request. Click <a style='color:yellow;' href='/admin/reports/transaction?id=" . $trans->id . "'>here</a> to see detail info!";
            event(new TransactionStatusUpdatedRoot($trans, $msg));

            ### update balance ###
            Helper::update_balance();

            return back()->with([
                'success' => 'Y'
            ]);


        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

}