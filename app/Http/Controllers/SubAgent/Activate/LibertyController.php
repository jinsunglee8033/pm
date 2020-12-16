<?php
/**
 * Created by Royce
 * Date: 6/22/18
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\liberty;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\gen;
use App\Model\Account;
use App\Model\Denom;
use App\Model\GenActivation;
use App\Model\GenFee;
use App\Model\LbtActivation;
use App\Model\PmModelSimLookup;
use App\Model\Product;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use App\Model\Promotion;
use App\Model\SpiffSetupSpecial;

use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;

use App\Model\Zip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LibertyController
{
    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
              'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_liberty != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Liberty activation. Please contact your distributor'
            ]);
        }

        $ret = Helper::check_parents_product($account->id, 'WLBTA');
        if($ret != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Liberty activation. Please contact your distributor'
            ]);
        }

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
          ->whereIn('product_id', ['WLBTA'])
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
            'product.id as product_id',
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


        $states = State::all();
        return view('sub-agent.activate.liberty')->with([
            'transactions' => $transactions,
            'states'   => $states
        ]);

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $trans->deviceinfo = LbtActivation::where('trans_id', $trans->id)->first();

        $states = State::all();
        return view('sub-agent.activate.liberty')->with([
          'trans' => $trans,
          'states'   => $states
        ]);

        // return self::post($request);

    }

    public function get_portin_form() {

        $states = State::all();

        return view('sub-agent.activate.portin-form-liberty')->with([
            'states'     => $states,
            'product_id' => 'WLBTA'
        ]);
    }

    public function esn(Request $request) {
        try {
            $product_id = ['WLBTA'];
            $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_id)->first();
            if (empty($esn_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WLBTA')->first();
                StockESN::upload_byod($request->esn, 'WLBTA', $p->carrier, $account->id, $account->name);
            }

            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();

            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Liberty Mobile activation is not ready.'
                ]);
            }

            $product_obj = Product::where('id', $product_id)->first();
            if ($product_obj->status !='A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Product is not Active.'
                ]);
            }
            $plans = Array();
            if (empty($esn_obj->amount)) {
                foreach ($denoms as $d) {
                    $plans[] = [
                        'denom_id' => $d->id,
                        'denom' => $d->denom,
                        'name'  => $d->name
                    ];
                }
            } else {
                $ds = explode('|', $esn_obj->amount);
                foreach ($ds as $s) {
                    $denom_tmp = Denom::where('product_id', $product_id)->where('denom', $s)->where('status', 'A')->get();
                    foreach ($denom_tmp as $d) {
                        $plans[] = [
                            'denom_id' => $d->id,
                            'denom' => $d->denom,
                            'name' => $d->name
                        ];
                    }
                }
            }

            $account = Account::find(Auth::user()->account_id);

            foreach ($plans as $p) {
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, null, null);

                $p['spiff'] = $ret_spiff['spiff_amt'];
            }

            $allowed_months = Helper::get_min_month($esn_obj, $account, 'liberty_min_month');

            return response()->json([
                'code'  => '0',
                'plans' => $plans,
                'allowed_months' => $allowed_months,
                'msg'   => ''
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function sim(Request $request) {
        try {
            $sim_obj = StockSIM::where('sim_serial', $request->sim)->where('sim_group', 'Liberty')->first();
            if (empty($sim_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WLBTA')->first();
                StockSim::upload_byos($request->sim, null, 'WLBTA', $p->carrier, $p->sim_group, $account->id, $account->name);
            }
            return response()->json([
                'code'  => '0',
                'msg' => ''
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function commission(Request $request) {
        try {

            $spiff = 0;
            $rebate = 0;
            $product_id = 'WLBTA';
            $p = Product::where('id', $product_id)->first();
            $special_spiffs = null;

            $denom   = Denom::find($request->denom_id);
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();
            $esn_obj = StockESN::where('esn', $request->imei)->where('product', $product_id)->first();

            if (!empty($denom)) {
                $account = Account::find(Auth::user()->account_id);

                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn);
                $spiff = $ret_spiff['spiff_amt'];

                if ($account->rebates_eligibility == 'Y') {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
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
            if($sim_obj) {
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
            }else {
                $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
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

            // $sim_label = $extra_spiff > 0 ? ('Credit $' . ($spiff - $extra_spiff) . ', Extra Spiff $' . $extra_spiff) : 'Credit $' . $spiff;
            $esn_label = (!empty($esn_obj) && (($esn_obj->type == 'P' && $rebate == 0) || $esn_obj->status!= 'A')) ? 'Credit Already Paid' : ($rebate > 0 ? 'Credit $' . number_format($rebate,2) : '');

            return response()->json([
                'code'  => '0',
                'data'  => [
//                    'sim_label'     => $sim_label,
//                    'esn_label'     => $esn_label,
                    'spiff_labels'    => $spiff_labels,
                    'sim_charge'      => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'      => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'      => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'      => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate
                ]
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }

    }


    public function byod(Request $request) {
        try {

            ### API: ValidateBYOD
            $ret = liberty::byod($request->esn);

            if ($ret['code'] !== '1') {

                return response()->json([
                    'code'  => '-1',
                    'msg'   => 'Your device is not available [' . $ret['message'] . ']'
                ]);
            }

            return response()->json([
                'code'  => '0',
                'sim'  => '111111111',
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function getMdnInfo(Request $request) {
        try {
            ### API: get mdn info
            $ret = liberty::getmdninfo($request->mdn);

            $result = $ret['result'];

            if ($result->StatusCode != '1') {

                return response()->json([
                    'code' => '-7',
                    'data' => [
                        'fld'   => 'Your request has been failed. [V]',
                        'msg'   => $result->StatusCodeName . ' [' . $result->StatusCode . ']'
                    ]
                ]);
            }
            return response()->json([
                'code'  => '0',
                'msg'   => ''
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
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

            $v = Validator::make($request->all(), [
                'esn' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'is_port_in' => 'required',
                'denom_id' => 'required',

//                'mdn'           => 'required_if:is_port_in,Y',
                'first_name'    => 'required_if:is_port_in,Y',
                'last_name'     => 'required_if:is_port_in,Y',
                'port_in_mdn'   => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'carrier'       => 'required_if:is_port_in,Y',
                'account_no'    => 'required_if:is_port_in,Y',
                'password'      => 'required_if:is_port_in,Y',
                'street_number' => 'required_if:is_port_in,Y',
                'street_name'   => 'required_if:is_port_in,Y',
                'city'          => 'required_if:is_port_in,Y',
                'state'         => 'required_if:is_port_in,Y',
                'call_back_number' => 'required_if:is_port_in,Y',
                'email'         => 'required_if:is_port_in,Y'
            ], [
                'zip.required' => 'Valid zip code is required',

//                'mdn.required_if'           =>  'MDN is required',
                'first_name.required_if'    => 'First name is required',
                'last_name.required_if'     => 'Last name is required',
                'port_in_mdn.required_if'   => 'Port-in MDN is required',
                'carrier.required_if'       =>  'Carrier is required',
                'account_no.required_if'    => 'Account # is required',
                'password.required_if'      => 'Password is required',
                'street_number.required_if' => 'Street number is required',
                'street_name.required_if'   => 'Street name is required',
                'city.required_if'          => 'City is required',
                'state.required_if'         => 'State is required',
                'call_back_number.required_if' => 'Call back number # is required',
                'email.required_if'         => 'Email is required'
            ]);

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

            $denom = Denom::find($request->denom_id);
            if (empty($denom) || $denom->status != 'A') {
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Invalid denomination provided.]'
                  ]
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Your session has been expired.]'
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

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[The product is not available.]'
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
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Vendor configuration incomplete.]'
                  ]
                ]);
            }

            $sim_obj = null;
            $esn_obj = null;

            $esn_obj = StockESN::where('esn', $request->esn)->where('product', 'WLBTA')->first();

            if (!empty($esn_obj)) {

                if (!empty($esn_obj->amount) && $esn_obj->product != 'WLBTA') {
                    return response()->json([
                      'code' => '-2',
                      'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Please enter valid device id.]'
                      ]
                    ]);
                }

                if (!empty($esn_obj->amount) && !in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    return response()->json([
                      'code' => '-2',
                      'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Plan $' . $denom->denom . ' is not allowed to the device]'
                      ]
                    ]);
                }

                ### check owner path ###
                if (!empty($esn_obj->owner_id)) {
                    $owner = Account::where('id', $esn_obj->owner_id)
                      ->whereRaw("? like concat(path, '%')", [$account->path])
                      ->first();
                    if (empty($owner)) {
                        return response()->json([
                          'code' => '-2',
                          'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg' => '[ESN is not available. Not valid owner.]'
                          ]
                        ]);
                    }
                }
            }

            if (!empty($request->sim)) {

                ### Liberty Sim Reuse 6/12/2020 ###
                $sim_obj = StockSim::where('sim_serial', $request->sim)
                    ->where('sim_group', $product->sim_group)
//                    ->where('status', 'A')
                    ->first();

                if (empty($sim_obj)) {
                    return response()->json([
                      'code' => '-2',
                      'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Please enter valid SIM.]'
                      ]
                    ]);
                } else {

                    ### check owner path ###
                    if (!empty($sim_obj->owner_id)) {
                        $owner = Account::where('id', $sim_obj->owner_id)
                          ->whereRaw("? like concat(path, '%')", [$account->path])
                          ->first();
                        if (empty($owner)) {
                            return response()->json([
                              'code' => '-2',
                              'data' => [
                                'fld'   => 'Your request has been failed.',
                                'msg' => '[SIM is not available. Not valid owner.]'
                              ]
                            ]);
                        }
                    }
                }
            }

            ### fee ###
            $rtr_month  = $request->rtr_month;

            $rtr_discount   = 0;

            ### Act/Recharge Fee by products, not by accounts (7/24/19)  ###
            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

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
                $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                $rebate_amt = $ret_rebate['rebate_amt'];
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn);
            $spiff_amt = $ret_spiff['spiff_amt'];

            ### Special Spiff
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs('WLBTA', $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj);
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

            if ($limit_amount_to_check + $fee + $pm_fee> 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                      'code' => '-3',
                      'data' => [
                        'fld'   => 'Your request has been failed.',
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
            $trans->esn = $request->esn;
            $trans->zip = $request->zip;

            if ($request->is_port_in == 'Y'){
                $trans->phone = $request->port_in_mdn;
                $trans->first_name = $request->first_name;
                $trans->last_name = $request->last_name;
                $trans->current_carrier = $request->carrier;
                $trans->account_no = $request->account_no;
                $trans->account_pin = $request->password;
                $trans->address1 = $request->street_number;
                $trans->address2 = $request->street_name;
                $trans->account_city = $request->city;
                $trans->account_state = $request->state;
                $trans->account_zip = $request->portin_zip;
                $trans->call_back_phone = $request->call_back_number;
                $trans->email = $request->email;
            }
            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = '';

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            $params = new \stdClass();
            $params->trans_id = $trans->id;
            $params->zip = $request->zip;
            $params->esn = $request->esn;
            $params->sim = $request->sim;
            $params->act_pid = $vendor_denom->act_pid;

            if ($request->is_port_in != 'Y') {
                // Activate($params)
                $ret = liberty::serviceActivation($params);

            } else {

                $params->mdn            = $request->call_back_number; // MDN <= Call Back Number
                $params->first_name     = $request->first_name;
                $params->last_name      = $request->last_name;
                $params->port_in_mdn    = $request->port_in_mdn;
                $params->carrier        = $request->carrier;
                $params->account_no     = $request->account_no;
                $params->password       = $request->password;
                $params->street_number  = $request->street_number;
                $params->street_name    = $request->street_name;
                $params->city           = $request->city;
                $params->state          = $request->state;
                $params->portin_zip     = $request->portin_zip;
                $params->call_back_number = $request->call_back_number;
                $params->email          = $request->email;

                // Portin($params)
                $ret = liberty::Portin($params);
            }

            Helper::log('### Liberty API RESULT ###', [
              'ret' => $ret
            ]);

            $result = $ret['result'];

            if ($result->StatusCode != '1') {

                $trans->status = 'F';
                $trans->note = $result->StatusCodeName . '[' . $result->StatusCode . ']' . $result->Details[0];
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                return response()->json([
                  'code' => '-7',
                  'data' => [
                    'fld'   => 'Your request has been failed. [V]',
                    'msg'   => $result->StatusCodeName . ' [' . $result->StatusCode . ']' . $result->Details[0]
                  ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $result->StatusCodeName;
            $trans->phone   = $request->is_port_in == 'Y' ? $request->number_to_number : $result->mdn;
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

                    //Helper::send_mail('it@perfectmobileinc.com', '[PM][LBT][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                    Helper::send_mail('it@jjonbp.com', '[PM][LBT][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            ### Update ESN status
            if (!empty($esn_obj)) {
                StockESN::where('esn', $esn_obj->esn)
                  ->update([
                    'used_trans_id' => $trans->id,
                    'used_date'     => Carbon::now(),
                    'esn_charge'    => null,
                    'esn_rebate'    => null,
                    'status'        => 'U'
                  ]);
            }

            ### Update Sim status
            if (!empty($sim_obj)) {
                StockSim::where('sim_serial', $sim_obj->sim_serial)
                  ->update([
                    'used_trans_id' => $trans->id,
                    'used_date'     => Carbon::now(),
                    'product'       => $denom->product_id,
                    'status'        => 'U'
                  ]);

                $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $denom->product_id)->where('status', 'A')->first();
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

//                    Helper::send_mail('it@perfectmobileinc.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                    Helper::send_mail('it@jjonbp.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                    if (!empty($ret['error_msg'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
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
                  true,
                  null,
                  1,
                  $vendor_denom->fee,
                  $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][LBT][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WLBTA'){
                        $rtr_product_id = 'WLBTAR';
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
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] LBT Activation - applyRTR remaining month failed', $msg);
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
                'fld'   => 'Your request has been failed. [EXP]',
                'msg'   => $ex->getMessage() . ' [' . $ex->getCode() . ']'
              ]
            ]);
        }
    }


    public function invoice($id) {
        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();

        return view('sub-agent.activate.invoice')->with([
          'trans' => $trans
        ]);
    }
}