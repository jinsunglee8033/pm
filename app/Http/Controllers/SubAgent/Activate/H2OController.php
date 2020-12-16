<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/27/17
 * Time: 11:21 AM
 */

namespace App\Http\Controllers\SubAgent\Activate;

use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\PaymentProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\telestar;
use App\Model\Payment;
use App\Model\VendorDenom;
use Illuminate\Http\Request;
use App\Model\Product;
use App\Model\Denom;
use App\Model\State;
use App\Model\Transaction;
use App\Model\Account;
use App\Model\H2OSim;
use App\Model\RTRQueue;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use App\Lib\Helper;
use App\Events\TransactionStatusUpdatedRoot;
use App\Lib\h2o;
use App\Jobs\ProcessRTR;

class H2OController extends Controller
{
    public function show(Request $request) {
        return view('sub-agent.error')->with([
          'title' => 'Coming soon...',
          'error_msg' => ''
        ]);

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_h2o != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do H2O activation. Please contact your distributor'
            ]);
        }

        $products = Product::where('carrier', 'H2O')
            ->where('status', 'A')
            ->where('id', '!=', 'WMLL')
            ->get();

        $denom = null;
        $lock_product = 'Y';

        $rtr_month = 1;
        if (!empty($request->sim)) {

            $sim = H2OSim::find($request->sim);
            if (empty($sim)) {
                return back()->withErrors([
                    'sim' => 'Invalid SIM. H2O activation requires SIM # to be registered on our system.'
                ])->withInput();
            }

            if (!empty($sim->used_trans_id)) {
                return back()->withErrors([
                    'sim' => 'SIM already used for H2O activation transaction'
                ])->withInput();
            }

            if ($sim->status != 'A') {
                return back()->withErrors([
                    'sim' => 'SIM status is not active'
                ])->withInput();
            }

            $rtr_month = $sim->rtr_month;

            $vendor_denom = VendorDenom::where('vendor_code', 'LOC')
                ->where('act_pid', $sim->vendor_pid)
                ->where('denom', $sim->amount)
                ->where('status', 'A')
                ->first();

            if (empty($vendor_denom)) {
                return back()->withErrors([
                    'sim' => 'Unable to find proper denomination with SIM #'
                ])->withInput();
            }

            $denom = Denom::where('product_id', $vendor_denom->product_id)
                ->where('denom', $sim->amount)
                ->first();

            if (empty($denom)) {
                return back()->withErrors([
                    'sim' => 'Unable to find proper denomination with SIM #'
                ])->withInput();
            }

        }

        $states = State::all();

        $amt = isset($denom) ? $denom->denom : 0;
        $sub_total = $amt * $rtr_month;

        $fee = 0;

        if (!empty($denom)) {
            $vendor_denom = VendorDenom::where('product_id', $denom->product_id)
                ->where('vendor_code', 'LOC')
                ->where('denom_id', $denom->id )
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
            ->where('transaction.action', '!=', 'RTR')
            ->where('product.carrier', 'H2O')
            ->selectRaw('transaction.*, accounts.name as account_name')
            ->orderBy('transaction.cdate', 'desc')
            ->skip(0)->take(3)
            ->get();

        if (isset($sim) && $sim->type != 'R') {
            $sub_total = 0;
            $fee = 0;
            $total = 0;
        }

        return view('sub-agent.activate.h2o', [
            'transactions' => $transactions,
            'products' => $products,
            'states' => $states,
            'denom' => $denom,
            'denom_id' => isset($denom->id) ? $denom->id : null,
            'lock_product' => $lock_product,
            'sim' => $request->sim,
            'esn' => $request->esn,
            'port_in' => $request->port_in,
            'rtr_month' => $rtr_month,
            'amt' => $amt,
            'sub_total' => $sub_total,
            'fee' => $fee,
            'total' => $total
        ]);
    }

    public function post(Request $request) {
        try {

            /*
            if (getenv('APP_ENV') == 'production') {
                return back()->withInput()->withErrors([
                    'exception' => 'We are sorry. System is under maintenance for H2O services. Please contact us at 703-256-3456.'
                ]);
            }
            */

            if (Helper::is_login_as()) {
                return back()->withInput()->withErrors([
                    'exception' => 'We are sorry. Login as user is not allowed to make any transaction'
                ]);
            }



            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'sim' => 'required|regex:/^\d{10,30}$/',
                //'esn' => 'required_if:port_in,Y',
                'npa' => 'required_if:port_in,N',
                'zip' => 'required|regex:/^\d{5}$/',
                'number_to_port' => 'required_if:port_in,Y',
                'current_carrier' => 'required_if:port_in,Y',
                'account_no' => 'required_if:port_in,Y',
                'account_pin' => 'required_if:port_in,Y',
                'call_back_phone' => 'required_if:port_in,Y',
                'email' => 'required_if:port_in,Y',
                'first_name' => 'required_if:port_in,Y',
                'last_name' => 'required_if:port_in,Y',
                'address1' => 'required_if:port_in,Y',
                'city' => 'required_if:port_in,Y',
                'state' => 'required_if:port_in,Y',
                'carrier_contract' => 'required|in:Y,N'
            ], [
                'number_to_port.required_if' => 'Port-in number is required',
                'current_carrier.required_if' => 'Port-in from is required',
                'account_no.required_if' => 'Account # is required',
                'account_pin.required_if' => 'Account PIN is required',
                'call_back_phone.required_if' => 'Call back phone # is required',
                'npa.required_if' => 'Pref.Area Code is required',
                'denom_id.required' => 'Please select product',
                'first_name.required_if' => 'First name is required',
                'last_name.required_if' => 'Last name is required',
                'address1.required_if' => 'Address1 is required',
                'city.required_if' => 'City is required',
                'state.required_if' => 'State is required',
                'email.required_if' => 'Email is required'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)
                    ->withInput();
            }

            $pattern = '/^\d{10}$/';
            if ($request->port_in == 'Y' && !preg_match($pattern, $request->number_to_port)) {
                return back()->withErrors([
                    'number_to_port' => 'Please enter valid phone # to port-in'
                ])->withInput();
            }

            $pattern = '/^\d{10}$/';
            if ($request->port_in == 'Y' && !preg_match($pattern, $request->call_back_phone)) {
                return back()->withErrors([
                    'number_to_port' => 'Please enter valid call back phone #'
                ])->withInput();
            }

            $pattern = '/\d{3}$/';
            if ($request->port_in != 'Y' && !preg_match($pattern, $request->npa)) {
                return back()->withErrors([
                    'npa' => 'Please enter valid 3 digit preferred area code'
                ])->withInput();
            }

            $pattern = '/\d{5}$/';
            if ($request->port_in != 'Y' && !preg_match($pattern, $request->zip)) {
                return back()->withErrors([
                    'zip' => 'Please enter valid 5 digit zip code. Zip code is required for H2O activation'
                ])->withInput();
            }

            if ($request->port_in == 'Y') {
                $old_request = Transaction::join('product', 'product.id', '=', 'transaction.product_id')
                    ->where('product.carrier', 'H2O')
                    ->where('transaction.action', 'Port-In')
                    ->where('transaction.phone', $request->number_to_port)
                    ->where('transaction.cdate', '>=', Carbon::now()->subHours(24))
                    ->where('transaction.status', '!=', 'F')
                    ->first();
                if (!empty($old_request)) {
                    return back()->withErrors([
                        'number_to_port' => 'You have port-in request with same number within 24 hours. Please wait, it may take up to 48 hours.'
                    ])->withInput();
                }
            }

            ### For H2O, only SIM is required ###
            /*if (empty($request->sim) && empty($request->esn)) {
                return back()->withErrors([
                    'esn' => 'Either SIM or ESN/IME should be provided'
                ])->withInput();
            }*/

            $sim = H2OSim::find($request->sim);
            if (empty($sim) || $sim->status != 'A') {
                return back()->withErrors([
                    'zip' => 'Invalid SIM provided'
                ])->withInput();
            }

            /*if ($sim->type == 'R' && $sim->rtr_month != 1) {
                return back()->withErrors([
                    'sim' => 'Regular SIM RTR.Month should be 1 always but is ' . $sim->rtr_month
                ])->withInput();
            }*/

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return back()->withErrors([
                    'denom_id' => 'Invalid denomination provided'
                ])->withInput();
            }

            $product = Product::find($denom->product_id);
            if (empty($product)) {
                return back()->withErrors([
                    'exception' => 'Invalid product provided'
                ])->withInput();
            }

            switch ($product->vendor_code) {
                case 'LOC':
                case 'TST':
                    break;
                default:
                    return back()->withErrors([
                        'exception' => 'Invalid vendor configuration : ' . $product->vendor_code
                    ])->withInput();
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom)) {
                return back()->withErrors([
                    'exception' => 'Vendor configuration incomplete.'
                ])->withInput();
            }

            ### 1. check SIM serial first
            $sim = H2OSim::find($request->sim);
            if (empty($sim)) {
                return back()->withErrors([
                    'sim' => 'H2O activation requires SIM # to be registered on our system.'
                ])->withInput();;
            }

            if (!empty($sim->used_trans_id)) {
                return back()->withErrors([
                    'sim' => 'SIM already used for H2O activation transaction'
                ])->withInput();;
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withErrors([
                    'exception' => 'Session expired. Please login again'
                ]);
            }

            $ret = h2o::rotateDealerCode($account);
            Helper::log('### rotateDealerCode result ###', $ret);

            if (!empty($ret['msg'])) {
                return back()->withErrors([
                    'exception' => $ret['msg']
                ]);
            }

            $dc = $ret['dc'];
            $dp = $ret['dp'];

            if (empty($dc) || empty($dp)) {
                return back()->withErrors([
                    'exception' => 'Your account does not have dealer code information!'
                ]);
            }

            ### fee ###
            $fee = 0;
            $pm_fee = 0;
            if ($sim->type == 'R') {
                $fee = $vendor_denom->fee * $sim->rtr_month;
                $pm_fee = $vendor_denom->pm_fee * $sim->rtr_month;
            }

            ### get collection amount ###
            $collection_amt =  $denom->denom * $sim->rtr_month;
            if ($sim->type != 'R') {
                $collection_amt = 0;
            }

            ### check sales limit ###
            $net_revenue = 0;

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, $request->phone_type, $request->sim, $request->esn);
            $spiff_amt = $ret_spiff['spiff_amt'];
            $limit_amount_to_check = $collection_amt - $spiff_amt;

            if ($limit_amount_to_check > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
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
            $trans->carrier_contract = $request->carrier_contract;
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
            $trans->status = 'I';
            $trans->note = $request->note;
            $trans->dc = $dc;
            $trans->dp = $dp;

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $sim->rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            if ($request->port_in != 'Y') {
                switch ($vendor_denom->vendor_code) {
                    case 'LOC':
                        $ret = h2o::activateGSMSim($dc, $dp, $trans->id, $vendor_denom->act_pid, $trans->sim, $trans->npa, $trans->zip);
                        break;
                    case 'TST':
                        $ret = telestar::activate($trans->id, $vendor_denom->act_pid, $vendor_denom->denom, $trans->sim, '', $trans->npa, $trans->zip, $dc, $dp);
                        break;
                }
            } else {
                switch ($vendor_denom->vendor_code) {
                    case 'LOC':
                        $ret = h2o::createMDNPort(
                            $trans->id, $vendor_denom->act_pid, $trans->account_no, $trans->account_pin,
                            $trans->address1 . ' ' . $trans->address2, $trans->city, $trans->state, $trans->zip, $trans->first_name . ' ' . $trans->last_name,
                            $trans->email, $trans->call_back_phone, $dc, $dp,
                            $trans->esn, $trans->sim, $request->ip(), $trans->phone, $trans->current_carrier, $trans->carrier_contract
                        );
                        break;
                    case 'TST':
                        $ret = telestar::portin(
                            $trans->id, $vendor_denom->act_pid, $vendor_denom->denom, $trans->account_no, $trans->account_pin,
                            $trans->address1 . ' ' . $trans->address2, $trans->city, $trans->state, $trans->zip, $trans->first_name . ' ' . $trans->last_name,
                            $trans->email, $trans->call_back_phone, $trans->sim, $trans->phone, $trans->current_carrier, $trans->carrier_contract,
                            $dc, $dp
                        );
                        break;
                }
            }

            Helper::log('### H2O API RESULT ###', [
                'ret' => $ret
            ]);

            if (!empty($ret['error_msg'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                return back()->withErrors([
                    'exception' => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                ])->withInput();
            }

            $trans->status = $request->port_in != 'Y' ? 'C' : 'Q';
            $trans->phone = $ret['min'];
            $trans->vendor_tx_id = $ret['serial'];
            $trans->mdate = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            $sim = H2OSim::find($trans->sim);
            $sim->used_trans_id = $trans->id;
            $sim->used_date = Carbon::now();
            $sim->status = 'U';
            $sim->save();

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
            if ($collection_amt > 0 && $request->port_in != 'Y') {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }
            }

            ### dispatch RTR ###
            if ($request->port_in != 'Y') {

                $ret = RTRProcessor::applyRTR(
                    1,
                    isset($sim->type) ? $sim->type : '',
                    $trans->id,
                    $sim->type != 'B' ? 'House' : 'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $vendor_denom->denom,
                    $user->user_id,
                    $sim->type != 'B',
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@jjonbp.com', '[PM][H2O][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        $sim->type,
                        $trans->id,
                        'House',
                        $trans->phone,
                        $trans->product_id,
                        $vendor_denom->vendor_code,
                        $vendor_denom->rtr_pid,
                        $vendor_denom->denom,
                        $user->user_id,
                        true,
                        null,
                        2,
                        $vendor_denom->fee
                    );

                    if (!empty($error_msg)) {
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                        $msg .= ' - product : ' . $product->id . '<br/>';
                        $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                        $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                        $msg .= ' - error : ' . $error_msg;
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] H2O Activation - applyRTR remaining month failed', $msg);
                    }
                }
            }

            ### update balance ###
            Helper::update_balance();

            Helper::log('### API call completed ###');

            if ($request->port_in != 'Y') {
                return back()->with([
                    'activated' => 'Y',
                    'phone' => $trans->phone,
                    'invoice_no' => $trans->id,
                    'sim' => $trans->sim,
                    'sim_type' => $sim->type_name,
                    'esn' => $trans->esn,
                    'carrier' => 'H2O',
                    'product' => $product->name,
                    'amount' => $trans->denom,
                    'rtr_month' => $trans->rtr_month,
                    'sub_total' => $trans->collection_amt,
                    'fee' => $fee + $pm_fee,
                    'total' => $collection_amt + $fee + $pm_fee
                ]);
            } else {
                return back()->with([
                    'success' => 'Y',
                    'phone' => $trans->phone,
                    'invoice_no' => $trans->id,
                    'sim' => $trans->sim,
                    'sim_type' => $sim->type_name,
                    'esn' => $trans->esn,
                    'carrier' => 'H2O',
                    'product' => $product->name,
                    'amount' => $trans->denom,
                    'rtr_month' => $trans->rtr_month,
                    'sub_total' => $trans->collection_amt,
                    'fee' => $fee + $pm_fee,
                    'total' => $collection_amt + $fee + $pm_fee
                ]);
            }

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}