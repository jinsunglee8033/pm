<?php
/**
 * Created by Royce
 * Date: 6/22/18
 */

namespace App\Http\Controllers\GEN;


use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\gen;
use App\Mail\SendPhoneNumber;
use App\Model\Account;
use App\Model\Denom;
use App\Model\GenActivation;
use App\Model\GenFee;
use App\Model\PmModelSimLookup;
use App\Model\Product;
use App\Model\Promotion;
use App\Model\RTRPayment;
use App\Model\SpiffSetupSpecial;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;

use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;

use App\Model\ATTTIDLog;

use App\Model\Zip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Mail;

class ActivateController
{
    public function show(Request $request) {

        $states = State::all();
        return view('gen.activate')->with([
            'states'   => $states
        ]);

    }

    public function show_tmo(Request $request) {
        $states = State::all();
        return view('gen.activate-tmo')->with([
            'states'   => $states
        ]);
    }

    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();
        $trans->deviceinfo = GenActivation::where('trans_id', $trans->id)->first();

        $states = State::all();

        if($trans->network =='SPR') {
            return view('gen.activate')->with([
                'trans' => $trans,
                'states' => $states
            ]);
        }else{
            return view('gen.activate-tmo')->with([
                'trans' => $trans,
                'states' => $states
            ]);
        }
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
                $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
                $p = Product::where('id', 'WGENA')->first();
                $esn_obj = StockESN::upload_byod($request->esn, 'WGENA', $p->carrier, $acct_id, 'C-Store RTR');
            }

            $mapping = StockMapping::where('esn', $request->esn)->where('status', 'A')->first();

            ### SIM Lookup
            if($res['model_number'] !== ''){
                $kits = PmModelSimLookup::where('model_number', $res['model_number'])->first();
            }

            ### ESN NOT ACTIVE ###
            if ($esn_obj->status !== 'A') {
                return response()->json([
                  'code'  => '-2',
                  'msg'   => 'The device is not available. Already activated !!'
                ]);
            }

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
                $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
                $p = Product::where('id', 'WGENA')->first();
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WGENA', $p->carrier, $p->sim_group, $acct_id, 'C-Store RTR');
            }

            ### SIM NOT ACTIVE ###
            if ($sim_obj->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                ]);
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

    public function sim_tmo(Request $request) {
        try {

            $product_ids = ['WGENTA', 'WGENTOA'];

            // Search by Sim group
            $sim_obj = StockSIM::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->first();

            $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;

            if (empty($sim_obj)) {
                $p = Product::where('id', 'WGENTA')->first();
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WGENTA', $p->carrier, $p->sim_group, $acct_id, 'C-Store RTR');
                $c_store_id = null;
            }else{
                if(!empty($sim_obj->c_store_id)){
                    $c_store_id = $sim_obj->c_store_id;
                }else{
                    $c_store_id = null;
                }
            }

            ### SIM NOT ACTIVE ###
            if ($sim_obj->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                ]);
            }

            if (!empty($sim_obj) && $sim_obj->status == 'A' && $sim_obj->type == 'P' && !empty($sim_obj->amount)) {

                $plans = explode('|', $sim_obj->amount);

                $denoms = Denom::whereIn('denomination.product_id', $product_ids)
                    ->whereIn('denom', $plans)
                    ->where('denomination.status', 'A')
                    ->orderBy('denomination.denom', 'ASC')
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
                $account = Account::find($acct_id);
                if (!empty($account->gen_spiff_template)) {
                    $denoms = Denom::Leftjoin('spiff_setup', function($join) use($account) {
                        $join->on('spiff_setup.product_id', 'denomination.product_id');
                        $join->on('spiff_setup.denom', 'denomination.denom');
                        $join->where('spiff_setup.template', $account->gen_spiff_template);
                        $join->where('spiff_setup.account_type', 'S');
                    })
                        ->whereIn('denomination.product_id', $product_ids)
                        ->where('denomination.status', 'A')
                        ->orderBy('denomination.denom', 'ASC')
                        ->get([
                            DB::raw('denomination.id as denom_id'),
                            'denomination.denom',
                            DB::raw('denomination.name as denom_name'),
                            DB::raw('spiff_setup.spiff_1st as spiff'),
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
                        ->orderBy('denomination.denom', 'ASC')
                        ->get([
                            DB::raw('denomination.id as denom_id'),
                            'denomination.denom',
                            DB::raw('denomination.name as denom_name'),
                            DB::raw('spiff_setup.spiff_1st as spiff'),
                            'denomination.cdate',
                            'denomination.mdate',
                            'denomination.created_by',
                            'denomination.modified_by'
                        ]);
                }
            }

//            $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;

            return response()->json([
                'code'   => '0',
                'denoms' => $denoms,
                'sim_type' => empty($sim_obj) ? 'R' : $sim_obj->type,
                'button' => empty($c_store_id) ? 'paypal' : 'normal',
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }

    }

    public function zip_tmo(Request $request) {
        try {

            ### Check the availability of the service in the given Zip Code
            ### API: CheckServiceAvailability
            $res = gen::CheckServiceAvailability($request->zip);
            if ($res['code'] == '0') {

                $city   = Zip::where('zip', $request->zip)->first(['city', 'state']);
                if (empty($city)) {
                    $city = '';
                }

                return response()->json([
                    'code'   => '0',
                    'city'   => $city,
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

    public function zip(Request $request) {
        try {

            $product_ids = ['WGENA', 'WGENOA'];

            ### Check the availability of the service in the given Zip Code
            ### API: CheckServiceAvailability
            $res = gen::CheckServiceAvailability($request->zip);
            if ($res['code'] == '0') {

                $city   = Zip::where('zip', $request->zip)->first(['city', 'state']);
                if (empty($city)) {
                    $city = '';
                }

                $ecom_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;

                $sim_obj = StockSIM::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->first();
                if(!empty($sim_obj)){
                    if(!empty($sim_obj->c_store_id)){
                        $acct_id = $sim_obj->c_store_id;
                    }else{
                        $acct_id = $ecom_id;
                    }
                }

                $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();
                if(!empty($esn_obj)){
                    if(!empty($esn_obj->c_store_id)){
                        $acct_id = $esn_obj->c_store_id;
                    }else{
                        $acct_id = $ecom_id;
                    }
                }else{
                    $acct_id = $ecom_id;
                }


//                if(empty($esn_obj->c_store_id)){
//                    return response()->json([
//                        'code'  => '-1',
//                        'msg'   => 'Your device is not belong to C.Store.ID.'
//                    ]);
//                }

//                if(!empty($request->sim)) {
//                    if (empty($sim_obj->c_store_id)) {
//                        return response()->json([
//                            'code' => '-1',
//                            'msg' => 'Your sim is not belong to C.Store.ID.'
//                        ]);
//                    }
//                }
//
//                if(!empty($request->sim)) {
//                    if ($esn_obj->c_store_id !== $sim_obj->c_store_id) {
//                        return response()->json([
//                            'code' => '-1',
//                            'msg' => 'SIM & ESN are belong to different C.Store.ID. Please contact our customer support.'
//                        ]);
//                    }
//                }

                $c_store_id = !empty($esn_obj->c_store_id) ? $esn_obj->c_store_id : null;

                //$activation_fee = GenFee::get_total_fee($c_store_id, 'A', 0);
//                $activation_fee = 1;

                $sim_obj = null;
                if (!empty($request->sim)) {
                    $sim_obj = StockSIM::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->first();
                }

                if (!empty($sim_obj) && $sim_obj->status == 'A' && $sim_obj->type == 'P' && !empty($sim_obj->amount)) {

                    $plans = explode('|', $sim_obj->amount);

                    $denoms = Denom::whereIn('denomination.product_id', $product_ids)
                        ->whereIn('denom', $plans)
                        ->where('denomination.status', 'A')
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
                    $account = Account::find($c_store_id);
                    if (!empty($account->gen_spiff_template)) {
                        $denoms = Denom::Leftjoin('spiff_setup', function($join) use($account) {
                            $join->on('spiff_setup.product_id', 'denomination.product_id');
                            $join->on('spiff_setup.denom', 'denomination.denom');
                            $join->where('spiff_setup.template', $account->gen_spiff_template);
                            $join->where('spiff_setup.account_type', 'S');
                        })
                            ->whereIn('denomination.product_id', $product_ids)
                            ->where('denomination.status', 'A')
                            ->get([
                                DB::raw('denomination.id as denom_id'),
                                'denomination.denom',
                                DB::raw('denomination.name as denom_name'),
                                DB::raw('spiff_setup.spiff_1st as spiff'),
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
                                DB::raw('spiff_setup.spiff_1st as spiff'),
                                'denomination.cdate',
                                'denomination.mdate',
                                'denomination.created_by',
                                'denomination.modified_by'
                            ]);
                    }
                }

                return response()->json([
                    'code'   => '0',
                    'city'   => $city,
                    'denoms' => $denoms,
                    'allowed_months' => [
                        1,2,3
                    ],
                    'sim_type' => empty($sim_obj) ? 'R' : $sim_obj->type,
                    'button' => empty($c_store_id) ? 'paypal' : 'normal',
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

//            if (!empty($sim_obj)) {
//                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
//                    $product_id, $denom->denom, 'S', $sim_obj->c_store_id, $sim_obj, $esn_obj, $terms
//                );
//            }

            $spiff_labels = Array();

            if (!empty($sim_obj)) {
                $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
                if ($extra_spiff > 0) {
                    $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';
                }
            }

//            if (!empty($special_spiffs)) {
//                foreach ($special_spiffs as $ss) {
//                    $spiff_labels[] = '$ ' . number_format($ss['spiff'], 2) . ', ' . $ss['name'];
//                }
//            }

            $spiff_count = count($spiff_labels);

            $product_id = $denom->product_id;
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->first();

            if($vendor_denom->fee == null || $vendor_denom->fee == 0){
                $activation_fee = 0;
            }else{
                $activation_fee = $vendor_denom->fee;
            }

            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            if ($sim_type == 'X') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld' => 'exception',
                        'msg' => 'Unable to determine SIM type.'
                    ]
                ]);
            }

            ### get collection amount ###
            $collection_amt = $sim_type == 'R' ? $denom->denom : 0;

            $total = $collection_amt + $activation_fee
                + (empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge)
                - (empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate)
                + (empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge)
                - (empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate);

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'spiff_labels'      => $spiff_labels,
                    'spiff_count'       => $spiff_count,
                    'activation_fee'    => $activation_fee,
                    'sim_charge'        => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'        => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'        => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'        => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate,
                    'total'             => $total
                ]
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

            $v = Validator::make($request->all(), [
                'esn' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'city' => 'required',
                'state' => 'required',
                'denom_id' => 'required',
                'call_back_phone' => 'required',
                'email' => 'required'
            ], [
                'esn.required' => 'Please Device ID is required',
                'zip.required' => 'Valid zip code is required',
                'city.required' => 'Please City is required',
                'city.required' => 'Please State is required',
                'denom_id.required' => 'Please select Plan',
                'call_back_phone' => 'Please Call Back Number is required',
                'email' => 'Please Email is required'
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

            $sim_obj = null;
            $esn_obj = null;
            $c_store_id = null;

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
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

            if(!empty($request->sim)) {
                $sim_obj = StockSim::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->where('status', 'A')->first();
                if (empty($sim_obj)) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld' => 'sim',
                            'msg' => 'Please enter valid sim number.'
                        ]
                    ]);
                }

                $c_store_id = $sim_obj->c_store_id;
            }

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
                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Invalid ESN/IMEI provided.'
                        ]
                    ]);
                }

                if (!in_array($esn_obj->type, ['P', 'R'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Only Wallet or Regular ESN/IMEI is allowed.'
                        ]
                    ]);
                }
                $c_store_id = $esn_obj->c_store_id;
            }

            $account = Account::find($c_store_id);
            if (empty($account)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'SIM does not belong to C.Store. Please contact our customer support.'
                    ]
                ]);
            }

            $result = Helper::check_threshold_limit_by_account($c_store_id);
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
                        'fld'   => 'exception',
                        'msg'   => 'The product is not available.'
                    ]
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
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

            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            if ($sim_type == 'X') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld' => 'exception',
                        'msg' => 'Unable to determine SIM type.'
                    ]
                ]);
            }

            ### fee ###
            $rtr_month = 1;

            ### Need to add activation fee ###
            //$activation_fee = GenFee::get_total_fee($account->id, 'A', 0);
