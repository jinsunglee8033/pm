<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 8/21/17
 * Time: 10:03 AM
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\Denom;
use App\Model\MultiLine;
use App\Model\State;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Model\H2OSim;
use App\Lib\h2o;
use App\Lib\h2o_rtr;


class H2OMultiLineController extends Controller
{

    public function step1() {

        $sim_info = Session::get('H2O:MULTI-LINE:SIM_INFO');

        return view('sub-agent.activate.h2o-multi-line.step-1', [
            'sim_info' => $sim_info
        ]);
    }

    public function checkSim(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'sim_qty' => 'required|in:2,4',
                'sim1' => 'required_if:sim_qty,2',
                'sim2' => 'required_if:sim_qty,2',
                'sim3' => 'required_if:sim_qty,4',
                'sim4' => 'required_if:sim_qty,4',
            ]);

            if ($v->fails()) {
                return back()->withInput()->withErrors($v);
            }

            $sim_array = [];

            for ($i = 1; $i <= intval($request->sim_qty); $i++) {
                $sim_serial = $request->get('sim' . $i);

                $sim = H2OSim::find($sim_serial);
                if (empty($sim)) {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'Invalid SIM provided'
                    ]);
                }

                if ($sim->type != 'P') {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'Only Wallet SIM is allowed for Multi-Line'
                    ]);
                }

                if ($sim->vendor_pid != 'W30') {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'Only $30 monthly SIM can be used for Multi-Line'
                    ]);
                }

                if ($sim->status != 'A') {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'SIM is not in active status'
                    ]);
                }

                if (!empty($sim->used_trans_id)) {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'SIM already used'
                    ]);
                }

                if ($sim->rtr_month != 1) {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'Multiple RTR month SIM is not allowed for H2O Multi-Line'
                    ]);
                }

                if (in_array($sim_serial, $sim_array)) {
                    return back()->withInput()->withErrors([
                        'sim' . $i => 'Duplicated SIM serial found'
                    ]);
                }

                $sim_array[] = $sim_serial;
            }

            $sim_info = new \stdClass();
            $sim_info->sim_qty = $request->sim_qty;
            $sim_info->sim1 = $request->sim1;
            $sim_info->sim2 = $request->sim2;
            $sim_info->sim3 = $request->sim3;
            $sim_info->sim4 = $request->sim4;

            Session::put('H2O:MULTI-LINE:SIM_INFO', $sim_info);

            return redirect('/sub-agent/activate/h2o-multi-line/step-2');

        } catch (\Exception $ex) {

            return back()->withInput()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);

        }
    }

    public function step2() {

        $sim_info = Session::get('H2O:MULTI-LINE:SIM_INFO');
        if (empty($sim_info)) {
            return redirect('/sub-agent/activate/h2o-multi-line/step-1')
                ->withInput()
                ->withErrors([
                    'error' => 'Session has been expired. Please start over.'
                ]);
        }

        $states = State::all();

        return view('sub-agent.activate.h2o-multi-line.step-2', [
            'sim_info' => $sim_info,
            'states' => $states
        ]);
    }

    public function process(Request $request) {

        try {

            if (Helper::is_login_as()) {
                return back()->withInput()->withErrors([
                    'exception' => 'We are sorry. Login as user is not allowed to make any transaction'
                ]);
            }

            $sim_info = Session::get('H2O:MULTI-LINE:SIM_INFO');
            if (empty($sim_info)) {
                return redirect('/sub-agent/activate/h2o-multi-line/step-1')
                    ->withInput()
                    ->withErrors([
                        'error' => 'Session has been expired. Please start over.'
                    ]);
            }

            $v = Validator::make($request->all(), [
                'call_back_phone' => 'nullable|regex:/^(\d{10})?$/',
                'email' => 'nullable|email',

                'sim1_type' => 'required|in:A,P',
                'A1_npa' => 'required_if:sim1_type,A|nullable|regex:/^(\d{3})?$/',
                'A1_zip' => 'required_if:sim1_type,A|nullable|regex:/^(\d{5})?$/',
                'P1_number_to_port' => 'required_if:sim1_type,P|nullable|regex:/^(\d{10})?$/',
                'P1_carrier' => 'required_if:sim1_type,P',
                'P1_account_no' => 'required_if:sim1_type,P',
                'P1_account_pin' => 'required_if:sim1_type,P',
                'P1_fname' => 'required_if:sim1_type,P',
                'P1_lname' => 'required_if:sim1_type,P',
                'P1_address1' => 'required_if:sim1_type,P',
                'P1_city' => 'required_if:sim1_type,P',
                'P1_state' => 'required_if:sim1_type,P',
                'P1_zip' => 'required_if:sim1_type,P|nullable|regex:/^(\d{5})?$/',

                'sim2_type' => 'required|in:A,P',
                'A2_npa' => 'required_if:sim2_type,A|nullable|regex:/^(\d{3})?$/',
                'A2_zip' => 'required_if:sim2_type,A|nullable|regex:/^(\d{5})?$/',
                'P2_number_to_port' => 'required_if:sim2_type,P|nullable|regex:/^(\d{20})?$/',
                'P2_carrier' => 'required_if:sim2_type,P',
                'P2_account_no' => 'required_if:sim2_type,P',
                'P2_account_pin' => 'required_if:sim2_type,P',
                'P2_fname' => 'required_if:sim2_type,P',
                'P2_lname' => 'required_if:sim2_type,P',
                'P2_address1' => 'required_if:sim2_type,P',
                'P2_city' => 'required_if:sim2_type,P',
                'P2_state' => 'required_if:sim2_type,P',
                'P2_zip' => 'required_if:sim2_type,P|nullable|regex:/^(\d{5})?$/',

            ], [
                'call_back_phone.regex' => 'Please provide valid call back number. 10 digits only',

                'A1_npa.required_if' => 'Please provide preferred area code',
                'A1_npa.regex' => 'Please provide valid preferred area code. 3 digits only',
                'A1_zip.required_if' => 'Please provide zip code',
                'A1_zip.regex' => 'Please provide valid zip code. 5 digits only',
                'P1_number_to_port.required_if' => 'Please provide port-in number',
                'P1_number_to_port.regex' => 'Please provide valid port-in number. 10 digits only',
                'P1_carrier.required_if' => 'Please provide port-in from',
                'P1_account_no.required_if' => 'Please provide account #',
                'P1_account_pin.required_if' => 'Please provide account PIN',
                'P1_fname.required_if' => 'Please provide first name',
                'P1_lname.required_if' => 'Please provide last name',
                'P1_address1.required_if' => 'Please provide address 1',
                'P1_city.required_if' => 'Please provide city',
                'P1_state.required_if' => 'Please provide state',
                'P1_zip.required_if' => 'Please provide zip code',
                'P1_zip.regex' => 'Please provide valid zip code. 5 digits only',

                'A2_npa.required_if' => 'Please provide preferred area code' . ' ' . $request->sim2_type,
                'A2_npa.regex' => 'Please provide valid preferred area code. 3 digits only',
                'A2_zip.required_if' => 'Please provide zip code',
                'A2_zip.regex' => 'Please provide valid zip code. 5 digits only',
                'P2_number_to_port.required_if' => 'Please provide port-in number',
                'P2_number_to_port.regex' => 'Please provide valid port-in number. 10 digits only',
                'P2_carrier.required_if' => 'Please provide port-in from',
                'P2_account_no.required_if' => 'Please provide account #',
                'P2_account_pin.required_if' => 'Please provide account PIN',
                'P2_fname.required_if' => 'Please provide first name',
                'P2_lname.required_if' => 'Please provide last name',
                'P2_address2.required_if' => 'Please provide address 2',
                'P2_city.required_if' => 'Please provide city',
                'P2_state.required_if' => 'Please provide state',
                'P2_zip.required_if' => 'Please provide zip code',
                'P2_zip.regex' => 'Please provide valid zip code. 5 digits only',
            ]);

            if ($v->fails()) {
                return back()->withInput()->withErrors($v);
            }

            if ($sim_info->sim_qty == 4) {
                $v = Validator::make($request->all(), [

                    'sim3_type' => 'required|in:A,P',
                    'A3_npa' => 'required_if:sim3_type,A|nullable|regex:/^(\d{3})?$/',
                    'A3_zip' => 'required_if:sim3_type,A|nullable|regex:/^(\d{5})?$/',
                    'P3_number_to_port' => 'required_if:sim3_type,P|regex:/^(\d{30})?$/',
                    'P3_carrier' => 'required_if:sim3_type,P',
                    'P3_account_no' => 'required_if:sim3_type,P',
                    'P3_account_pin' => 'required_if:sim3_type,P',
                    'P3_fname' => 'required_if:sim3_type,P',
                    'P3_lname' => 'required_if:sim3_type,P',
                    'P3_address3' => 'required_if:sim3_type,P',
                    'P3_city' => 'required_if:sim3_type,P',
                    'P3_state' => 'required_if:sim3_type,P',
                    'P3_zip' => 'required_if:sim3_type,P|nullable|regex:/^(\d{5})?$/',

                    'sim4_type' => 'required|in:A,P',
                    'A4_npa' => 'required_if:sim4_type,A|nullable|regex:/^(\d{3})?$/',
                    'A4_zip' => 'required_if:sim4_type,A|nullable|regex:/^(\d{5})?$/',
                    'P4_number_to_port' => 'required_if:sim4_type,P|regex:/^(\d{40})?$/',
                    'P4_carrier' => 'required_if:sim4_type,P',
                    'P4_account_no' => 'required_if:sim4_type,P',
                    'P4_account_pin' => 'required_if:sim4_type,P',
                    'P4_fname' => 'required_if:sim4_type,P',
                    'P4_lname' => 'required_if:sim4_type,P',
                    'P4_address4' => 'required_if:sim4_type,P',
                    'P4_city' => 'required_if:sim4_type,P',
                    'P4_state' => 'required_if:sim4_type,P',
                    'P4_zip' => 'required_if:sim4_type,P|nullable|regex:/^(\d{5})?$/',

                ], [
                    'A3_npa.required_if' => 'Please provide preferred area code',
                    'A3_npa.regex' => 'Please provide valid preferred area code. 3 digits only',
                    'A3_zip.required_if' => 'Please provide zip code',
                    'A3_zip.regex' => 'Please provide valid zip code. 5 digits only',
                    'P3_number_to_port.required_if' => 'Please provide port-in number',
                    'P3_number_to_port.regex' => 'Please provide valid port-in number. 10 digits only',
                    'P3_carrier.required_if' => 'Please provide port-in from',
                    'P3_account_no.required_if' => 'Please provide account #',
                    'P3_account_pin.required_if' => 'Please provide account PIN',
                    'P3_fname.required_if' => 'Please provide first name',
                    'P3_lname.required_if' => 'Please provide last name',
                    'P3_address3.required_if' => 'Please provide address 3',
                    'P3_city.required_if' => 'Please provide city',
                    'P3_state.required_if' => 'Please provide state',
                    'P3_zip.required_if' => 'Please provide zip code',
                    'P3_zip.regex' => 'Please provide valid zip code. 5 digits only',

                    'A4_npa.required_if' => 'Please provide preferred area code',
                    'A4_npa.regex' => 'Please provide valid preferred area code. 3 digits only',
                    'A4_zip.required_if' => 'Please provide zip code',
                    'A4_zip.regex' => 'Please provide valid zip code. 5 digits only',
                    'P4_number_to_port.required_if' => 'Please provide port-in number',
                    'P4_number_to_port.regex' => 'Please provide valid port-in number. 10 digits only',
                    'P4_carrier.required_if' => 'Please provide port-in from',
                    'P4_account_no.required_if' => 'Please provide account #',
                    'P4_account_pin.required_if' => 'Please provide account PIN',
                    'P4_fname.required_if' => 'Please provide first name',
                    'P4_lname.required_if' => 'Please provide last name',
                    'P4_address4.required_if' => 'Please provide address 4',
                    'P4_city.required_if' => 'Please provide city',
                    'P4_state.required_if' => 'Please provide state',
                    'P4_zip.required_if' => 'Please provide zip code',
                    'P4_zip.regex' => 'Please provide valid zip code. 5 digits only',
                ]);

                if ($v->fails()) {
                    return back()->withInput()->withErrors($v);
                }
            }

            for ($i = 1; $i <= $sim_info->sim_qty; $i++) {
                $sim_type = $request->get('sim' . $i . '_type');

                if ($sim_type == 'P') {
                    $v = Validator::make($request->all(), [
                        'call_back_phone' => 'required',
                        'email' => 'required'
                    ], [
                        'call_back_phone.required' => 'Please provide call back number for port-in',
                        'email.required' => 'Please provide email for port-in'
                    ]);

                    if ($v->fails()) {
                        return back()->withInput()->withErrors($v);
                    }
                }
            }

            ### prepare transaction records ###
            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withErrors([
                    'exception' => 'Session expired. Please login again'
                ]);
            }


            ### check collection amount total ###
            $denom = Denom::where('product_id', 'WMLL')
                ->where('denom', $sim_info->sim_qty == 2 ? 50 : 100)
                ->first();
            if (empty($denom)) {
                return back()->withInput()->withErrors('Something is wrong. Invalid denomination found for H2O multi-line');
            }

            for ($i = 1; $i <= $sim_info->sim_qty; $i++) {

                switch ($i) {
                    case 1:
                        $sim = $sim_info->sim1;
                        break;
                    case 2:
                        $sim = $sim_info->sim2;
                        break;
                    case 3:
                        $sim = $sim_info->sim3;
                        break;
                    case 4:
                        $sim = $sim_info->sim4;
                        break;
                    default:
                        $sim = '';
                        break;
                }

                $sim_obj = H2OSim::find($sim);
                $collection_amt = 0;
                if ($sim_obj->type == 'R') {
                    $collection_amt = 25;
                }

                $fee = 0;
                $pm_fee = 0;

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

                ### prev-save fee ###
                if (!is_array($sim_info->fee)) {
                    $sim_info->fee = [];
                }
                $sim_info->fee[$i] = $fee;

                ### prev-save pm_fee ###
                if (!is_array($sim_info->pm_fee)) {
                    $sim_info->pm_fee = [];
                }
                $sim_info->pm_fee[$i] = $pm_fee;

                ### pre-save collection amt ###
                if (!is_array($sim_info->collection_amt)) {
                    $sim_info->collection_amt = [];
                }
                $sim_info->collection_amt[$i] = $collection_amt;

                ### pre-save net revenue ###
                if (!is_array($sim_info->net_revenue)) {
                    $sim_info->net_revenue = [];
                }
                $sim_info->net_revenue[$i] = $net_revenue;
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

            ### check port-in availability ###

            for ($i = 1; $i <= $sim_info->sim_qty; $i++) {
                $product = 'W30';
                $sim_type = $request->get('sim' . $i . '_type');
                if ($sim_type == 'P') {
                    $cid = time();
                    $min = $request->get('P' . $i . '_number_to_port');
                    $ret = h2o::getMDNPortability($cid, $product, $min);

                    if (!empty($ret['error_code'])) {
                        //return $ret;
                        return back()->withInput()->withInput([
                            'exception' => 'Unable to process port-in for ' . $min . ': ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                        ]);
                    }

                    $allowed_activity = $ret['allowedactivity'];
                    if (!in_array($allowed_activity, ['C', 'R'])) {
                        return back()->withInput()->withInput([
                            'exception' => 'Unable to process port-in for ' . $min . ': Requested number is not in Port-In allowed status'
                        ]);
                    }

                    $portable = $ret['portable'];
                    if ($portable != 'Y') {

                        return back()->withInput()->withInput([
                            'exception' => 'Unable to process port-in for ' . $min . ': Requested number is not in Port-In allowed status'
                        ]);
                    }
                }
            }

            ### prepare transaction ###
            $first_trans_id = null;

            $multi_trans = [];

            for ($i = 1; $i <= $sim_info->sim_qty; $i++) {
                $sim_type = $request->get('sim'. $i . '_type');
                $sim = '';
                $npa = $request->get('A' . $i . '_npa');
                $fname = $request->get('P' . $i . '_fname');
                $lname = $request->get('P' . $i . '_lname');
                $address1 = $request->get('P' . $i . '_address1');
                $address2 = $request->get('P' . $i . '_address2');
                $city = $request->get('P' . $i . '_city');
                $state = $request->get('P' . $i . '_state');
                $zip = $sim_type == 'A' ? $request->get('A' . $i . '_zip') : $request->get('P' . $i . '_zip');
                $phone = $request->get('P' . $i . '_number_to_port');
                $carrier = $request->get('P'. $i . '_carrier');
                $account_no = $request->get('P' . $i . '_account_no');
                $account_pin = $request->get('P' . $i . '_account_pin');

                switch ($i) {
                    case 1:
                        $sim = $sim_info->sim1;
                        break;
                    case 2:
                        $sim = $sim_info->sim2;
                        break;
                    case 3:
                        $sim = $sim_info->sim3;
                        break;
                    case 4:
                        $sim = $sim_info->sim4;
                        break;
                }

                $trans = new Transaction;
                $trans->account_id = $user->account_id;
                $trans->product_id = 'WMLL';
                $trans->action = $sim_type == 'A' ? 'Activation' : 'Port-In';
                $trans->denom = 25;
                $trans->sim = $sim;
                $trans->esn = '';
                $trans->npa = $npa;
                $trans->first_name = $fname;
                $trans->last_name = $lname;
                $trans->address1 = $address1;
                $trans->address2 = $address2;
                $trans->city = $city;
                $trans->state = $state;
                $trans->zip = $zip;
                $trans->phone = $phone;
                $trans->current_carrier = $carrier;
                $trans->carrier_contract = 'Y';
                $trans->account_no = $account_no;
                $trans->account_pin = $account_pin;
                $trans->call_back_phone = $request->call_back_phone;
                $trans->email = $request->email;
                $trans->pref_pin = '';
                $trans->status = '';
                $trans->created_by = $user->user_id;
                $trans->cdate = Carbon::now();
                $trans->status = 'I';
                $trans->note = '';
                $trans->dc = $dc;
                $trans->dp = $dp;

                $trans->collection_amt = $sim_info->collection_amt[$i];
                $trans->fee = $sim_info->fee[$i];
                $trans->pm_fee = $sim_info->pm_fee[$i];
                $trans->rtr_month = 1;
                $trans->net_revenue = $sim_info->net_revenue[$i];

                $trans->save();

                $multi_trans[$i] = $trans;

                if ($i == 1) {
                    $first_trans_id = $trans->id;
                }
            }

            Helper::log('#### SIM_QTY #####', $sim_info->sim_qty);

            ### need processing now ###
            # 1. Bind SIM
            switch ($sim_info->sim_qty) {
                case 2:
                    $ret = h2o::CreateLineSet2($first_trans_id, 'W30', $sim_info->sim1, $sim_info->sim2);
                    break;
                case 4:
                    $ret = h2o::CreateLineSet4($first_trans_id, 'W30', $sim_info->sim1, $sim_info->sim2, $sim_info->sim3, $sim_info->sim4);
                    break;
                default:
                    throw new \Exception('Invalid SIM qty found: ' . $sim_info->sim_qty);
            }

            if (!empty($ret['error_code'])) {
                return back()->withInput()->withErrors([
                    'exception' => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                ]);
            }

            Helper::log('#### BP2 #####', 'HEY');

            # 2. Payment via HDN
            $ret = h2o_rtr::payment('WMLL', $sim_info->sim1, $sim_info->sim_qty == 2 ? 50 : 100, $first_trans_id);
            if (!empty($ret['error_code'])) {
                return back()->withInput()->withErrors([
                    'exception' => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                ]);
            }

            Helper::log('#### BP3 #####', 'HEY');

            # 3. call activate or port-in
            for ($i = 1; $i <= $sim_info->sim_qty; $i++) {
                $sim_type = $request->get('sim' . $i . '_type');
                $trans = $multi_trans[$i];

                switch ($sim_type) {
                    case 'A':
                        $ret = h2o::activateGSMSim($dc, $dp, $trans->id, 'W30', $trans->sim, $trans->npa, $trans->zip);
                        break;
                    case 'P':
                        $ret = h2o::createMDNPort($trans->id, 'W30', $trans->account_no, $trans->account_pin,
                            $trans->address1 . ' ' . $trans->address2, $trans->city, $trans->state, $trans->zip, $trans->first_name . ' ' . $trans->last_name,
                            $trans->email, $trans->call_back_phone, $dc, $dp,
                            $trans->esn, $trans->sim, $request->ip(), $trans->phone, $trans->current_carrier, $trans->carrier_contract,
                            $require_portability_check = false
                        );
                        break;
                }

                if (!empty($ret['error_code'])) {
                    $trans->status = 'F';
                    $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                    $trans->mdate = Carbon::now();
                    $trans->modified_by = $user->user_id;
                    $trans->api = 'Y';
                    $trans->save();

                    /*return back()->withErrors([
                        'exception' => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                    ])->withInput();*/

                    Helper::send_mail('it@perfectmobileinc.com', '[H2O][' . getenv('APP_ENV') . '] Multi-Line failure', ' - ID : ' . $trans->id);
                } else {
                    $trans->status = $sim_type == 'A' ? 'C' : 'Q';
                    $trans->phone = $ret['min'];
                    $trans->vendor_tx_id = $ret['serial'];
                    $trans->mdate = Carbon::now();
                    $trans->modified_by = $user->user_id;
                    $trans->api = 'Y';
                    $trans->save();
                }

                $sim = H2OSim::find($trans->sim);
                $sim->used_trans_id = $trans->id;
                $sim->used_date = Carbon::now();
                $sim->status = 'U';
                $sim->save();

                ### commission ###
                # - no commission for activation 09/25/2017
                /*
                $ret = CommissionProcessor::create($trans->id, false, $denom->denom);
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
                if ($trans->collection_amt > 0) {
                    $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $denom->denom, 1, $trans->phone, $trans->id, $trans->created_by, $sim_info->sim_qty);
                    if (!empty($ret['error_code'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                    }
                }
            }



            Helper::log('#### BP4 #####', 'HEY');

            ### multi-line log ###
            $line = new MultiLine;
            $line->sim_qty = $sim_info->sim_qty;
            $line->sim1 = $sim_info->sim1;
            $line->sim2 = $sim_info->sim2;
            $line->sim3 = isset($sim_info->sim3) ? $sim_info->sim3 : '';
            $line->sim4 = isset($sim_info->sim4) ? $sim_info->sim4 : '';
            $line->mdn1 = $multi_trans[1]->phone;
            $line->mdn2 = $multi_trans[2]->phone;
            if ($line->sim_qty == 4) {
                $line->mdn3 = $multi_trans[3]->phone;
                $line->mdn4 = $multi_trans[4]->phone;
            }
            $line->act_trans_id = $first_trans_id;
            $line->cdate = Carbon::now();
            $line->created_by = $user->user_id;
            $line->save();

            Helper::log('#### BP5 #####', 'HEY');

            Session::put('H2O:MULTI-LINE:SIM_INFO', null);
            Session::put('H2O:MULTI-LINE:SIM_INFO:COMPLETE', $sim_info);
            Session::put('H2O:MULTI-LINE:TRANS:COMPLETE', $multi_trans);

            return redirect('/sub-agent/activate/h2o-multi-line/step-3');

        } catch (\Exception $ex) {
            return back()->withInput()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']' . ' => ' . $ex->getTraceAsString()
            ]);
        }

    }

    public function step3() {

        $sim_info = Session::get('H2O:MULTI-LINE:SIM_INFO:COMPLETE');
        $multi_trans = Session::get('H2O:MULTI-LINE:TRANS:COMPLETE');

        return view('sub-agent.activate.h2o-multi-line.step-3', [
            'sim_info' => $sim_info,
            'multi_trans' => $multi_trans
        ]);
    }

}