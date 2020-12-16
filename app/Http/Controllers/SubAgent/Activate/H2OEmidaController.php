<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 11/12/18
 * Time: 1:25 PM
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Lib\ConsignmentProcessor;
use App\Lib\emida2;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\emida;
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

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class H2OEmidaController
{
    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
              'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_h2o != 'Y') {
            return redirect('/sub-agent/error')->with([
              'error_msg' => 'Your account is not authorized to do H2O Mobile activation. Please contact your distributor'
            ]);
        }

        $ret = Helper::check_parents_product($account->id, 'WH2OM');
        if($ret != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do H2O Mobile activation. Please contact your distributor'
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
          ->whereIn('product_id', ['WH2OM', 'WH2OP'])
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

        return view('sub-agent.activate.h2oemida')->with([
            'transactions'  => $transactions,
            'account'       => $account
        ]);

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $account = Account::find(Auth::user()->account_id);

        return view('sub-agent.activate.h2oemida')->with([
            'trans' => $trans,
            'account' => $account
        ]);

        // return self::post($request);

    }

    public function sim(Request $request, $type) {
        try {
            $v = Validator::make($request->all(), [
              'product_id' => 'required',
              'code' => 'required'
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

            $product_id = $request->product_id;
            $product_obj = Product::where('id', $product_id)->first();
            if ($product_obj->status !='A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Product is not Active.'
                ]);
            }
            $sim_group = $product_obj->sim_group;
            $account = Account::find(Auth::user()->account_id);

            $query = StockSim::query();
            if ($type == 'sim') {
                $query->where('sim_serial', $request->code);
            } else {
//                $query->where('afcode', $request->code);
            }

            $sim_obj = $query->where('sim_group', $sim_group)->first();

            //  BYOS is not Allow in H2O
            if (empty($sim_obj) ) {
                return response()->json([
                  'code' => '-2',
                  'msg' => 'SIM is not available, Not in System'
                ]);
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
                  'msg' => 'H2O activation is not ready.'
                ]);
            }

            $plans = Array();
            if (empty($sim_obj->amount)) {
                foreach ($denoms as $d) {
                    $v_denom = VendorDenom::where('product_id', $product_id)
                        ->where('vendor_code',$product_obj->vendor_code)
                        ->where('denom_id', $d->id)
                        ->where('status', 'A')
                        ->first();
                    if(!empty($v_denom->act_pid)) {
                        $plans[] = [
                            'denom_id' => $d->id,
                            'denom' => $d->denom,
                            'name' => $d->name
                        ];
                    }
                }
            } else {
                $ds = explode('|', $sim_obj->amount);
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

            foreach ($plans as $p) {
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, $request->sim, null);

                $p['spiff'] = $ret_spiff['spiff_amt'];
            }

            $allowed_months = Helper::get_min_month($sim_obj, $account, 'h2o_min_month');

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
            $v = Validator::make($request->all(), [
              'product_id' => 'required',
              'sim' => 'required|regex:/^\d{20}$/',
              'esn' => 'required'
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

            $product_id = $request->product_id;

            $esn_obj = StockESN::where('esn', $request->esn)->where('product', $product_id)->first();

            if (empty($esn_obj)) {
                return response()->json([
                  'code'  => '0',
                  'sub_carrier' => ''
                ]);
            }

            $esn_mappings = StockMapping::where('product', $product_id)
                ->where('esn', $request->esn)
                ->where('status', 'A')
                ->count();

            if ($esn_mappings > 0) {
                $mapping = StockMapping::where('product', $product_id)
                    ->where('esn', $request->esn)
                    ->where('sim', $request->sim)
                    ->where('status', 'A')
                    ->count();

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
            $v = Validator::make($request->all(), [
              'product_id' => 'required',
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

            $spiff = 0;
            $rebate = 0;
            $product_id = $request->product_id;
            $special_spiffs = null;

            $denom   = Denom::find($request->denom_id);
            $p = Product::where('id', $product_id)->first();
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

            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
            if ($sim_obj->type == 'P') {
                if ($spiff == 0) {
                    $sim_label = 'Credit Already Paid' . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff,2) : '');
                    $spiff_labels[] = 'Already Paid';
                } else {
                    $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                    $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                }
            } else {
                $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
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
                'sim_label'   => $sim_label,
                'esn_label'   => $esn_label,
                'spiff_labels' => $spiff_labels
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

        return view('sub-agent.activate.portin-form-h2oe')->with([
          'states'     => $states
        ]);
    }

    public function post(Request $request) {
        try {
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
                'product_id'  => 'required',
                'denom_id'    => 'required',
                'rtr_month'   => 'required',
                'sim'         => 'required|regex:/^\d{20}$/',
                'zip_code'    => 'required|regex:/^\d{5}$/',
                'area_code'   => 'required|regex:/^\d{3}$/',

                'imei'            => 'required_if:is_port_in,Y',
                'number_to_port'  => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'old_service_provider' => 'required_if:is_port_in,Y',
                'cell_number_contract' => 'required_if:is_port_in,Y',
                'account_no'      => 'required_if:is_port_in,Y',
                'account_pin'     => 'required_if:is_port_in,Y',
                'first_name'      => 'required_if:is_port_in,Y',
                'last_name'       => 'required_if:is_port_in,Y',
                'address1'        => 'required_if:is_port_in,Y',
                'address2'        => 'required_if:is_port_in,Y',
                'city'            => 'required_if:is_port_in,Y',
                'state'           => 'required_if:is_port_in,Y',
                'call_back_phone' => 'required_if:is_port_in,Y',
                'email'           => 'required_if:is_port_in,Y',

            ], [
              'sim.regex'           => 'The SIM format is invalid or (less than 20 digits)',
              'denom_id.required'   => 'Please select product',
              'rtr_month.required'  => 'Please select activation month',
              'zip_code.required'   => 'Valid zip code is required',

              'number_to_port.required_if'  => 'Port-in number is required',
              'old_service_provider.required_if' => 'Old Service Provider is required',
              'cell_number_contract.required_if' => 'Cell Number Contract is required',
              'account_no.required_if'      => 'Account # is required',
              'account_pin.required_if'     => 'Account PIN is required',
              'first_name.required_if'      => 'First name is required',
              'last_name.required_if'       => 'Last name is required',
              'address1.required_if'        => 'Street number is required',
              'address2.required_if'        => 'Street name is required',
              'city.required_if'            => 'City is required',
              'state.required_if'           => 'State is required',
              'call_back_phone.required_if' => 'Call back phone # is required',
              'email.required_if'           => 'Email is required'
            ]);

            $product_id = $request->product_id;

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
            if (empty($vendor_denom)) {
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Vendor configuration incomplete.'
                  ]
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
            $discount_obj   = PaymentProcessor::get_rtr_discount_amount($account, $product_id, $denom->denom, $rtr_month - 1);
            Helper::log(' ### get_rtr_discount_amount result ###', $discount_obj);
            $fee        = $discount_obj['fee'];
            $pm_fee     = $discount_obj['pm_fee'];
            $rtr_discount   = $discount_obj['discount'];

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
            $trans->city = $request->city;

            if ($request->is_port_in == 'Y'){
                $trans->phone = $request->number_to_port;
                $trans->account_no = $request->account_no;
                $trans->account_pin = $request->account_pin;
                $trans->first_name = $request->first_name;
                $trans->last_name = $request->last_name;
                $trans->address1 = $request->address1;
                $trans->address2 = $request->address2;
                $trans->city = $request->city;
                $trans->state = $request->state;
                $trans->note = $request->note;
                $trans->current_carrier = $request->current_carrier;
                $trans->call_back_phone = $request->call_back_phone;
                $trans->email = $request->email;
                $trans->current_carrier = $request->old_service_provider;
                if($request->cell_number_contract == 'YES'){
                    $trans->carrier_contract = 'Y';
                }else{
                    $trans->carrier_contract = 'N';
                }
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

            if ($request->is_port_in != 'Y') {
                // LocusActivateGSMsim($trans_id, $act_prod_id, $pin_prod_id, $sim, $npa, $city, $zip)
                //$ret = emida2::LocusActivateGSMsim(
                $ret = emida2::LocusActivateGSMsim(
                    $trans->id,             // invoice No
                    $vendor_denom->act_pid, // activationProductId (Act.SKU)
                    $vendor_denom->act_pid, // pinProductId (PIN.SKU) 6/30/2020 => Same with act sku (from Emida request)
                    $request->sim,
                    $request->area_code,
                    $request->city ,
                    $request->zip_code);
            } else {
                //$ret = emida2::H2OWirelessPortin(
                $ret = emida2::H2OWirelessPortin(
                    $trans->id,
                    $vendor_denom->act_pid,
                    $request->email,
                    $request->call_back_phone,
                    $request->old_service_provider,
                    $request->number_to_port,
                    $request->cell_number_contract,
                    $request->first_name,
                    $request->last_name,
                    $request->account_no,
                    $request->account_pin,
                    $request->address1. ' ' .$request->address2,
                    $request->city,
                    $request->state,
                    $request->zip_code,
                    $request->sim,
                    $request->imei,
                    $vendor_denom->act_pid
                    );
            }

            Helper::log('### EMD API RESULT ###', [
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

            $trans->status      = 'C'; // Port In : Complete
            $trans->note       .= ' ' . $ret['error_msg'];
            $trans->phone       = $request->is_port_in == 'Y' ? $request->number_to_port : $ret['min'];
            $trans->vendor_tx_id = $ret['serial'];
            $trans->mdate       = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api         = 'Y';
            $trans->save();

            ### Consignment Charge ###
            if ($sim_type == 'C') {
                $ret = ConsignmentProcessor::charge($trans);
                if (!empty($ret['error_code'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            $sim_obj->used_trans_id = $trans->id;
            $sim_obj->product = $denom->product_id;
            $sim_obj->used_date = Carbon::now();
            $sim_obj->status = 'U';
            $sim_obj->save();

            if (!empty($esn_obj)) {
                $esn_obj->used_trans_id = $trans->id;
                $esn_obj->used_date = Carbon::now();
                $esn_obj->esn_charge = null;
                $esn_obj->esn_rebate = null;
                $esn_obj->status = 'U';
                $esn_obj->save();

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

                if (!empty($ret['error_code'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                    if (!empty($ret['error_msg'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                        Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
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
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($product_id == 'WH2OM'){
                        $rtr_product_id = 'WH2OMR';
                    }elseif($product_id == 'WH2OB'){
                        $rtr_product_id = 'WH2OBR';
                    }elseif($product_id == 'WH2OP'){
                        $rtr_product_id = 'WH2OPR';
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
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] H2O Activation - applyRTR remaining month failed', $msg);
                    }
                }
            }else{
                ### 1st spiff for port-in ###
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();
                $account = \App\Model\Account::find($trans->account_id);
                \App\Model\Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                $esn_obj = StockESN::where('product', $trans->product_id)->where('esn', $trans->esn)->first();
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {
                    $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $trans->product_id, $trans->denom, 1, 1, $trans->phone_type, $trans->sim, $trans->esn);
                    $spiff_amt = $ret_spiff['spiff_amt'];

                    $esn_obj = StockESN::where('esn', $trans->esn)->where('product', $trans->product_id)->first();
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->denom * $trans->rtr_month - $spiff_amt, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                    if (!empty($ret['error_msg'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                        Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                    }
                }

                $ret = RTRProcessor::applyRTR(
                    1,
                    '',
                    $trans->id,
                    'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $trans->vendor_code,
                    '',
                    $vendor_denom->denom,
                    'system',
                    false,
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][H2O][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WH2OM'){
                        $rtr_product_id = 'WH2OMR';
                    }elseif($trans->product_id == 'WH2OB'){
                        $rtr_product_id = 'WH2OBR';
                    }elseif($trans->product_id == 'WH2OP'){
                        $rtr_product_id = 'WH2OPR';
                    }
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        '',
                        $trans->id,
                        'House',
                        $trans->phone,
                        $rtr_product_id,
                        $vendor_denom->vendor_code,
                        $vendor_denom->rtr_pid,
                        $vendor_denom->denom,
                        'system',
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

        return view('sub-agent.activate.invoice')->with([
          'trans' => $trans
        ]);
    }
}