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
use App\Lib\gen;
use App\Model\Account;
use App\Model\Denom;
use App\Model\GenActivation;
use App\Model\GenFee;
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

class GENController
{
    public function show(Request $request) {

//        $trans = Transaction::find(20731);
//        gen::QueryPortin($trans);

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
              'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_gen != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Gen Mobile activation. Please contact your distributor'
            ]);
        }

        $ret = Helper::check_parents_product($account->id, 'WGENA');
        if($ret != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Gen Mobile activation. Please contact your distributor'
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
          ->whereIn('product_id', ['WGENA', 'WGENOA'])
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
        return view('sub-agent.activate.gen')->with([
            'transactions' => $transactions,
            'states'   => $states
        ]);

        // return self::post($request);

    }

    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $trans->deviceinfo = GenActivation::where('trans_id', $trans->id)->first();

        $states = State::all();
        return view('sub-agent.activate.gen')->with([
          'trans' => $trans,
          'states'   => $states
        ]);

        // return self::post($request);

    }

    public function esn(Request $request) {
        try {

            $product_ids = ['WGENA', 'WGENOA'];

            ### API: ValidateBYOD
            $res = gen::ValidateBYOD($request->esn);

            if ($res['code'] !== '0') {
                return response()->json([
                  'code'  => '-1',
                  'msg'   => 'Your device is not available [' . $res['error_msg'] . ']'
                ]);
            }

            $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();

            if (empty($esn_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WGENA')->first();
                StockESN::upload_byod($request->esn, 'WGENA', $p->carrier, $account->id, $account->name);
            }

            $mapping = StockMapping::where('esn', $request->esn)->where('status', 'A')->first();

            ### SIM Lookup
            if($res['model_number'] !== ''){
                $kits = PmModelSimLookup::where('model_number', $res['model_number'])->first();
            }

            ### ESN NOT ACTIVE ###
//            if ($esn_obj->status !== 'A') {
//                return response()->json([
//                  'code'  => '-2',
//                  'msg'   => 'The device is not available. Already activated !!'
//                ]);
//            }

            return response()->json([
                'code'  => '0',
                'sim'  => empty($mapping) ? '' : $mapping->sim,
                'kits'  => empty($kits) ? '' : $kits,
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

            $product_ids = ['WGENA', 'WGENOA'];

            $sim_obj = StockSIM::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->first();

            if (empty($sim_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WGENA')->first();
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WGENA', $p->carrier, $p->sim_group, $account->id, $account->name);
            }

            ### ESN NOT ACTIVE ###
            ### Allow Used SIM Again 6/12/2020 ###
//            if ($sim_obj->status !== 'A') {
//                // ADD LOGIC
//                return response()->json([
//                    'code' => '-2',
//                    'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
//                ]);
//            }

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

    public function zip(Request $request) {
        try {

            $product_ids = ['WGENA', 'WGENOA'];
            $user = Auth::user();
            $account = Account::find($user->account_id);

            ### Check the availability of the service in the given Zip Code
            ### API: CheckServiceAvailability
            $res = gen::CheckServiceAvailability($request->zip);

            if ($res['code'] == '0') {

                $city   = Zip::where('zip', $request->zip)->first(['city', 'state']);
                if (empty($city)) {
                    $city = '';
                }

                //$activation_fee = GenFee::get_total_fee($account->id, 'A', 0);
//                $activation_fee = 1;

//                $fee = $vendor_denom->fee;

                $sim_obj = null;
                if (!empty($request->sim)) {
                    $sim_obj = StockSIM::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->first();
                }

                if (!empty($sim_obj) && $sim_obj->type == 'P' && !empty($sim_obj->amount)) {
                    $plans = explode('|', $sim_obj->amount);

                    $denoms = Denom::whereIn('denomination.product_id', $product_ids)
                        ->whereIn('denom', $plans)
                        ->get([
                            DB::raw('denomination.id as denom_id'),
                            'denomination.denom',
                            DB::raw('denomination.name as denom_name'),
                            'denomination.cdate',
                            'denomination.mdate',
                            'denomination.created_by',
                            'denomination.modified_by'
                        ]);
                    foreach ($denoms as $d) {
                        $d->spiff = $sim_obj->spiff_override_r;
                    }
                } else {
                    if (!empty($account->spiff_template)) {
                        $denoms = Denom::Leftjoin('spiff_setup', function($join) use($account) {
                            $join->on('spiff_setup.product_id', 'denomination.product_id');
                            $join->on('spiff_setup.denom', 'denomination.denom');
                            $join->where('spiff_setup.template', $account->spiff_template);
                            $join->where('spiff_setup.account_type', 'S');
                        })
                            ->whereIn('denomination.product_id', $product_ids)
                            ->where('denomination.status', 'A')
                            ->get([
                                DB::raw('denomination.id as denom_id'),
                                'denomination.denom',
                                DB::raw('denomination.name as denom_name'),
                                DB::raw('ifnull(spiff_setup.spiff_1st, 0) as spiff'),
                                'denomination.cdate',
                                'denomination.mdate',
                                'denomination.created_by',
                                'denomination.modified_by'
                            ]);

                    }else {
                        $denoms = Denom::Leftjoin('spiff_setup', function ($join) {
                            $join->on('spiff_setup.product_id', '=', 'denomination.product_id');
                            $join->on('spiff_setup.denom', '=', 'denomination.denom');
                            $join->whereRaw('(spiff_setup.template is null or spiff_setup.template=\'\')');
                            $join->where('spiff_setup.account_type', 'S');
                        })
                            ->whereIn('denomination.product_id', $product_ids)
                            ->where('denomination.status', 'A')
                            ->get([
                                DB::raw('denomination.id as denom_id'),
                                'denomination.denom',
                                DB::raw('denomination.name as denom_name'),
                                DB::raw('ifnull(spiff_setup.spiff_1st , 0) as spiff'),
                                'denomination.cdate',
                                'denomination.mdate',
                                'denomination.created_by',
                                'denomination.modified_by'
                            ]);
                    }
                }

                $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();

                $allowed_months = Helper::get_min_month($esn_obj, $account, 'gen_min_month');

                return response()->json([
                  'code'   => '0',
//                  'activation_fee' => number_format($activation_fee, 2),
                  'city'   => $city,
                  'denoms' => $denoms,
                  'allowed_months' => $allowed_months,
                  'sim_type' => empty($sim_obj) ? 'R' : $sim_obj->type,
                  'msg' => ''
                ]);
            } else {
                return response()->json([
                  'code'  => '-1',
                  'msg'   => 'Your ZIP is not available [' . $res['error_msg'] . ']'
                ]);
            }

        } catch (\Exception $ex) {
            return response()->json([
              'code'  => '-9',
              'msg'   => $ex->getMessage()
            ]);
        }

    }

    public function commission(Request $request) {
        try {

            $special_spiffs = null;

            $denom   = Denom::find($request->denom_id);
            $product_id = $denom->product_id;
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('product', $product_id)->first();
            $esn_obj = StockESN::where('esn', $request->esn)->where('product', $product_id)->first();

            ### Special Spiff
            $terms = array();
            if ($request->is_port_in == 'Y') {
                $terms[] = 'Port';
            }

            $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                $product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj, $terms
            );

            $spiff_labels = Array();

            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
            if ($extra_spiff > 0) {
                $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';
            }

            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_labels[] = '$ ' . number_format($ss['spiff'], 2) . ', ' . $ss['name'];
                }
            }

            $spiff_count = count($spiff_labels);

            $product_id = $denom->product_id;
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            if($vendor_denom->fee == null || $vendor_denom->fee == 0){
                $activation_fee = 0;
            }else{
                $activation_fee = $vendor_denom->fee;
            }

            return response()->json([
              'code'  => '0',
              'data'  => [
                  'spiff_labels'    => $spiff_labels,
                  'spiff_count'     => $spiff_count,
                  'activation_fee'  => $activation_fee,
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

    public function get_portin_form() {

        $states = State::all();

        return view('sub-agent.activate.portin-form')->with([
            'states'     => $states,
            'product_id' => 'WGENA'
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
                'esn' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'city' => 'required',
                'state' => 'required',
//              'contact_phone' => 'required',
//              'contact_email' => 'required',
                'is_port_in' => 'required',

                'number_to_port' => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'account_no' => 'required_if:is_port_in,Y',
                'account_pin' => 'required_if:is_port_in,Y',
                'first_name' => 'required_if:is_port_in,Y',
                'last_name' => 'required_if:is_port_in,Y',
                'address1' => 'required_if:is_port_in,Y',
                'address2' => 'required_if:is_port_in,Y',
                'account_city' => 'required_if:is_port_in,Y',
                'account_state' => 'required_if:is_port_in,Y',
                'account_zip' => 'required_if:is_port_in,Y',
                'call_back_phone' => 'required_if:is_port_in,Y',
                'email' => 'required_if:is_port_in,Y'
            ], [
                'denom_id.required' => 'Please select product',
                'zip.required' => 'Valid zip code is required',

                'number_to_port.required_if' => 'Port-in number is required',
                'account_no.required_if' => 'Account # is required',
                'account_pin.required_if' => 'Account PIN is required',
                'first_name.required_if' => 'First name is required',
                'last_name.required_if' => 'Last name is required',
                'address1.required_if' => 'Street number is required',
                'address2.required_if' => 'Street name is required',
                'account_city.required_if' => 'City is required',
                'account_state.required_if' => 'State is required',
                'account_zip.required_if' => 'Zip is required',
                'call_back_phone.required_if' => 'Call back phone # is required',
                'email.required_if' => 'Email is required'
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
            if (empty($denom->status) || $denom->status != 'A') {
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Invalid denomination provided.]'
                  ]
                ]);
            }

            $product_ids = ['WGENA', 'WGENOA'];
            $product_id = $denom->product_id;

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

            $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();
            if (!empty($esn_obj)) {
                if (!empty($esn_obj->amount) && $esn_obj->product != $product_id) {
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
                            'msg' => '[SIM is not available. Not valid owner.]'
                          ]
                        ]);
                    }
                }
            }

            if (!empty($request->sim)) {

                $sim_obj = StockSim::where('sim_serial', $request->sim)
                    ->where('product', $product_id)
                    ->where('status', 'A')
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
                    if (!empty($sim_obj->amount) && $sim_obj->product != $product_id) {
                        return response()->json([
                          'code' => '-2',
                          'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Please enter valid SIM.]'
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

            ### Need to add activation fee ###
//            $activation_fee = GenFee::get_total_fee($account->id, 'A', 0);
//            $fee        = $activation_fee;
//            $pm_fee     = 0;

            $rtr_discount   = 0;

            ### Act/Recharge Fee by products, not by accounts for Gen (7/24/19)  ###
            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt =  !empty($sim_obj) && $sim_obj->type == 'P' ? 0 : $denom->denom - $rtr_discount;

            ### check sales limit ###
            $net_revenue = 0;
            $rebate_amt = 0;
            if ($account->rebates_eligibility == 'Y') {
                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                $rebate_amt = $ret_rebate['rebate_amt'];
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'GEN SPR');
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
            $trans->denom_id = $request->denom_id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->zip = $request->zip;

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
                $trans->account_city = $request->account_city;
                $trans->account_state = $request->account_state;
                $trans->account_zip = $request->account_zip;
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

            $params = new \stdClass();
            $params->trans_id = $trans->id;
            $params->esn = $request->esn;
            $params->sim = $request->sim;
            $params->zip = $request->zip;
            $params->city = $request->city;
            $params->state = $request->state;
            $params->act_pid = $vendor_denom->act_pid;
            $params->network = 'SPR';

            if ($request->is_port_in != 'Y') {
                // Activate($params)
                $ret = gen::Activate($params);
            } else {
                $params->phone = $request->number_to_port;
                $params->current_carrier = $request->current_carrier;
                $params->account_no = $request->account_no;
                $params->account_pin = $request->account_pin;
                $params->first_name = $request->first_name;
                $params->last_name = $request->last_name;
                $params->address1 = $request->address1;
                $params->address2 = $request->address2;
                $params->account_city = $request->account_city;
                $params->account_state = $request->account_state;
                $params->account_zip = $request->account_zip;
                $params->call_back_phone = $request->call_back_phone;
                $params->email = $request->email;

                // Portin($params)
                $ret = gen::Portin($params);
            }

            Helper::log('### GEN API RESULT ###', [
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
                    'fld'   => 'Your request has been failed. [V]',
                    'msg'   => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                  ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $ret['error_msg'];
            $trans->phone   = $request->is_port_in == 'Y' ? $request->number_to_port : $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            ### Consignment Charge ###
//            if ($sim_type == 'C') {
//                $ret = ConsignmentProcessor::charge($trans);
//                if (!empty($ret['error_msg'])) {
//                    ### send message only ###
//                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
//                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//
//                    Helper::send_mail('it@perfectmobileinc.com', '[PM][AT&T][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
//                }
//            }

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

                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                    $trans->product_id, $trans->denom, 'S', $trans->account_id, $sim_obj, $esn_obj, []
                );

                $pay_activation_fee = true;
                if (!empty($special_spiffs)) {
                    foreach ($special_spiffs as $s) {
                        if (in_array($s['special_id'], [295, 296, 297, 298, 299])) {
                            $pay_activation_fee = false;
                            break;
                        }
                    }
                }

                if ($pay_activation_fee) {
                    ### Pay GEN Activation FEE ###
                    ### Act/Recharge Fee by products, not by accounts for Gen 7/24/19) ###
//                    GenFee::pay_fee($account->id, 'A', $trans->id, $account);
                }

                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

                if (!empty($ret['error_code'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                $rebate_type = 'R';
                $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
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
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WGENA'){
                        $rtr_product_id = 'WGENR';
                    }elseif($trans->product_id == 'WGENOA'){
                        $rtr_product_id = 'WGENOR';
                    }elseif($trans->product_id == 'WGENTA'){
                        $rtr_product_id = 'WGENTAR';
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
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] GEN Activation - applyRTR remaining month failed', $msg);
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



    /*
     *  GEN T-Mobile
     *
     */

    public function show_tmo(Request $request) {

//        $trans = Transaction::find(20731);
//        gen::QueryPortin($trans);

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_gen != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Gen Mobile activation. Please contact your distributor'
            ]);
        }

        $ret = Helper::check_parents_product($account->id, 'WGENA');
        if($ret != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Gen Mobile activation. Please contact your distributor'
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
            ->whereIn('product_id', ['WGENTA', 'WGENTOA'])
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
        return view('sub-agent.activate.gen-tmo')->with([
            'transactions' => $transactions,
            'states'   => $states
        ]);
        // return self::post($request);

    }


    public function sim_gen_tmo(Request $request) {
        try {

            $product_ids = ['WGENTA', 'WGENTOA'];
            $account = Account::find(Auth::user()->account_id);
            $p = Product::where('id', 'WGENTA')->first();
            $sim_obj = StockSIM::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            if (empty($sim_obj)) {
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WGENTA', $p->carrier, $p->sim_group, $account->id, $account->name);
            }else{
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

            $mapping = StockMapping::whereIn('product', $product_ids)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();

            $denoms = Denom::whereIn('product_id', $product_ids)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'GEN TMO activation is not ready.'
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
                    $denom_tmp = Denom::whereIn('product_id', $product_ids)->where('denom', $s)->where('status', 'A')->get();
                    foreach ($denom_tmp as $d) {
                        $plans[] = [
                            'denom_id' => $d->id,
                            'denom' => $d->denom,
                            'name' => $d->name
                        ];
                    }
                }
            }

//            foreach ($plans as $p) {
//                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, $request->sim, null);
//
//                $p['spiff'] = $ret_spiff['spiff_amt'];
//            }

            $allowed_months = Helper::get_min_month($sim_obj, $account, 'boom_min_month');

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim'       => $sim_obj->sim_serial,
                    'meid'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim_obj->sub_carrier,
//                    'product_id' => $product_id,
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

    public function commission_tmo(Request $request) {
        try {
            $spiff = 0;
            $rebate = 0;
            $special_spiffs = null;
            $denom   = Denom::find($request->denom_id);
            $product_id = $denom->product_id;
            $p = Product::where('id', $product_id)->first();

            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            // BYOD allow or not..?
            if($request->meid) {
                $esn_obj = StockESN::where('esn', $request->meid)->where('product', $product_id)->first();

                if (empty($esn_obj)) {
                    $account = Account::find(Auth::user()->account_id);
                    StockESN::upload_byod($request->meid, $product_id, $p->carrier, $account->id, $account->name);
                }
            }else{
                $esn_obj ='';
            }

            if (!empty($denom)) {
                $account = Account::find(Auth::user()->account_id);
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'GEN TMO');
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

            // Regulatory Fee, instead of Vendor Fee //
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            $fee = empty($vendor_denom->fee) ? 0 : $vendor_denom->fee;
            $pm_fee = empty($vendor_denom->pm_fee) ? 0 : $vendor_denom->pm_fee;

            $activation_fee = $fee + $pm_fee;

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'spiff_labels'    => $spiff_labels,
                    'sim_charge'      => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'      => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'      => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'      => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate,
                    'activation_fee'  => $activation_fee
                ]
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }

    }

    public function get_portin_form_tmo() {

        $states = State::all();

        return view('sub-agent.activate.portin-form-gen-tmo')->with([
            'states'     => $states,
            'product_id' => 'WGENTA'
        ]);
    }

    public function post_tmo(Request $request) {
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
                'sim'       => 'required',
//                'esn'       => 'required',
                'zip'       => 'required|regex:/^\d{5}$/',
                'is_port_in'=> 'required',
                'denom_id'  => 'required',
                'rtr_month' => 'required',
                'city'      => 'required',
                'state'     => 'required',

                'p_number_to_port'  => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'p_account_no'      => 'required_if:is_port_in,Y',
                'p_account_pin'     => 'required_if:is_port_in,Y',
                'p_first_name'      => 'required_if:is_port_in,Y',
                'p_last_name'       => 'required_if:is_port_in,Y',
                'p_account_address1'=> 'required_if:is_port_in,Y',
                'p_account_city'    => 'required_if:is_port_in,Y',
                'p_account_state'   => 'required_if:is_port_in,Y',
                'p_account_zip'     => 'required_if:is_port_in,Y',
            ], [
                'zip.required_if'               => 'Valid zip code is required',
                'first_name.required_if'        => 'First name is required',
                'last_name.required_if'         => 'Last name is required',
                'p_number_to_port.required_if'  => 'Port-in MDN is required',
                'p_account_no.required_if'      => 'Account # is required',
                'p_account_pin.required_if'     => 'Password is required',
                'p_first_name.required_if'      => 'PortIn First name is required',
                'p_last_name.required_if'       => 'PortIn Last name is required',
                'p_account_address1.required_if'=> 'PortIn Address is required',
                'p_account_city.required_if'    => 'PortIn City is required',
                'p_account_state.required_if'   => 'PortIn State is required',
                'p_account_zip.required_if'     => 'PortIn Zip is required'
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

            // Duplicate check (Status 'I' in 10 min)
            $ret_t = Transaction::where('account_id', $account->id)
                ->where('product_id', $product->id)
                ->where('status', 'I')
                ->where('cdate', '>=', Carbon::now()->subMinutes(10)->toDateTimeString())
                ->first();

            if (!empty($ret_t)){
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'You already have another transaction (In Progress)',
                        'msg'   => 'Please wait for the respond at Transaction report.'
                    ]
                ]);
            }

            $product_ids = ['WGENTA', 'WGENTOA'];
            $product_id = $denom->product_id;

            $sim_obj = null;
            $esn_obj = null;

            if ($request->esn) {
                $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();
            }
            if (!empty($esn_obj)) {

                if (!empty($esn_obj->amount) && $esn_obj->product != $product_id) {
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

                $sim_obj = StockSim::where('sim_serial', $request->sim)
                    ->where('sim_group', $product->sim_group)
                    ->where('status', 'A')
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
                if($request->esn) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                    $rebate_amt = $ret_rebate['rebate_amt'];
                }
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'GEN TMO');
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
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->address1 = $request->address;
            $trans->city = $request->city;
            $trans->state = $request->state;
            $trans->email = 'ops@softpayplus.com';

            if ($request->is_port_in == 'Y'){
                $trans->phone = $request->p_number_to_port;
                $trans->account_no = $request->p_account_no;
                $trans->account_pin = $request->p_account_pin;
                $trans->account_zip = $request->p_account_zip;
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
            $params->trans_id   = $trans->id;
            $params->zip        = $request->zip;
            $params->esn        = $request->esn;
            $params->sim        = $request->sim;
            $params->service_type = $request->service_type;
            $params->act_pid    = $vendor_denom->act_pid;
            $params->first_name = $request->first_name;
            $params->last_name  = $request->last_name;
            $params->address    = $request->address;
            $params->city       = $request->city;
            $params->state      = $request->state;
            $params->email      = 'ops@softpayplus.com';
            $params->network    = 'TMB';

            if ($request->is_port_in != 'Y') {

                $ret = gen::Activate($params);


            } else {

                $params->phone          = $request->p_number_to_port;
                $params->call_back_phone  = '';

                $params->first_name     = $request->p_first_name;
                $params->last_name      = $request->p_last_name;
                $params->address1       = $request->p_account_address1;
                $params->address2       = '';
                $params->account_city   = $request->p_account_city;
                $params->account_state  = $request->p_account_state;
                $params->account_zip    = $request->p_account_zip;
                $params->account_no     = $request->p_account_no;
                $params->account_pin    = $request->p_account_pin;

                $ret = gen::Portin($params);

            }

            Helper::log('### GEN API RESULT ###', [
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
                        'fld'   => 'Your request has been failed. [V]',
                        'msg'   => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $ret['error_msg'];
            $trans->phone   = $request->is_port_in == 'Y' ? $request->p_number_to_port : $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

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
                        'status'        => 'U'
                    ]);
                if(!empty($esn_obj)) {
                    $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $product_id)->where('status', 'A')->first();
                    if (!empty($mapping)) {
                        $mapping->status = 'U';
                        $mapping->update();
                    }
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

                    Helper::send_mail('it@jjonbp.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {

                    if($esn_obj) {
                        $rebate_type = empty($esn_obj) ? 'B' : 'R';
                        $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                        if (!empty($ret['error_msg'])) {
                            ### send message only ###
                            $msg = ' - trans ID : ' . $trans->id . '<br/>';
                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                            Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN TMO][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                        }
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
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN TMO][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WGENA'){
                        $rtr_product_id = 'WGENR';
                    }elseif($trans->product_id == 'WGENOA'){
                        $rtr_product_id = 'WGENOR';
                    }elseif($trans->product_id == 'WGENTA'){
                        $rtr_product_id = 'WGENTAR';
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
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] GEN Activation - applyRTR remaining month failed', $msg);
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

    public function success_tmo(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $trans->deviceinfo = GenActivation::where('trans_id', $trans->id)->first();

        $states = State::all();
        return view('sub-agent.activate.gen-tmo')->with([
            'trans' => $trans,
            'states'   => $states
        ]);

        // return self::post($request);

    }

}