//            $activation_fee = 1;

            $fee        = $vendor_denom->fee * $rtr_month;
            $pm_fee     = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt = $sim_type == 'R' ? $denom->denom : 0;

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
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product_id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }
            
            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($c_store_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => $ret['error_msg']
                        ]
                    ]);
                }
                $net_revenue = $ret['net_revenue'];
            }

            $trans = new Transaction;
            $trans->account_id = $account->id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->zip = $request->zip;
            $trans->npa = $request->area_code;
            $trans->created_by = 'cstore';
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;
            $trans->phone_type = '';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;
            $trans->network = 'SPR';
            $trans->save();

            $params = new \stdClass();
            $params->trans_id = $trans->id;
            $params->esn = $request->esn;
            $params->sim = $request->sim;
            $params->zip = $request->zip;
            $params->city = $request->city;
            $params->state = $request->state;
            $params->act_pid = $vendor_denom->act_pid;
            $params->network = $trans->network;

            $ret = gen::Activate($params);

            Helper::log('### GEN API RESULT ###', [
                'ret' => $ret
            ]);

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                $trans->mdate = Carbon::now();
                $trans->modified_by = 'cstore';
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

            $trans->status  = 'C';
            $trans->note    .= $ret['error_msg'];
            $trans->phone   = $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = 'cstore';
            $trans->api = 'Y';
            $trans->save();

            ### Update ESN status
            if (!empty($esn_obj)) {
                StockESN::where('esn', $esn_obj->esn)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
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

//            if ($pay_activation_fee) {
//                ### Pay GEN Activation FEE ###
//                GenFee::pay_fee($account->id, 'A', $trans->id, $account);
//            }

            ### spiff ###
            $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

            if (!empty($ret['error_msg'])) {
                ### send message only ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
            }

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

            ### Pay extra spiff and sim charge, sim rebate
            Promotion::create_by_order($sim_obj, $account, $trans->id);

            ### Pay extra spiff and esn charge, esn rebate
            Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

            Helper::log('### API call completed ###');

            $ret = RTRProcessor::applyRTR(
                1,
                isset($sim_type) ? $sim_type : '',
                $trans->id,
                'Carrier',
                $trans->phone,
                $trans->product_id,
                $vendor_denom->vendor_code,
                '',
                $vendor_denom->denom,
                'cstore',
                false,
                null,
                1,
                $vendor_denom->fee,
                $trans->rtr_month
            );

            if (!empty($ret)) {
                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
            }

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

    public function post_tmo(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'sim'   => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'city' => 'required',
                'state' => 'required',
                'denom_id' => 'required',
                'call_back_phone' => 'required',
                'email' => 'required'
            ], [
                'sim.required' => 'Please select sim',
                'zip.required' => 'Valid zip code is required',
                'city.required' => 'Please select City',
                'state.required' => 'Please select State',
                'denom_id.required' => 'Please select Plan',
                'call_back_phone.required' => 'Please Call Back Number is required',
                'email.required' => 'Please Email is required'
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

            $sim_obj = null;
            $esn_obj = null;
            $c_store_id = null;

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Invalid denomination provided.]'
                    ]
                ]);
            }

            $product_ids = ['WGENTA', 'WGENTOA'];
            $product_id = $denom->product_id;

            if(!empty($request->sim)) {
                $sim_obj = StockSim::where('sim_serial', $request->sim)->whereIn('product', $product_ids)->where('status', 'A')->first();
                if (empty($sim_obj)) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld' => 'sim',
                            'msg' => 'Please enter valid sim number.'
                        ]
                    ]);
                }
                $c_store_id = !empty($sim_obj->c_store_id) ? $sim_obj->c_store_id : null;
            }

            if (!empty($request->esn)) {
                $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();
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
                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Invalid ESN/IMEI provided.'
                        ]
                    ]);
                }

                if (!in_array($esn_obj->type, ['P', 'R'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Only Wallet or Regular ESN/IMEI is allowed.'
                        ]
                    ]);
                }
