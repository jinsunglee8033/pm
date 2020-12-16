<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/21/18
 * Time: 4:46 PM
 */

namespace App\Http\Controllers\ROK;


use App\Http\Controllers\Controller;
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
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class ActivateController extends Controller
{

    public function show(Request $request) {

        $type = '';
        $has_mapping_esn = false;
        $has_mapping_sim = false;
        $esn_status = null;
        $allowed_months = '1|2|3';
        $allowed_products = 'WROKC|WROKG|WROKS';
        $esn = $request->esn;
        if ($esn == '123456789') {
            $esn = '';
        }
        $denom = Denom::find($request->denom_id);
        $rtr_month = $request->get('rtr_month');
        $allowed_denoms = '';
        $selected_product_id = '';

        $phone_type = $request->input('phone_type', old('phone_type'));
        if (!empty($denom)) {
            if ($denom->product_id == 'WROKG') {
                $phone_type = '4g';
            }

            if ($denom->product_id == 'WROKC') {
                $phone_type = '4g';
            }
        }

        if (!empty($esn)) {
            $esn_obj = ROKESN::find($esn);
            if (!empty($esn_obj)) {
                ### allow re-use of ESN ###
                /*
                if (!empty($esn_obj->used_trans_id)) {
                    return back()->withErrors([
                        'exception' => 'ESN/IMEI already used for ROK activation transaction'
                    ])->withInput();
                }
                */

                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return back()->withErrors([
                        'exception' => 'ESN/IMEI status is not active : ' . $esn_obj->status
                    ])->withInput();
                }

                $esn_status = $esn_obj->status;

                if (!in_array($esn_obj->type, ['P', 'R'])) {
                    return back()->withErrors([
                        'exception' => 'Only Preload or Regular ESN is allowed'
                    ])->withInput();
                }

                if (!empty($esn_obj->rtr_month)) {
                    $allowed_months = $esn_obj->rtr_month;
                }

                switch ($esn_obj->sub_carrier) {
                    case 'CDMA':
                        $allowed_products = 'WROKC';
                        $selected_product_id = 'WROKC';
                        break;
                    case 'GSM':
                        $allowed_products = 'WROKG';
                        $selected_product_id = 'WROKG';
                        break;
                    case 'SPR':
                        $allowed_products = 'WROKS';
                        $selected_product_id = 'WROKS';
                        break;
                }

                $allowed_denoms = $esn_obj->amount;

                if (
                    empty($request->sim) &&
                    isset($denom) &&
                    (
                        !in_array($denom->product_id, explode('|', $allowed_products)) ||
                        !in_array($denom->denom, explode('|', $allowed_denoms)) && count(explode('|', $allowed_denoms)) > 0
                    )
                ) {
                    if ($esn_obj->type != 'R') $denom = null;
                }

                $mapping_count = ROKMapping::where('esn',$esn)->where('status', 'A')->count();
                if (!empty($mapping_count) && $mapping_count > 0) {
                    $has_mapping_sim = true;
                }

                $type = $esn_obj->type;
            } else {
                return back()->withErrors([
                    'esn' => 'ESN is not in our system'
                ])->withInput();
            }
        }

        if (!empty($request->sim)) {

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

                if (!in_array($sim->type, ['P', 'R'])) {
                    return back()->withErrors([
                        'exception' => 'Only Wallet or Regular SIM is allowed'
                    ])->withInput();
                }

                if (!empty($sim->rtr_month)) {
                    $allowed_months = $sim->rtr_month;
                }

                switch ($sim->sub_carrier) {
                    case 'CDMA':
                        $allowed_products = 'WROKC';
                        $selected_product_id = 'WROKC';
                        break;
                    case 'GSM':
                        $allowed_products = 'WROKG';
                        $selected_product_id = 'WROKG';
                        break;
                    case 'SPR':
                        $allowed_products = 'WROKS';
                        $selected_product_id = 'WROKS';
                        break;
                }

                $allowed_denoms = $sim->amount;

                if (
                    isset($denom) &&
                    (
                        !in_array($denom->product_id, explode('|', $allowed_products)) ||
                        !in_array($denom->denom, explode('|', $allowed_denoms)) && count(explode('|', $allowed_denoms)) > 0
                    )
                ) {
                    if ($sim->type != 'R') $denom = null;
                }

                $mapping = ROKMapping::where('sim',$request->sim)->where('status', 'A')->first();
                if (!empty($mapping)) {
                    $request->esn = $mapping->esn;
                    $has_mapping_esn = true;
                } else {
                    if ($has_mapping_sim) {
                        return back()->withErrors([
                            'sim' => 'SIM [' . $request->sim . '] can not activate the device [' . $esn . '] !!'
                        ])->withInput();
                    }
                }

                $type = $sim->type;

            } else {
                return back()->withErrors([
                    'sim' => 'SIM is not in our system'
                ])->withInput();
            }
        }

        if (!in_array($rtr_month, explode('|', $allowed_months)) && count(explode('|', $allowed_months)) > 0) {
            $rtr_month = explode('|', $allowed_months)[0];
        }


        if (empty($selected_product_id) && !empty($denom)) {
            $selected_product_id = $denom->product_id;
        }

        ### CDMA denoms ###
        $denoms_cdma = Denom::join('product', 'product.id', 'denomination.product_id')
            ->join('vendor_denom', function($join) {
                $join->on('vendor_denom.product_id', 'product.id');
                $join->on('vendor_denom.vendor_code', 'product.vendor_code');
                $join->on('denomination.denom', 'vendor_denom.denom');
            })->where('denomination.product_id', 'WROKC')
            ->where('denomination.status', 'A')
            ->where('vendor_denom.act_pid', '!=', '')
            ->where('vendor_denom.status', 'A')
            ->where('product.status', 'A')
            ->select('denomination.*')
            ->get();

        $denoms_gsm = Denom::join('product', 'product.id', 'denomination.product_id')
            ->join('vendor_denom', function($join) {
                $join->on('vendor_denom.product_id', 'product.id');
                $join->on('vendor_denom.vendor_code', 'product.vendor_code');
                $join->on('denomination.denom', 'vendor_denom.denom');
            })->where('denomination.product_id', 'WROKG')
            ->where('denomination.status', 'A')
            ->where('vendor_denom.act_pid', '!=', '')
            ->where('vendor_denom.status', 'A')
            ->where('product.status', 'A')
            ->select('denomination.*')
            ->get();

        $denoms_spr = Denom::join('product', 'product.id', 'denomination.product_id')
            ->join('vendor_denom', function($join) {
                $join->on('vendor_denom.product_id', 'product.id');
                $join->on('vendor_denom.vendor_code', 'product.vendor_code');
                $join->on('denomination.denom', 'vendor_denom.denom');
            })->where('denomination.product_id', 'WROKS')
            ->where('denomination.status', 'A')
            ->where('vendor_denom.act_pid', '!=', '')
            ->where('vendor_denom.status', 'A')
            ->where('product.status', 'A')
            ->select('denomination.*')
            ->get();

        return view('rok.activate', [
            'type'  => $type,
            'denoms_cdma' => $denoms_cdma,
            'denoms_gsm' => $denoms_gsm,
            'denoms_spr' => $denoms_spr,
            'denom_id' => isset($denom) ? $denom->id : '',
            'allowed_months' => $allowed_months,
            'allowed_products' => $allowed_products,
            'allowed_denoms' => $allowed_denoms,
            'esn' => $request->esn,
            'sim' => $request->sim,
            'has_mapping_esn' => $has_mapping_esn,
            'esn_status' => $esn_status,
            'rtr_month' => $rtr_month,
            'phone_type' => $phone_type,
            'register_for_rebate' => $request->register_for_rebate,
            'zip' => $request->zip,
            'selected_product_id' => $selected_product_id
        ]);
    }

    public function post(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'esn' => 'required',
                'sim' => 'nullable|regex:/^\d{5,30}$/',
                //'npa' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'rtr_month' => 'required|in:1,2,3'
            ], [
                'npa.required' => 'Pref.Area Code is required',
                'esn.erquired' => 'ESN/IMEI is required',
                'denom_id.required' => 'Please select product',
                'rtr_month.required' => 'Please select activation month'
            ]);

            $esn = $request->esn;
            if ($esn == '123456789') {
                $esn = '';
            }

            $all_sessions = $request->session()->all();
            foreach ($all_sessions as $key => $val) {
                Session::put($key, $val);
            }

            if ($v->fails()) {
                return back()->withErrors($v)
                    ->withInput();
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return back()->withErrors([
                    'exception' => 'Invalid denomination provided'
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
                    'exception' => 'ESN / IMEI is required'
                ])->withInput();
            }

            if (($product->id == 'WROKG' || $request->phone_type == '4g') && empty($request->sim)) {
                return back()->withErrors([
                    'exception' => 'SIM is required'
                ])->withInput();
            }

            if ($product->id == 'WROKS' && $request->phone_type == '3g' && $esn != '' &&  !ctype_alnum($esn)) {
                return back()->withErrors([
                    'exception' => 'Please enter valid ESN/IMEI - alpha numeric only'
                ])->withInput();
            }

            if ($product->id == 'WROKS' && $request->phone_type == '4g' && $esn != '' &&  !is_numeric($esn)) {
                return back()->withErrors([
                    'exception' => 'Please enter valid ESN/IMEI - digits only'
                ])->withInput();
            }

            if ($product->id == 'WROKC' && $request->phone_type == '4g' && $esn != '' &&  !is_numeric($esn)) {
                return back()->withErrors([
                    'exception' => 'Please enter valid ESN/IMEI - digits only'
                ])->withInput();
            }

            if (empty($request->sim) && $product->id == 'WROKC' && $request->phone_type == '4g') {
                return back()->withErrors([
                    'exception' => 'SIM is required for ROK - CDMA with 4g phone'
                ])->withInput();
            }

            if (empty($request->phone_type) && $product->id != 'WROKG') {
                return back()->withErrors([
                    'exception' => 'Phone type is required'
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

            $c_store_id = null;
            $esn_sub_carrier = null;
            $esn_rtr_month = null;
            $esn_obj = ROKESN::find($esn);
            if (!empty($esn)) {
                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return back()->withErrors([
                        'exception' => 'Invalid ESN/IMEI provided'
                    ])->withInput();
                }

                if (!in_array($esn_obj->type, ['P', 'R'])) {
                    return back()->withErrors([
                        'exception' => 'Only preload or regular SIM is allowed'
                    ])->withInput();
                }

                if ((empty($esn_obj->amount) && $esn_obj->type != 'R') || !in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    return back()->withErrors([
                        'exception' => 'Plan $' . $denom->denom . ' not is allowed to the ESN'
                    ])->withInput();
                }

                ### allow re-use of ESN ###
                /*if (!empty($esn_obj->used_trans_id)) {
                    return back()->withErrors([
                        'exception' => 'ESN/IMEI already used for ROK activation transaction'
                    ])->withInput();
                }*/

                $c_store_id = $esn_obj->c_store_id;
                $esn_sub_carrier = $esn_obj->sub_carrier;
                $esn_rtr_month = $esn_obj->rtr_month;
            }

            $sim = ROKSim::find($request->sim);
            if (!empty($sim)) {
                if ($sim->status != 'A') {
                    return back()->withErrors([
                        'exception' => 'Invalid SIM provided'
                    ])->withInput();
                }

                if (!in_array($sim->type, ['P', 'R'])) {
                    return back()->withErrors([
                        'exception' => 'Only preload or regular SIM is allowed'
                    ])->withInput();
                }

                if (!empty($sim->used_trans_id)) {
                    return back()->withErrors([
                        'exception' => 'SIM already used for ROK activation transaction'
                    ])->withInput();
                }

                if ((empty($sim->amount) && $sim->type != 'R') || !in_array($denom->denom, explode('|', $sim->amount))) {
                    return back()->withErrors([
                        'exception' => 'Plan $' . $denom->denom . ' is not allowed to the SIM'
                    ])->withInput();
                }

                if (!empty($esn_sub_carrier) && ($esn_sub_carrier != $sim->sub_carrier)) {
                    return back()->withErrors([
                        'exception' => 'SIM and ESN are not belong to same carrier'
                    ])->withInput();
                }

                if (!empty($esn_rtr_month) && ($esn_rtr_month != $sim->rtr_month)) {
                    return back()->withErrors([
                        'exception' => 'SIM and ESN have different RTR Months'
                    ])->withInput();
                }

                if (!empty($c_store_id) && $c_store_id != $sim->c_store_id) {
                    return back()->withErrors([
                        'exception' => 'SIM & ESN C.Store.ID is different'
                    ]);
                }

                $c_store_id = $sim->c_store_id;
            }

            if (!empty($esn) && empty($esn_obj)) {
                return back()->withErrors([
                    'exception' => 'BYOD Device is not allowed for C.Store activation'
                ])->withInput();
            }

            if (!empty($request->sim) && empty($sim)) {
                return back()->withErrors([
                    'exception' => 'BYOD SIM is not allowed for C.Store activation'
                ])->withInput();
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->first();
            if (empty($vendor_denom)) {
                return back()->withErrors([
                    'exception' => 'Vendor configuration incomplete.'
                ])->withInput();
            }


            //$user = Auth::user();
            $account = Account::find($c_store_id);
            if (empty($account)) {
                return back()->withErrors([
                    'exception' => 'SIM or ESN does not belong to C.Store. Please contact our customer support.'
                ])->withInput();
            }

            if ($account->c_store != 'Y') {
                return back()->withErrors([
                    'exception' => 'SIM or ESN account is not registered as C.Store. Please contact our customer support.'
                ])->withInput();
            }

            ### fee ###
            $rtr_month = $request->rtr_month;
            $fee = 0;       // Fee will be ignored for C.Store activation.
            $pm_fee = 0;    // PM.Fee will be ignored for C.Store activation.

            $sim_type = ROKSim::get_sim_type($request->phone_type, $denom->product_id, $esn, $request->sim);
            if ($sim_type ==  'X') {
                return back()->withErrors([
                    'exception' => 'Unable to determine SIM type'
                ])->withInput();
            }

            ### get collection amount ###
            $collection_amt = $sim_type == 'R' ? $denom->denom * $rtr_month : 0;

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
                if ($sim_type == 'R') {
                    return back()->withErrors([
                        'exception' => 'The SIM or ESN is not allowed for C.Store activation with current denom $' . $denom->denom . ' CAM $' . $limit_amount_to_check
                    ])->withInput();
                }

                $ret = PaymentProcessor::check_limit($c_store_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return back()->withErrors([
                        'exception' => $ret['error_msg']
                    ])->withInput();
                }

                $net_revenue = $ret['net_revenue'];
            }

            ### TODO: cost ? ###
            $npa = '000';

            $trans = new Transaction;
            $trans->account_id = $c_store_id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'Activation';
            $trans->denom = $denom->denom;
            $trans->sim = $request->sim;
            $trans->esn = $esn;
            $trans->npa = $npa;
            $trans->first_name = '';
            $trans->last_name = '';
            $trans->address1 = '';
            $trans->address2 = '';
            $trans->city = '';
            $trans->state = '';
            $trans->zip = $request->zip;
            $trans->phone = '';
            $trans->current_carrier = '';
            $trans->account_no = '';
            $trans->account_pin = '';
            $trans->first_name = '';
            $trans->last_name = '';
            $trans->call_back_phone = $account->office_number;
            $trans->email = '';
            $trans->pref_pin = '';
            $trans->created_by = 'cstore';
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->note = '';
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

            $ret = reup::activation($trans->sim, $esn, $vendor_denom->act_pid, $trans->phone_type,
                $trans->first_name, $trans->last_name, $trans->address1, $trans->address2,
                $trans->city, $trans->state, $trans->zip, $trans->npa, $trans->email
            );

            Helper::log('### ROK API RESULT ###', [
                'ret' => $ret
            ]);

            if (!empty($ret['error_msg'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                $trans->mdate = Carbon::now();
                $trans->modified_by = 'cstore';
                $trans->api = 'Y';
                $trans->save();

                return back()->withErrors([
                    'exception' => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                ])->withInput();
            }

            $trans->status = 'C';
            $trans->phone = $ret['min'];
            $trans->vendor_tx_id = $ret['tx_id'];
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'cstore';
            $trans->api = 'Y';
            $trans->save();

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
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, $trans->phone_type, $trans->sim, $trans->esn);
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
                'cstore',
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
                    'cstore',
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

            ### update balance ###
            //Helper::update_balance();

            Helper::log('### API call completed ###');

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

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ])->withInput();
        }
    }
}