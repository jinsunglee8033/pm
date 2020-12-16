<?php
/**
 * Created by Royce
 * Date: 6/22/18
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\gss;
use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use App\Model\Promotion;
use App\Model\SpiffSetupSpecial;

use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;
use App\Model\ATTTID;
use App\Model\ATTTIDLog;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ATTDataOnlyController
{
    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

//        if ($account->act_att != 'Y') {
//            return redirect('/sub-agent/att-error')->with([
//                'error_msg' => 'Your account is not authorized to do ATT Mobile activation. Please contact your distributor'
//            ]);
//        }
//
//        $tid = Helper::check_att_tid($account);
//        if (empty($tid)) {
//            return redirect('/sub-agent/error')->with([
//                'error_msg' => 'Your account is not authorized to do ATT Mobile activation. Please contact your distributor'
//            ]);
//        }

        $query = Transaction::join('product', 'transaction.product_id', 'product.id')
            ->join("accounts", 'transaction.account_id', 'accounts.id')
            ->join("accounts as master", "accounts.master_id", "master.id")
            ->leftjoin("stock_sim", function($join) {
                  $join->on('transaction.sim', 'stock_sim.sim_serial')
                    ->where('transaction.product_id', 'stock_sim.product');
              })
            ->Leftjoin("accounts as dist", function($join) {
                $join->on('accounts.parent_id', 'dist.id')
                    ->where('dist.type', 'D');
            });

        $transactions = $query->where('transaction.account_id', Auth::user()->account_id)
            ->where('product_id', 'WATTDO')
            ->whereIn('action', ['Activation', 'Port-In'])
            ->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
            ->select(
                'transaction.id',
                \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                'master.id as master_id',
                'master.name as master_name',
                'dist.id as dist_id',
                'dist.name as dist_name',
                'accounts.id as account_id',
                'accounts.type as account_type',
                'accounts.name as account_name',
                'product.carrier',
                'product.name as product_name',
                'transaction.denom',
                'transaction.rtr_month',
                'transaction.collection_amt',
                'transaction.fee',
                'transaction.pm_fee',
                'transaction.net_revenue',
                'transaction.action',
                'transaction.api',
                'transaction.sim',
                'transaction.esn',
                'transaction.npa',
                'transaction.phone',
                'transaction.pin',
                'accounts.loc_id',
                'accounts.outlet_id',
                'accounts.state as loc_state',
                \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                'transaction.dc',
                'transaction.dp',
                'transaction.status',
                \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
                'transaction.created_by',
                'transaction.cdate',
                'transaction.mdate',
                \DB::raw("case stock_sim.type when 'R' then 'Regular' when 'P' then 'Wallet' when 'B' then 'Bundle' when 'C' then 'Consignment' else '' end as sim_type")
            )->limit(10)->get();

        return view('sub-agent.activate.attdataonly')->with([
            'transactions' => $transactions,
            'account' => $account
        ]);

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $account = Account::find($trans->account_id);

        return view('sub-agent.activate.attdataonly')->with([
            'trans' => $trans,
            'account' => $account
        ]);

        // return self::post($request);

    }

    public function sim(Request $request) {
        try {
            $v = Validator::make($request->all(), [
              'sim' => 'required|regex:/^\d{20}$/'
            ], [
              'sim.regex' => 'The SIM format is invalid or (less than 20 digits)',
            ]);

            if ($v->fails()) {
                $errors = '';
                foreach ($v->errors()->messages() as $key => $value) {
                    $errors  .= ','. $value[0];
                };

                return response()->json([
                  'code' => '-1',
                  'msg' => substr($errors, 1)
                ]);
            }

            $product_id = 'WATTDO';
            $p = Product::where('id', $product_id)->first();
            $account = Account::find(Auth::user()->account_id);

            $result = Helper::check_threshold_limit_by_account($account->id);
            if ($result['code'] != '0') {
                return response()->json($result);
            }

            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            if (empty($sim_obj) ) {
                //Do not allow BYOS from 01/01/2020.
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Invalid SIM. ATT activation requires SIM # to be registered on our system.'
                ]);
//                if ($account->att_allow_byos == 'Y') {
//                    $sim_obj = StockSim::upload_byos($request->sim, null, $product_id, 'ATT', 'ATT');
//                } else {
//                    return response()->json([
//                        'code' => '-2',
//                        'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
//                    ]);
//                }
            } else {
                if ($sim_obj->status !== 'A') {
                    return response()->json([
                        'code' => '-2',
                        'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                    ]);
                }

                ### check owner path ###
                if (!empty($sim_obj->owner_id)) {
                    $owner = Account::where('id', $sim_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return response()->json([
                            'code' => '-2',
                            'msg' => 'SIM is not available. Not valid owner.'
                        ]);
                    }
                }
            }

            $mapping = StockMapping::where('product', $product_id)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();

            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'ATT activation is not ready.'
                ]);
            }

            $plans = Array();
            if (empty($sim_obj->amount)) {
                foreach ($denoms as $d) {
                    $plans[] = [
                        'denom_id' => $d->id,
                        'denom' => $d->denom,
                        'name'  => $d->name
                    ];
                }
            } else {
                $ds = explode('|', $sim_obj->amount);
                foreach ($ds as $s) {
                    $denom_tmp = Denom::where('product_id', $product_id)->where('denom', $s)->where('status', 'A')->first();
                    if (!empty($denom_tmp)) {
                        $plans[] = [
                            'denom_id' => $denom_tmp->id,
                            'denom' => $denom_tmp->denom,
                            'name'  => $denom_tmp->name
                        ];
                    }
                }
            }

            foreach ($plans as $p) {
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, $request->sim, null);

                $p['spiff'] = $ret_spiff['spiff_amt'];
            }

            if ($sim_obj->is_byos == 'Y' && !empty($account->att_byos_act_month)) {
                $allowed_months = array();
                $allowed_months[] = $account->att_byos_act_month;
                for($i = $account->att_byos_act_month + 1; $i <= 12; $i++) {
                    $allowed_months[] = $i;
                }
            } else {
                $allowed_months = explode('|', empty($sim_obj->rtr_month) ? '1|2|3|4|5|6|7|8|9|10|11|12' : $sim_obj->rtr_month);
            }

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim'       => $sim_obj->sim_serial,
                    'imei'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim_obj->sub_carrier,
                    'product_id' => $product_id,
                    'plans'     => $plans,
                    'allowed_months' => $allowed_months,
                    'sim_charge' => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate' => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'sim_consignment_charge' => $sim_obj->type == 'C' ? (empty($sim_obj->charge_amount_r) ? 0 : $sim_obj->charge_amount_r) : 0
                ]
            ]);

        } catch (\Exception $ex) {   
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
        
    }

    public function esn(Request $request) {
        try {
            $product_id = 'WATTDO';

            $esn_obj = StockESN::where('esn', $request->esn)->where('product', $product_id)->first();

            if (empty($esn_obj)) {
                return response()->json([
                    'code'  => '0',
                    'sub_carrier' => ''
                ]);
            }

            $esn_mappings = StockMapping::where('product', $product_id)->where('esn', $request->esn)->where('status', 'A')->count();
            if ($esn_mappings > 0) {
                $mapping = StockMapping::where('product', $product_id)->where('esn', $request->esn)->where('sim', $request->sim)->where('status', 'A')->first();

                if (empty($mapping)) {
                    return response()->json([
                        'code' => '-2',
                        'sub_carrier' => $esn_obj->sub_carrier,
                        'msg' => 'SIM [' . $request->sim . '] can not activate the device [' . $request->esn . '] !!'
                    ]);
                }
            }

            return response()->json([
                'code'  => '0',
                'sub_carrier' => $esn_obj->sub_carrier,
            ]);

        } catch (\Exception $ex) {   
            return response()->json([
                'code'  => '-9',
                'sub_carrier' => '',
                'msg'   => $ex->getMessage()
            ]);
        }
        
    }

    public function commission(Request $request) {
        try {

            $spiff = 0;
            $rebate = 0;
            $product_id = 'WATTDO';
            $p = Product::where('id', $product_id)->first();
            $special_spiffs = null;

            $denom   = Denom::find($request->denom_id);
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();
            $esn_obj = StockESN::where('esn', $request->imei)->where('product', $product_id)->first();

            if (!empty($denom)) {
                $account = Account::find(Auth::user()->account_id);

                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->imei);
                $spiff = $ret_spiff['spiff_amt'];

                if ($account->rebates_eligibility == 'Y') {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->imei);
                    $rebate = $ret_rebate['rebate_amt'];

                }

                if (empty($request->sim)) {
                    $rebate += $spiff;
                }
                ### Special Spiff
                $terms = array();
                if ($request->is_port_in == 'Y') {
                    $terms[] = 'Port';
                }
                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                    $product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj, $terms
                );
            }

            $spiff_labels = Array();
            $sim_label = '';
            $esn_label = '';

            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);

            if(!empty($sim_obj)) {

                if ($sim_obj->type == 'P') {
                    if ($spiff == 0) {
                        $sim_label = 'Credit Already Paid' . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');

                        $spiff_labels[] = 'Already Paid';

                    } else {
                        $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');

                        $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                    }
                } else {
                    $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');

                    $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                }
            }

            if ($extra_spiff > 0) {

                $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';

            }

            if (!empty($special_spiffs)) {

                foreach ($special_spiffs as $ss) {
                    $sim_label .= ', ' . $ss['name'] . ' $' . number_format($ss['spiff'], 2);

                    $spiff_labels[] = '$ ' . number_format($ss['spiff'], 2) . ', ' . $ss['name'];
                }
            }

            if(!$spiff_labels) {
                $spiff_labels[] = '$0.00';
            }

            // $sim_label = $extra_spiff > 0 ? ('Credit $' . ($spiff - $extra_spiff) . ', Extra Spiff $' . $extra_spiff) : 'Credit $' . $spiff;
            $esn_label = (!empty($esn_obj) && (($esn_obj->type == 'P' && $rebate == 0) || $esn_obj->status!= 'A')) ? 'Credit Already Paid' : ($rebate > 0 ? 'Credit $' . number_format($rebate,2) : '');

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim_label'     => $sim_label,
                    'esn_label'     => $esn_label,
                    'spiff_labels'  => $spiff_labels,
                    'esn_charge'    => empty($esn_obj) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'    => empty($esn_obj) ? 0 : $esn_obj->esn_rebate
                ]
            ]);

        } catch (\Exception $ex) {   
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }

    }

    public function get_portin_form() {

        $states = State::all();

        return view('sub-agent.activate.portin-form')->with([
            'states'     => $states
        ]);
    }

    public function post(Request $request) {
        try {

            if (Helper::is_login_as()) {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Login as is not allowed to do activation.'
                  ]
                ]);
            }

            // $request->afcode         : afcode
            // $request->sim            : sim
            // $request->esn            : esn
            // $request->handset_os     : handset_os
            // $request->imei           : imei
            // $request->zip_code       : zip_code
            // $request->sub_carrier    : sub_carrier
            // $request->denom_id       : denom_id
            // $request->is_port_in     : 'Y'
            // $request->equipment_type : equipment_type
            // $request->number_to_port : number_to_port
            // $request->account_no     : account_no
            // $request->account_pin    : account_pin
            // $request->first_name     : first_name
            // $request->last_name      : last_name
            // $request->address1       : address1
            // $request->address2       : address2
            // $request->city           : city
            // $request->state          : state
            // $request->call_back_phone: call_back_phone
            // $request->email          : email


            // if (Helper::is_login_as()) {
            //     return back()->withInput()->withErrors([
            //         'exception' => 'We are sorry. Login as user is not allowed to make any transaction'
            //     ]);
            // }

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'rtr_month' => 'required',
                'sim' => 'required|regex:/^\d{20}$/',
                'zip_code' => 'required|regex:/^\d{5}$/',
                'area_code' => 'required|regex:/^\d{3}$/',

                'number_to_port' => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'account_no' => 'required_if:is_port_in,Y',
                'account_pin' => 'required_if:is_port_in,Y',
                'first_name' => 'required_if:is_port_in,Y',
                'last_name' => 'required_if:is_port_in,Y',
                'address1' => 'required_if:is_port_in,Y',
                'address2' => 'required_if:is_port_in,Y',
                'city' => 'required_if:is_port_in,Y',
                'state' => 'required_if:is_port_in,Y',
                'call_back_phone' => 'required_if:is_port_in,Y',
                'email' => 'required_if:is_port_in,Y',
            ], [
                'sim.regex' => 'The SIM format is invalid or (less than 20 digits)',
                'denom_id.required' => 'Please select product',
                'rtr_month.required' => 'Please select activation month',
                'zip_code.required' => 'Valid zip code is required',

                'number_to_port.required_if' => 'Port-in number is required',
                'account_no.required_if' => 'Account # is required',
                'account_pin.required_if' => 'Account PIN is required',
                'first_name.required_if' => 'First name is required',
                'last_name.required_if' => 'Last name is required',
                'address1.required_if' => 'Street number is required',
                'address2.required_if' => 'Street name is required',
                'city.required_if' => 'City is required',
                'state.required_if' => 'State is required',
                'call_back_phone.required_if' => 'Call back phone # is required',
                'email.required_if' => 'Email is required'
            ]);

            $product_id = 'WATTDO';

            if ($v->fails()) {
                $errors = Array();
                foreach ($v->errors()->messages() as $key => $value) {
                    $errors[] = [
                        'fld'   => $key,
                        'msg'   => $value[0]
                    ];
                };

                return response()->json([
                    'code' => '-1',
                    'data' => $errors
                ]);
            }

            $account = Account::find(Auth::user()->account_id);
            if (empty($account)) {
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Your session has been expired.'
                    ]
                ]);
            }

            $result = Helper::check_threshold_limit_by_account($account->id);
            if ($result['code'] != '0') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'    => 'threshold',
                        'msg'    => $result['msg']
                    ]
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom) || $denom->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'denom_id',
                        'msg'   => 'Invalid denomination provided.'
                    ]
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'The product is not available.'
                    ]
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom) || empty($vendor_denom->act_pid)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Vendor configuration incomplete.'
                    ]
                ]);
            }

            if ($account->act_att != 'Y') {
                return redirect('/sub-agent/att-error')->with([
                    'error_msg' => 'Your account is not authorized to do ATT Mobile activation. (no act)
                 Please contact your distributor'
                ]);
            }

            $tid = Helper::get_att_tid($account);
            if (empty($tid)) {
                return redirect('/sub-agent/error')->with([
                    'error_msg' => 'Your account is not authorized to do ATT Mobile activation. Please contact your distributor'
                ]);
            }

            $sim_obj = null;
            $esn_obj = null;

            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $product->sim_group)->where('status', 'A')->first();
            if (empty($sim_obj)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'Please enter valid SIM.'
                    ]
                ]);
            } else {
                if (!empty($sim_obj->amount) && $sim_obj->amount != $denom->denom) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'sim',
                            'msg'   => 'Please enter valid SIM.'
                        ]
                    ]);
                }

                ### check owner path ###
                if (!empty($sim_obj->owner_id)) {
                    $owner = Account::where('id', $sim_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return response()->json([
                            'code' => '-2',
                            'data' => [
                                'fld'   => 'sim',
                                'msg' => 'SIM is not available. Not valid owner.'
                            ]
                        ]);
                    }
                }
            }

            $allowed_months = explode('|', empty($sim_obj->rtr_month) ? '1|2|3|4|5|6|7|8|9|10|11|12' : $sim_obj->rtr_month);
            if (!in_array($request->rtr_month, $allowed_months)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'denom_id',
                        'msg'   => 'Invalid denomination provided. SIM[' . $sim_obj->rtr_month . ']'
                    ]
                ]);
            }

            $esn_obj = StockESN::where('esn', $request->imei)->where('product', $product_id)->first();

            if (!empty($esn_obj)) {
                if (!empty($esn_obj->amount) && !in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'imei',
                            'msg'   => 'Plan $' . $denom->denom . ' is not allowed to the IMEI'
                        ]
                    ]);
                }


                $allowed_months = explode('|', empty($esn_obj->rtr_month) ? '1|2|3|4|5|6|7|8|9|10|11|12' : $esn_obj->rtr_month);
                if (!in_array($request->rtr_month, $allowed_months)) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'denom_id',
                            'msg'   => 'Invalid denomination provided. IMEI[' . $esn_obj->rtr_month . ']'
                        ]
                    ]);
                }
            }

            $user = Auth::user();

            ### fee ###
            $rtr_month  = $request->rtr_month;

            ### get_rtr_discount_amount($account, $product_id, $denom_amt, $rtr_month)
            $discount_obj   = PaymentProcessor::get_rtr_discount_amount($account, 'WATTDO', $denom->denom, $rtr_month - 1);
            Helper::log(' ### get_rtr_discount_amount result ###', $discount_obj);
            $fee        = $discount_obj['fee'];
            $pm_fee     = $discount_obj['pm_fee'];
            $rtr_discount   = 0;

            ### get collection amount ###
            $collection_amt =  $denom->denom * $rtr_month - $rtr_discount;

            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            switch (substr($sim_type, 0, 1)) {
                case 'P':
                    $collection_amt = 0;
                    break;
                case 'C':
                    ### collection amount = charge.amount.r of SIM / ESN ###
                    $collection_amt = $collection_amt + StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'S');
                    break;
                case 'X':
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => 'Unable to determine SIM type.'
                        ]
                    ]);
            }

            ### check sales limit ###
            $net_revenue = 0;
            $rebate_amt = 0;
            if ($account->rebates_eligibility == 'Y') {
                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->imei);
                $rebate_amt = $ret_rebate['rebate_amt'];
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->imei);
            $spiff_amt = $ret_spiff['spiff_amt'];

            ### CHECK Parent Spiff ###
//            $p_account = Account::find($account->parent_id);
//            $p_ret_spiff = SpiffProcessor::get_account_spiff_amt($p_account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->imei);
//            $p_spiff_amt = $p_ret_spiff['spiff_amt'];
//            if ($spiff_amt > $p_spiff_amt) {
//                return response()->json([
//                    'code' => '-2',
//                    'data' => [
//                        'fld'   => 'exception',
//                        'msg'   => 'Unable to activate the product. Please contact customer service.'
//                    ]
//                ]);
//            }
//
//            ### CHECK Master Spiff ###
//            if ($p_account->type == 'D') {
//                $m_account = Account::find($p_account->parent_id);
//                $m_ret_spiff = SpiffProcessor::get_account_spiff_amt($m_account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->imei);
//                $m_spiff_amt = $m_ret_spiff['spiff_amt'];
//                if ($p_spiff_amt > $m_spiff_amt) {
//                    return response()->json([
//                      'code' => '-2',
//                      'data' => [
//                        'fld'   => 'exception',
//                        'msg'   => 'Unable to activate the product. Please contact customer service.'
//                      ]
//                    ]);
//                }
//            }

            ### Special Spiff
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            ### sim/esn of recharge/rebate ###
            $sim_recharge   = 0;
            $sim_rebate     = 0;
            $esn_recharge   = 0;
            $esn_rebate     = 0;

            if(!empty($sim_obj)){
                $sim_recharge   = $sim_obj->sim_charge;
                $sim_rebate     = $sim_obj->sim_rebate;
            }

            if(!empty($esn_obj)){
                $esn_recharge   = $esn_obj->esn_charge;
                $esn_rebate     = $esn_obj->esn_rebate;
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt - $sim_recharge + $sim_rebate - $esn_recharge + $esn_rebate;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'code' => '-3',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => $ret['error_msg']
                        ]
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            $trans = new Transaction;
            $trans->account_id = $user->account_id;
            $trans->product_id = $denom->product_id;
            $trans->action = $request->is_port_in == 'Y' ? 'Port-In' : 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->imei;
            $trans->zip = $request->zip_code;
            $trans->npa = $request->area_code;

            if ($request->is_port_in == 'Y'){
                $trans->note = $request->note;
                $trans->phone = $request->number_to_port;
                $trans->current_carrier = $request->current_carrier;
                $trans->account_no = $request->account_no;
                $trans->account_pin = $request->account_pin;
                $trans->first_name = $request->first_name;
                $trans->last_name = $request->last_name;
                $trans->address1 = $request->address1;
                $trans->address2 = $request->address2;
                $trans->city = $request->city;
                $trans->state = $request->state;
                $trans->call_back_phone = $request->call_back_phone;
                $trans->email = $request->email;

            }
            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = empty($request->sim) ? '3g' : '4g';

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            ### ATT TID LOG
            $tid_log = new ATTTIDLog();
            $tid_log->code = $tid;
            $tid_log->trans_id = $trans->id;
            $tid_log->account_id = $trans->account_id;
            $tid_log->cdate = Carbon::now();
            $tid_log->save();

            if ($request->is_port_in != 'Y') {
                // ActivatePhone($trans_id, $pid, $sim, $imei, $zip, $area_code, $tid)
                $ret = gss::ActivatePhone($trans->id, $vendor_denom->act_pid, $request->sim, $request->imei,  
                    $request->zip_code, $request->area_code, $tid);
            } else {
                // PortPhone($trans_id, $mdn, $pid, $sim, $imei, $zip, $area_code, $tid, $first_name, $last_name, $city, $state, $street_number, $street_name, $account_no, $pin)
                $ret = gss::PortPhone($trans->id, $request->number_to_port, $vendor_denom->act_pid, $request->sim, $request->imei, $request->zip_code, '', $tid, $request->first_name, $request->last_name, $request->city, $request->state, $request->address1, $request->address2, $request->account_no, $request->account_pin);
            }

            Helper::log('### GSS API RESULT ###', [
                'ret' => $ret
            ]);


            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                return response()->json([
                    'code' => '-7',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $ret['error_msg'];
            $trans->phone   = $request->is_port_in == 'Y' ? $request->number_to_port : $ret['mdn'];
            $trans->vendor_tx_id = $request->is_port_in == 'Y' ? $ret['req_number'] : '';
            $trans->mdate   = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            ### Consignment Charge ###
            if ($sim_type == 'C') {
                $ret = ConsignmentProcessor::charge($trans);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][AT&T][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            ### Update Sim status
            StockSim::where('sim_serial', $sim_obj->sim_serial)
              ->update([
                'used_trans_id' => $trans->id,
                'product'       => $denom->product_id,
                'used_date'     => Carbon::now(),
                'status'        => 'U'
              ]);

            if (!empty($esn_obj)) {
                StockESN::where('esn', $request->imei)->where('product', $product_id)
                    ->update([
                      'used_trans_id' => $trans->id,
                      'used_date'     => Carbon::now(),
                      'status'        => 'U'
                    ]);

                $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $product_id)->where('status', 'A')->first();
                if (!empty($mapping)) {
                    $mapping->status = 'U';
                    $mapping->update();
                }
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            if ($request->is_port_in != 'Y') {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### ATT Scheduling Availability
                if ($trans->rtr_month > 1) {
                    \App\Model\ATTBatchMDNAvailability::create_availability($trans->account_id, $trans->phone, Carbon::today(), $trans->rtr_month);
                }

                ### rebate ###
                if (!empty($trans->esn)) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                    if (!empty($ret['error_msg'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                        Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                    }
                }

                $ret = RTRProcessor::applyRTR(
                    1,
                    isset($sim_type) ? $sim_type : '',
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
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WATTA'){
                        $rtr_product_id = 'WATTR';
                    }elseif($trans->product_id == 'WATTPVA'){
                        $rtr_product_id = 'WATTPVR';
                    }else{
                        $rtr_product_id = $trans->product_id;
                    }
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        $sim_type,
                        $trans->id,
                        'House',
                        $trans->phone,
                        $rtr_product_id,
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
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ATT Activation - applyRTR remaining month failed', $msg);
                    }
                }
            }

            ### update balance ###
            Helper::update_balance();

            Helper::log('### API call completed ###');

            
            return response()->json([
                'code' => '0',
                'data' => [
                    'id'    => $trans->id,
                    'mdn'   => $trans->phone,
                    'msg'   => $trans->note
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'data' => [
                    'fld'   => 'exception',
                    'msg'   => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
                ]
            ]);
        }
    } 


    public function invoice($id) {
        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $account = Account::find(Auth::user()->account_id);

        return view('sub-agent.activate.invoice')->with([
            'trans' => $trans,
            'account' => $account
        ]);
    }
}