//                $c_store_id = $esn_obj->c_store_id;
            }

            if(!empty($c_store_id)){
                $account_id = $c_store_id;
            }else{
                $account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            }

            $account = Account::find($account_id);
            if (empty($account)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'SIM does not belong to C.Store. Please contact our customer support.'
                    ]
                ]);
            }

            $result = Helper::check_threshold_limit_by_account($c_store_id);
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
                        'fld'   => 'exception',
                        'msg'   => 'The product is not available.'
                    ]
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
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

            $sim_type = StockSim::get_sim_type(!empty($request->esn) ? $request->esn : null, $request->sim, $denom->product_id);
            if ($sim_type == 'X') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld' => 'exception',
                        'msg' => 'Unable to determine SIM type.'
                    ]
                ]);
            }

            ### fee ###
            $rtr_month = 1;

            ### Need to add activation fee ###
            //$activation_fee = GenFee::get_total_fee($account->id, 'A', 0);
//            $activation_fee = 1;

            $fee        = $vendor_denom->fee * $rtr_month;
            $pm_fee     = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt = $sim_type == 'R' ? $denom->denom : 0;

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
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product_id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($c_store_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => $ret['error_msg']
                        ]
                    ]);
                }
                $net_revenue = $ret['net_revenue'];
            }

            $trans = new Transaction;
            $trans->account_id = $account->id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = !empty($request->esn) ? $request->esn : null;
            $trans->zip = $request->zip;
