<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 11/13/17
 * Time: 10:41 AM
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\reup;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\ROKESN;
use App\Model\ROKSim;
use App\Model\ROKMapping;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ROKController
{
    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_rok != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do ROK Mobile activation. Please contact your distributor'
            ]);
        }

        $has_mapping_esn = false;
        $has_mapping_sim = false;
        $esn_status = null;

        $call_back_phone = $request->call_back_phone;
        if (empty($call_back_phone)) {
            $call_back_phone = $account->office_number;
        }

        $register_for_rebate = $request->input('register_for_rebate', 'N');

        $products = Product::where('carrier', 'ROK')
            ->where('status', 'A')
            ->get();

        $denom = null;

        $rtr_month = $request->get('rtr_month', 0);

        $denom = Denom::find($request->denom_id);

        $enabled_product_id = '';
        $lock_product = 'N';

        $esn = $request->esn;
        if ($esn == '123456789') {
            $esn = '';
        }

        $phone_type = $request->input('phone_type', old('phone_type'));

        if (!empty($denom)) {
            $enabled_product_id = $denom->product_id;
            if ($enabled_product_id == 'WROKG') {
                //$esn = '';
            }

            if ($enabled_product_id == 'WROKC') {
                $phone_type = '4g';
            }
        }

        $allowed_denoms = [];
        $allowed_product_id = '';
        $allowed_months = [1, 2, 3];

        $sim_type = null;
        $esn_type = null;
        $sim_spiff_amt = 0;
        $esn_spiff_amt = 0;
        $dvc_rebate_amt = 0;

        if (!empty($esn)) {
            $esn_obj = ROKESN::find($esn);

            if (!empty($denom)) {
                if ($account->rebates_eligibility == 'Y') {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $esn);
                    $dvc_rebate_amt = $ret_rebate['rebate_amt'];
                }

                if ($phone_type == '3g') {
                    $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, $phone_type, null, $esn);
                    $esn_spiff_amt = $ret_spiff['spiff_amt'];
                }
            }

            if (!empty($esn_obj)) {

                $esn_type = $esn_obj->type;

                /* Allow re-use of ESN
                if (!empty($esn_obj->used_trans_id)) {
                    return back()->withErrors([
                        'esn' => 'ESN/IMEI already used for ROK activation transaction'
                    ])->withInput();
                }
                */

                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return back()->withErrors([
                        'esn' => 'ESN/IMEI status is not active : ' . $esn_obj->status
                    ])->withInput();
                }

                $esn_status = $esn_obj->status;

                /*if (!empty($enabled_product_id)) {

                    switch ($enabled_product_id) {
                        case 'WROKC':
                            if ($esn_obj->sub_carrier != 'CDMA') {
                                return back()->withErrors([
                                    'esn' => 'Only CMDA ESN/IMEI is allowed'
                                ])->withInput(Input::except('esn'));
                            }
                            break;
                        case 'WROKG':
                            if ($esn_obj->sub_carrier != 'GSM') {
                                return back()->withErrors([
                                    'esn' => 'Only GSM ESN/IMEI is allowed'
                                ])->withInput(Input::except('esn'));
                            }
                            break;
                        case 'WROKS':
                            if ($esn_obj->sub_carrier != 'SPR') {
                                return back()->withErrors([
                                    'esn' => 'Only SPR ESN/IMEI is allowed'
                                ])->withInput(Input::except('esn'));
                            }
                            break;
                    }

                }*/

                //if ($esn_obj->type != 'R') {
                    $allowed_months = explode('|', empty($esn_obj->rtr_month) ? '1|2|3' : $esn_obj->rtr_month);
                //}



                switch ($esn_obj->sub_carrier) {
                    case 'CDMA':
                        $enabled_product_id = $allowed_product_id = 'WROKC';
                        break;
                    case 'GSM':
                        $enabled_product_id = $allowed_product_id = 'WROKG';
                        break;
                    case 'SPR':
                        $enabled_product_id = $allowed_product_id = 'WROKS';
                        break;
                }

                if ($esn_obj->type == 'C') {
                    ### check owner ###
                    if (empty($esn_obj->owner_id)) {
                        return back()->withErrors([
                            'esn' => 'Consignment ESN owner ID is not set.'
                        ])->withInput();
                    }

                    ### check owner path ###
                    $owner = Account::where('id', $esn_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return back()->withErrors([
                            'esn' => 'Consignment ESN owner ID is invalid.'
                        ])->withInput();
                    }
                }

                if ($esn_obj->type != 'R' || !empty($esn_obj->amount)) {
                    $lock_product = 'Y';
                    $allowed_denoms = explode('|', $esn_obj->amount);
                } else {
                    $denoms = Denom::where('product_id', $enabled_product_id)
                        ->where('status', 'A')
                        ->select('denom')
                        ->get();
                    foreach ($denoms as $o) {
                        $allowed_denoms[] = $o->denom;
                    }
                }

                if (empty($request->sim) && isset($denom) && ($denom->product_id != $allowed_product_id || !in_array($denom->denom, $allowed_denoms) && count($allowed_denoms) > 0)) {
                    $denom = null;
                }

                $mapping_count = ROKMapping::where('esn',$request->esn)->where('status', 'A')->count();
                if (!empty($mapping_count) && $mapping_count > 0) {
                    $has_mapping_sim = true;
                }

            }
        }

        if (!empty($request->sim)) {

            if (!empty($denom)) {
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, $phone_type, $request->sim, $esn);
                $sim_spiff_amt = $ret_spiff['spiff_amt'];
            }

            $sim = ROKSim::find($request->sim);
            if (!empty($sim)) {
                if (!empty($sim->used_trans_id)) {
                    return back()->withErrors([
                        'sim' => 'SIM already used for ROK activation transaction'
                    ])->withInput();
                }

                if ($sim->status != 'A') {
                    return back()->withErrors([
                        'sim' => 'SIM status is not active : ' . $sim->status
                    ])->withInput();
                }

                $sim_type = $sim->type;

                /*if (!empty($enabled_product_id)) {

                    switch ($enabled_product_id) {
                        case 'WROKC':
                            if ($sim->sub_carrier != 'CDMA') {
                                return back()->withErrors([
                                    'sim' => 'Only CMDA SIM is allowed'
                                ])->withInput(Input::except('sim'));
                            }
                            break;
                        case 'WROKG':
                            if ($sim->sub_carrier != 'GSM') {
                                return back()->withErrors([
                                    'sim' => 'Only GSM SIM is allowed'
                                ])->withInput(Input::except('sim'));
                            }
                            break;
                        case 'WROKS':
                            if ($sim->sub_carrier != 'SPR') {
                                return back()->withErrors([
                                    'sim' => 'Only SPR SIM is allowed'
                                ])->withInput(Input::except('sim'));
                            }
                            break;
                    }

                }*/

                //if ($sim->type != 'R') {
                    $allowed_months = explode('|', empty($sim->rtr_month) ? '1|2|3' : $sim->rtr_month);
                //}

                switch ($sim->sub_carrier) {
                    case 'CDMA':
                        $enabled_product_id = $allowed_product_id = 'WROKC';
                        break;
                    case 'GSM':
                        $enabled_product_id = $allowed_product_id = 'WROKG';
                        break;
                    case 'SPR':
                        $enabled_product_id = $allowed_product_id = 'WROKS';
                        break;
                }

                if ($sim->type == 'C') {
                    ### check owner ###
                    if (empty($sim->owner_id)) {
                        return back()->withErrors([
                            'sim' => 'Consignment SIM owner ID is not set.'
                        ])->withInput();
                    }

                    ### check owner path ###
                    $owner = Account::where('id', $sim->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return back()->withErrors([
                            'sim' => 'Consignment SIM owner ID is invalid.'
                        ])->withInput();
                    }
                }

                if ($sim->type != 'R' || !empty($sim->amount)) {
                    $lock_product = 'Y';
                    $allowed_denoms = explode('|', $sim->amount);
                } else {
                    $denoms = Denom::where('product_id', $enabled_product_id)
                        ->where('status', 'A')
                        ->select('denom')
                        ->get();
                    foreach ($denoms as $o) {
                        $allowed_denoms[] = $o->denom;
                    }
                }

                if (isset($denom) && ($denom->product_id != $allowed_product_id || !in_array($denom->denom, $allowed_denoms) && count($allowed_denoms) > 0)) {
                    $denom = null;
                }

                $mapping = ROKMapping::where('sim',$request->sim)->where('status', 'A')->first();
                if (!empty($mapping)) {
                    $request->esn = $mapping->esn;
                    $has_mapping_esn = true;

                    $esn_obj = ROKESN::find($request->esn);
                    if (!empty($esn_obj)) {
                        $esn_type = $esn_obj->type;
                        $esn_status = $esn_obj->status;
                    }

                    if (!empty($denom)) {
                        if ($account->rebates_eligibility == 'Y') {
                            $rebate_type = empty($esn_obj) ? 'B' : 'R';
                            $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                            $dvc_rebate_amt = $ret_rebate['rebate_amt'];
                        }

                        if ($phone_type == '3g') {
                            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, $phone_type, null, $request->esn);
                            $esn_spiff_amt = $ret_spiff['spiff_amt'];
                        }
                    }
                } else {
                    if ($has_mapping_sim) {
                        return back()->withErrors([
                            'sim' => 'SIM [' . $request->sim . '] can not activate the device [' . $esn . '] !!'
                        ])->withInput();
                    }
                }
            } else {
                if ($has_mapping_sim) {
                    return back()->withErrors([
                        'sim' => 'SIM [' . $request->sim . '] can not activate the device [' . $esn . '] !!'
                    ])->withInput();
                }
            }
        }

        if (!in_array($rtr_month, $allowed_months) && count($allowed_months) > 0) {
            $rtr_month = $allowed_months[0];
        }

        $states = State::all();

        $amt = isset($denom) ? $denom->denom : 0;

        $is_consignment = 'N';
        $charge_amt = 0;
        if (!empty($denom)) {
            $sim_type = ROKSim::get_sim_type($phone_type, $denom->product_id, $esn, $request->sim);
            if ($sim_type == 'C') {
                $charge_amt = ROKSim::get_sim_charge_amt($phone_type, $denom->product_id, $esn, $request->sim, 'S');
                $is_consignment = 'Y';
                $amt = $charge_amt;
            }
        }

        $sub_total = $amt * $rtr_month;

        $fee = 0;

        if (!empty($denom)) {
            $vendor_denom = VendorDenom::where('product_id', $denom->product_id)
                ->where('vendor_code', 'RUP')
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
            ->where('product.carrier', 'ROK')
            ->selectRaw('transaction.*, accounts.name as account_name')
            ->orderBy('transaction.cdate', 'desc')
            ->skip(0)->take(3)
            ->get();

        $carriers = DB::select("
            select * 
            from reup_carrier
            where `name` not like 'ROK%'
            order by name asc
        ");

        return view('sub-agent.activate.rok', [
            'transactions' => $transactions,
            'products' => $products,
            'states' => $states,
            'denom' => $denom,
            'denom_id' => isset($denom->id) ? $denom->id : null,
            'enabled_product_id' => $enabled_product_id,
            'lock_product' => $lock_product,
            'carriers' => $carriers,
            'current_carrier_id' => $request->current_carrier_id,
            'number_to_port' => $request->number_to_port,
            'account_no' => $request->account_no,
            'account_pin' => $request->account_pin,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'address1' => $request->address1,
            'address2' => $request->address2,
            'city' => $request->city,
            'state' => $request->state,
            'npa' => $request->npa,
            'zip' => $request->zip,
            'call_back_phone' => $call_back_phone,
            'email' => $request->email,
            'phone_type' => $phone_type,
            'sim' => $request->sim,
            'esn' => $request->esn,
            'sim_type' => $sim_type,
            'esn_type' => $esn_type,
            'has_mapping_esn' => $has_mapping_esn,
            'esn_status' => $esn_status,
            'port_in' => $request->port_in,
            'rtr_month' => $rtr_month,
            'amt' => $amt,
            'sub_total' => $sub_total,
            'fee' => $fee,
            'total' => $total,
            'register_for_rebate' => $register_for_rebate,
            'allowed_denoms' => $allowed_denoms,
            'allowed_product_id' => $allowed_product_id,
            'allowed_months' => $allowed_months,
            'is_consignment' => $is_consignment,
            'charge_amt' => $charge_amt,
            'sim_spiff_amt' => $sim_spiff_amt,
            'esn_spiff_amt' => $esn_spiff_amt,
            'dvc_rebate_amt' => $dvc_rebate_amt
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
                'sim' => 'nullable|regex:/^\d{5,30}$/',
                'esn' => 'required',
                //'npa' => 'required_if:port_in,N',
                'zip' => 'nullable|regex:/^\d{5}$/',
                'number_to_port' => 'required_if:port_in,Y',
                'current_carrier_id' => 'required_if:port_in,Y',
                'account_no' => 'required_if:port_in,Y',
                'account_pin' => 'required_if:port_in,Y',
                'call_back_phone' => 'required',
                'rtr_month' => 'required|in:1,2,3'
            ], [
                'number_to_port.required_if' => 'Port-in number is required',
                'current_carrier_id.required_if' => 'Port-in from is required',
                'account_no.required_if' => 'Account # is required',
                'account_pin.required_if' => 'Account PIN is required',
                'call_back_phone.required_if' => 'Call back phone # is required',
                'npa.required_if' => 'Pref.Area Code is required',
                'denom_id.required' => 'Please select product',
                'first_name.required' => 'First name is required',
                'last_name.required' => 'Last name is required',
                'address1.required' => 'Address1 is required',
                'city.required' => 'City is required',
                'state.required' => 'State is required',
                'email.required' => 'Email is required',
                'rtr_month.required' => 'Please select activation month'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)
                    ->withInput();
            }

            $esn = $request->esn . $request->esn_16;
            if ($esn == '123456789') {
                $esn = '';
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



            /*$pattern = '/\d{3}$/';
            if ($request->port_in != 'Y' && !preg_match($pattern, $request->npa)) {
                return back()->withErrors([
                    'npa' => 'Please enter valid 3 digit preferred area code'
                ])->withInput();
            }*/

            $pattern = '/\d{5}$/';
            if ($request->port_in != 'Y' && !preg_match($pattern, $request->zip)) {
                return back()->withErrors([
                    'zip' => 'Please enter valid 5 digit zip code. Zip code is required for ROK activation'
                ])->withInput();
            }

            if ($request->port_in == 'Y') {
                $old_request = Transaction::join('product', 'product.id', '=', 'transaction.product_id')
                    ->where('product.carrier', 'ROK')
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

            if ($product->id != 'WROKG' && empty($request->esn)) {
                return back()->withErrors([
                    'esn' => 'ESN / IMEI is required'
                ])->withInput();
            }

            if (($product->id == 'WROKG' || $request->phone_type == '4g') && empty($request->sim)) {
                return back()->withErrors([
                    'sim' => 'SIM is required'
                ])->withInput();
            }

            if ($product->id == 'WROKS' && $request->phone_type == '3g' && $esn != '' && !ctype_alnum($esn)) {
                return back()->withErrors([
                    'esn' => 'Please enter valid ESN/IMEI - alpha numeric only'
                ])->withInput();
            }

            if ($product->id == 'WROKS' && $request->phone_type == '4g' && $esn != '' &&  !is_numeric($esn)) {
                return back()->withErrors([
                    'esn' => 'Please enter valid ESN/IMEI - digits only'
                ])->withInput();
            }

            if ($product->id == 'WROKC' && $request->phone_type == '4g' && $esn != '' &&  !is_numeric($esn)) {
                return back()->withErrors([
                    'esn' => 'Please enter valid ESN/IMEI - digits only'
                ])->withInput();
            }

            if (empty($request->sim) && $product->id == 'WROKC' && $request->phone_type == '4g') {
                return back()->withErrors([
                    'sim' => 'SIM is required for ROK - CDMA with 4g phone'
                ])->withInput();
            }

            if ($request->register_for_rebate == 'Y' && empty($esn)) {
                return back()->withErrors([
                    'esn' => 'Please enter ESN/IMEI for device rebate'
                ])->withInput();
            }

            if (empty($request->phone_type) && $product->id != 'WROKG') {
                return back()->withErrors([
                    'phone_type' => 'Phone type is required'
                ])->withInput();
            }

            $account = Account::find(Auth::user()->account_id);
            if (empty($account)) {
                return back()->withErrors([
                    'exception' => 'Your session has been expired.'
                ])->withInput();
            }

            $mapping = null;
            $mapping_count = ROKMapping::where('esn',$esn)->where('status', 'A')->count();
            if (!empty($mapping_count) && $mapping_count > 0) {
                $mapping = ROKMapping::where('esn',$esn)->where('sim',$request->sim)->where('status', 'A')->first();
                if (empty($mapping)) {
                    return back()->withErrors([
                        'exception' => 'The esn is mapped with different sims !!'
                    ])->withInput();
                }
            }

            $sim_sub_carrier = null;
            $sim_rtr_month = null;
            $sim = ROKSim::find($request->sim);
            if (!empty($sim)) {
                if ($sim->status != 'A') {
                    return back()->withErrors([
                        'sim' => 'Invalid SIM provided'
                    ])->withInput();
                }

                if ($sim->type == 'C') {
                    ### check owner ###
                    if (empty($sim->owner_id)) {
                        return back()->withErrors([
                            'sim' => 'Consignment SIM owner ID is not set.'
                        ])->withInput();
                    }

                    ### check owner path ###
                    $owner = Account::where('id', $sim->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return back()->withErrors([
                            'sim' => 'Consignment SIM owner ID is invalid.'
                        ])->withInput();
                    }
                }

                /*if ($sim->type == 'R' && $sim->rtr_month != 1) {
                    return back()->withErrors([
                        'sim' => 'Regular SIM RTR.Month should be 1 always but is ' . $sim->rtr_month
                    ])->withInput();
                }*/

                if (!empty($sim->used_trans_id)) {
                    return back()->withErrors([
                        'sim' => 'SIM already used for ROK activation transaction'
                    ])->withInput();;
                }


                if (!in_array($denom->denom, explode('|', $sim->amount))) {
                    return back()->withErrors([
                        'sim' => 'Plan $' . $denom->denom . ' is not allowed to the SIM'
                    ])->withInput();
                }

                $sim_sub_carrier = $sim->sub_carrier;
                $sim_rtr_month = $sim->rtr_month;
            }

            $esn_obj = ROKESN::find($esn);
            if (!empty($esn_obj)) {
                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return back()->withErrors([
                        'esn' => 'Invalid ESN/IMEI provided'
                    ])->withInput();
                }

                if ($esn_obj->type == 'C') {
                    ### check owner ###
                    if (empty($esn_obj->owner_id)) {
                        return back()->withErrors([
                            'esn' => 'Consignment ESN owner ID is not set.'
                        ])->withInput();
                    }

                    ### check owner path ###
                    $owner = Account::where('id', $esn_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return back()->withErrors([
                            'esn' => 'Consignment ESN owner ID is invalid.'
                        ])->withInput();
                    }
                }

                /*if ($esn_obj->type == 'R' && $esn_obj->rtr_month != 1) {
                    return back()->withErrors([
                        'esn' => 'Regular ESN/IMEI RTR.Month should be 1 always but is ' . $esn_obj->rtr_month
                    ])->withInput();
                }*/

                ### allow re-use of ESN ###
                /*
                if (!empty($esn_obj->used_trans_id)) {
                    return back()->withErrors([
                        'esn' => 'ESN/IMEI already used for ROK activation transaction',
                        'exception' => 'ESN/IMEI already used for ROK activation transaction'
                    ])->withInput();
                }
                */


                if (!in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    return back()->withErrors([
                        'esn' => 'Plan $' . $denom->denom . ' is not allowed to the ESN'
                    ])->withInput();
                }

                if (!empty($sim_sub_carrier) && ($sim_sub_carrier != $esn_obj->sub_carrier)) {
                    return back()->withErrors([
                        'esn' => 'SIM and ESN are not belong to same carrier'
                    ])->withInput();
                }

                if (!empty($sim_rtr_month) && ($sim_rtr_month != $esn_obj->rtr_month)) {
                    return back()->withErrors([
                        'esn' => 'SIM and ESN have different RTR Months'
                    ])->withInput();
                }
            }

            /*switch ($product->id) {
                case 'WROKS':
                    break;
                default:
                    if (!empty($esn) && empty($esn_obj)) {
                        return back()->withErrors([
                            'esn' => 'BYOD Device is not allowed for non-Sprint product'
                        ])->withInput();
                    }

                    if (!empty($request->sim) && empty($sim)) {
                        return back()->withErrors([
                            'sim' => 'BYOD Device is not allowed for non-Sprint product'
                        ])->withInput();
                    }
                    break;
            }*/

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

            $user = Auth::user();
            $current_carrier = '';
            if ($request->port_in == 'Y') {
                $carrier = DB::table('reup_carrier')->where('id', $request->current_carrier_id)->first();
                if (empty($carrier)) {
                    return back()->withErrors([
                        'current_carrier_id' => 'Please select Port-In From'
                    ])->withInput();
                }

                $current_carrier = $carrier->name;
            }

            ### fee ###
            $rtr_month = $request->rtr_month;
            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt =  $denom->denom * $rtr_month;

            $sim_type = ROKSim::get_sim_type($request->phone_type, $denom->product_id, $esn, $request->sim);
            switch (substr($sim_type, 0, 1)) {
                case 'P':
                    $collection_amt = 0;
                    break;
                case 'C':
                    ### collection amount = charge.amount.r of SIM / ESN ###
                    $collection_amt = $rtr_month * ROKSim::get_sim_charge_amt($request->phone_type, $denom->product_id, $esn, $request->sim, 'S');
                    break;
                case 'X':
                    return back()->withErrors([
                        'sim' => 'Unable to determine SIM type'
                    ])->withInput();
            }

            $esn_type = ROKESN::get_esn_type($esn);

            Helper::log('### SIM / ESN TYPE ###', [
                'sim_type' => $sim_type,
                'esn_type' => $esn_type
            ]);

            ### check sales limit ###
            $net_revenue = 0;
            $rebate_type = empty($esn_obj) ? 'B' : 'R';
            $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $esn);
            $rebate_amt = $account->rebates_eligibility == 'Y' ? $ret_rebate['rebate_amt'] : 0;
            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, $request->phone_type, $request->sim, $esn);
            $spiff_amt = $ret_spiff['spiff_amt'];
            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt;

            Helper::log('### limit check ###', [
                'rebate_amt' => $rebate_amt,
                'spiff_amt' => $spiff_amt,
                'collection_amt' => $collection_amt,
                'limit_amount_to_check' => $limit_amount_to_check
            ]);

            if ($limit_amount_to_check > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return back()->withErrors([
                        'exception' => $ret['error_msg']
                    ])->withInput();
                }

                $net_revenue = $ret['net_revenue'];
            }

            ### TODO: cost ? ###

            $account = Account::find($user->account_id);

            $trans = new Transaction;
            $trans->account_id = $user->account_id;
            $trans->product_id = $denom->product_id;
            $trans->action = $request->port_in == 'Y' ? 'Port-In' : 'Activation';
            $trans->denom = $denom->denom;
            $trans->sim = $request->sim;
            $trans->esn = $esn;
            $trans->npa = $request->npa;
            $trans->address1 = $request->address1;//empty($request->address1) ? $account->address1 : $request->address1;
            $trans->address2 = $request->address2;//empty($request->address2) ? $account->address2 : $request->address2;
            $trans->city = $request->city;//empty($request->city) ? $account->city : $request->city;
            $trans->state = $request->state; //empty($request->state) ? $request->state : $request->state;
            $trans->zip = $request->zip;
            $trans->phone = $request->number_to_port;
            $trans->current_carrier = $current_carrier;
            //$trans->carrier_contract = $request->carrier_contract;
            $trans->account_no = $request->account_no;
            $trans->account_pin = $request->account_pin;
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->call_back_phone = $request->call_back_phone;//empty($request->call_back_phone) ? $account->office_number : $request->call_back_phone;
            $trans->email = $request->email;//empty($request->email) ? $account->email : $request->email;
            $trans->pref_pin = $request->pref_pin;
            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->note = $request->note;
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = empty($request->phone_type) ? '4g' : $request->phone_type;

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            ### ReUP trows error when sending ESN for GSM ###
            if ($vendor_denom->product_id == 'WROKG') {
                $esn = '';
            }

            if ($request->port_in != 'Y') {
                $ret = reup::activation($trans->sim, $esn, $vendor_denom->act_pid, $trans->phone_type,
                    $trans->first_name, $trans->last_name, $trans->address1, $trans->address2,
                    $trans->city, $trans->state, $trans->zip, $trans->npa, $trans->email
                );
            } else {
                $ret = reup::portin($trans->sim, $esn, $vendor_denom->act_pid, $trans->phone_type,
                    $request->current_carrier_id, $trans->phone, $trans->account_no, $trans->account_pin,
                    $trans->first_name, $trans->last_name, $trans->address1, $trans->address2,
                    $trans->city, $trans->state, $trans->zip, $trans->npa, $trans->email
                );
            }

            Helper::log('### ROK API RESULT ###', [
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
            $trans->vendor_tx_id = $ret['tx_id'];
            $trans->mdate = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            ### Consignment SIM / ESN charge for Master & Distributor ###
            if ($sim_type == 'C') {
                $ret = ConsignmentProcessor::charge($trans);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ROK][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            ### ROK might have no SIM for CDMAV / CDMAS
            if (!empty($sim)) {
                $sim->used_trans_id = $trans->id;
                $sim->used_date = Carbon::now();
                $sim->status = 'U';
                $sim->save();
            }

            if (!empty($esn_obj)) {
                $esn_obj->used_trans_id = $trans->id;
                $esn_obj->used_date = Carbon::now();
                $esn_obj->esn_charge = null;
                $esn_obj->esn_rebate = null;
                $esn_obj->status = 'U';
                $esn_obj->save();
            }

            if (!empty($mapping)) {
                $mapping->status = 'U';
                $mapping->save();
            }


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
            if ($request->port_in != 'Y') {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, $trans->phone_type, $trans->sim, $trans->esn, $trans->denom_id);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ROK][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }
            }

            ### rebate ###
            if ($request->port_in != 'Y' && !empty($trans->esn)) {
                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $denom->denom * $rtr_month - $spiff_amt, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ROK][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                }
            }

            ### dispatch RTR ###
            if ($request->port_in != 'Y') {

                $ret = RTRProcessor::applyRTR(
                    1,
                    isset($sim->type) ? $sim->type : '',
                    $trans->id,
                    'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $vendor_denom->denom,
                    $user->user_id,
                    false,
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@jjonbp.com', '[PM][ROK][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        $sim_type,
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
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ROK Activation - applyRTR remaining month failed', $msg);
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
                    'sim_type' => isset($sim) ? $sim->type_name : '',
                    'esn' => $trans->esn,
                    'carrier' => 'ROK',
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
                    'sim_type' => isset($sim) ? $sim->type_name : '',
                    'esn' => $trans->esn,
                    'carrier' => 'ROK',
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
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ])->withInput();
        }
    }
}