//            $trans->npa = $request->area_code;
            $trans->created_by = 'cstore';
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;
            $trans->phone_type = '';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;
            $trans->network = 'TMB';

            $trans->save();

            $params = new \stdClass();
            $params->trans_id = $trans->id;
            $params->esn = $request->esn;
            $params->network = $trans->network;
            $params->sim = $request->sim;
            $params->zip = $request->zip;
            $params->city = $request->city;
            $params->state = $request->state;
            $params->act_pid = $vendor_denom->act_pid;

            $ret = gen::Activate($params);

            Helper::log('### GEN API RESULT ###', [
                'ret' => $ret
            ]);

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                $trans->mdate = Carbon::now();
                $trans->modified_by = 'cstore';
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

            $trans->status  = 'C';
            $trans->note    .= $ret['error_msg'];
            $trans->phone   = $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = 'cstore';
            $trans->api = 'Y';
            $trans->save();

            ### Update ESN status
            if (!empty($esn_obj)) {
                StockESN::where('esn', $esn_obj->esn)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
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

//            if ($pay_activation_fee) {
//                ### Pay GEN Activation FEE ###
//                GenFee::pay_fee($account->id, 'A', $trans->id, $account);
//            }

            ### spiff ###
            $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

            if (!empty($ret['error_msg'])) {
                ### send message only ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
            }

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

            ### Pay extra spiff and sim charge, sim rebate
            Promotion::create_by_order($sim_obj, $account, $trans->id);

            ### Pay extra spiff and esn charge, esn rebate
            Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

            Helper::log('### API call completed ###');

            $ret = RTRProcessor::applyRTR(
                1,
                isset($sim_type) ? $sim_type : '',
                $trans->id,
                'Carrier',
                $trans->phone,
                $trans->product_id,
                $vendor_denom->vendor_code,
                '',
                $vendor_denom->denom,
                'cstore',
                false,
                null,
                1,
                $vendor_denom->fee,
                $trans->rtr_month
            );

            if (!empty($ret)) {
                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
            }

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

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'esn' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'call_back_phone' => 'required',
                'email' => 'required'
            ], [
                'denom_id.required' => 'Please select product',
                'zip.required' => 'Valid zip code is required',
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

            $sim_obj = null;
            $esn_obj = null;
            $c_store_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            $account = Account::find($c_store_id);

            ### check denomination ###
            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid denomination provided'
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'msg' => '[The product is not available]'
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->first();
            if (empty($vendor_denom)) {
                return response()->json([
                    'msg' => '[Vendor configuration incomplete]'
                ]);
            }

            ### fee ###
            $rtr_month = 1;
            $fee        = $vendor_denom->fee * $rtr_month;
            $pm_fee     = $vendor_denom->pm_fee * $rtr_month;

            if(!empty($request->sim)) {
                $sim_obj = StockSim::where('sim_serial', $request->sim)->where('product', $product->id)->first();
            }

            if(!empty($request->esn)) {
                $esn_obj = StockESN::where('esn', $request->esn)->where('product', $product->id)->first();
            }

            ### collection_amt ###
            $collection_amt = $esn_obj->type == 'R' ? $denom->denom : 0;

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
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product->id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($c_store_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'msg' => $ret['error_msg']
                    ]);
                }
                $net_revenue = $ret['net_revenue'];
            }

            ### final_fee ###
            $final_fee = ($fee + $pm_fee)
                + (empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge)
                - (empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate)
                + (empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge)
                - (empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate);

            $total = $collection_amt + $final_fee;

            $trans = new Transaction;
            $trans->account_id = $c_store_id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->city = $request->city;
            $trans->state = $request->state;
            $trans->zip = $request->zip;
            $trans->created_by = 'cstore';
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = '';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->invoice_number  = $request->invoice_number;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;
            $trans->network = 'SPR';

            $trans->save();

            ### log payment ###
            $pmt = new RTRPayment;
            $pmt->trans_id = $trans->id;
            $pmt->payer_id = $request->payer_id;
            $pmt->payment_id = $request->payment_id;
            $pmt->payment_token = $request->payment_token;
            $pmt->cdate = Carbon::now();
            $pmt->save();

            return response()->json([
                'msg' => '',
                'invoice_no' => $trans->invoice_number,
                'transaction_no' => $trans->id,
                'sim' => $trans->sim,
                'esn' => $trans->esn,
                'call_back_phone' => $trans->call_back_phone,
                'email' => $trans->email,
                'product' => $product->name,
                'rtr_month' => $trans->rtr_month,
                'phone' => $trans->phone,
                'sub_total' => $trans->denom,
                'fee' => $final_fee,
                'total' => $total
            ]);

        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - account id : ' . $c_store_id . '<br/>';
            $msg .= ' - name : C Store ' . '<br/>';
            $msg .= ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Gen Mobile Activation (E-Com) Failed', $msg);

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function process_tmo(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'sim' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'call_back_phone' => 'required',
                'email' => 'required'
            ], [
                'denom_id.required' => 'Please select product',
                'zip.required' => 'Valid zip code is required',
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

            $sim_obj = null;
            $esn_obj = null;
            $c_store_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;

            $account = Account::find($c_store_id);

            ### check denomination ###
            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid denomination provided'
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'msg' => '[The product is not available]'
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->first();
            if (empty($vendor_denom)) {
                return response()->json([
                    'msg' => '[Vendor configuration incomplete]'
                ]);
            }

            ### fee ###
            $rtr_month = 1;
            $fee        = $vendor_denom->fee * $rtr_month;
            $pm_fee     = $vendor_denom->pm_fee * $rtr_month;

            if(!empty($request->sim)) {
                $sim_obj = StockSim::where('sim_serial', $request->sim)->where('product', $product->id)->first();
            }

            if(!empty($request->esn)) {
                $esn_obj = StockESN::where('esn', $request->esn)->where('product', $product->id)->first();
            }


            ### collection_amt ###
            $collection_amt = $sim_obj->type == 'R' ? $denom->denom : 0;

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
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product->id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($c_store_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'msg' => $ret['error_msg']
                    ]);
                }
                $net_revenue = $ret['net_revenue'];
            }

            ### final_fee ###
            $final_fee = ($fee + $pm_fee)
                + (empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge)
                - (empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate)
                + (empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge)
                - (empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate);

            $total = $collection_amt + $final_fee;

            $trans = new Transaction;
            $trans->account_id = $c_store_id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->city = $request->city;
            $trans->state = $request->state;
            $trans->zip = $request->zip;
            $trans->created_by = 'cstore';
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = '';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->invoice_number  = $request->invoice_number;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;
            $trans->network = 'TMB';

            $trans->save();

            ### log payment ###
            $pmt = new RTRPayment;
            $pmt->trans_id = $trans->id;
            $pmt->payer_id = $request->payer_id;
            $pmt->payment_id = $request->payment_id;
            $pmt->payment_token = $request->payment_token;
            $pmt->cdate = Carbon::now();
            $pmt->save();

            return response()->json([
                'msg' => '',
                'invoice_no' => $trans->invoice_number,
                'transaction_no' => $trans->id,
                'sim' => $trans->sim,
                'esn' => $trans->esn,
                'call_back_phone' => $trans->call_back_phone,
                'email' => $trans->email,
                'product' => $product->name,
                'rtr_month' => $trans->rtr_month,
                'phone' => $trans->phone,
                'sub_total' => $trans->denom,
                'fee' => $final_fee,
                'total' => $total
            ]);

        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - account id : ' . $c_store_id . '<br/>';
            $msg .= ' - name : C Store ' . '<br/>';
            $msg .= ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Gen Mobile Activation (E-Com) Failed', $msg);

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public static function process_after_pay($invoice_number) {
        Helper::log('##### process_after_pay (GEN - Activation) ###' , $invoice_number );
        $trans = null;

        try {
            $trans = Transaction::where('status', 'I')->where('invoice_number', $invoice_number)->first();

            if (empty($trans)) return;

            ### check product ###
            $product = Product::find($trans->product_id);
            if (empty($product)) {
                $trans->status = 'F';
                $trans->note = 'Please select product first';
                $trans->update();
                return;
            }

            if ($product->status != 'A') {
                $trans->status = 'F';
                $trans->note = 'Product is not active. Please contact our customer care.';
                $trans->update();
                return;
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $trans->denom_id)
                ->where('status', 'A')
                ->first();

            if (empty($vendor_denom)) {
                $trans->status = 'F';
                $trans->note = '$' . number_format($trans->denom) . ' is not supported by the vendor [' . $product->vendor_code . ']';
                $trans->update();
                return;
            }

            $params = new \stdClass();
            $params->trans_id = $trans->id;
            $params->esn = $trans->esn;
            $params->sim = $trans->sim;
            $params->zip = $trans->zip;
            $params->city = $trans->city;
            $params->state = $trans->state;
            $params->act_pid = $vendor_denom->act_pid;
            $params->network = $trans->network;

            Helper::log('##### Start Gen Activation (eCom) ###', $vendor_denom->act_pid);

            $ret = gen::Activate($params);

            Helper::log('### GEN API RESULT ###', [
                'ret' => $ret
            ]);

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                $trans->mdate = Carbon::now();
                $trans->modified_by = 'paypal';
                $trans->api = 'Y';
                $trans->save();

                $msg = ' - Trans.ID : ' . $trans->id . '<br/>';
                $msg .= ' - msg : ' . $trans->note . '<br/>';

                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] GEN E-commerce Activation Failed. Please be sure to refund Paypal, or make completion by contacting the vendor.', $msg );

                return response()->json([
                    'code' => '-7',
                    'data' => [
                        'fld'   => 'Your request has been failed. [V]',
                        'msg'   => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = 'C';
            $trans->note    .= ' ' . $ret['error_msg'];
            $trans->phone   = $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = 'paypal';
            $trans->api = 'Y';
            $trans->save();

            $account = Account::find($trans->account_id);

            ### Update ESN status
            $esn_obj = StockESN::where('esn', $trans->esn)->where('product', $trans->product_id)->first();

            ### Pay extra spiff and esn charge, esn rebate
            Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

            if (!empty($esn_obj)) {
                $esn_obj->used_trans_id = $trans->id;
                $esn_obj->used_date = Carbon::now();
                $esn_obj->status = 'U';
                $esn_obj->save();
            }

            ### Update Sim status
            if (!empty($trans->sim)) {
                $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);
            }

            if (!empty($sim_obj)) {
                $sim_obj->used_trans_id = $trans->id;
                $sim_obj->used_date = arbon::now();
                $sim_obj->status = 'U';

                $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $trans->product_id)->where('status', 'A')->first();
                if (!empty($mapping)) {
                    $mapping->status = 'U';
                    $mapping->update();
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
                'cstore',
                false,
                null,
                1,
                $vendor_denom->fee,
                $trans->rtr_month
            );

            if (!empty($ret)) {
                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
            }

            if (getenv('APP_ENV') == 'production') {
                $email = ['register@softpayplus.com', $trans->email];
            } else {
                $email = ['it@jjonbp.com', $trans->email];
            }

            Mail::to($email)
                ->bcc('it@perfectmobileinc.com')
                ->send(new SendPhoneNumber($trans, $account));

            return;
            
        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Gen Mobile Activation (eCom) Failed', $msg);

            return;
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