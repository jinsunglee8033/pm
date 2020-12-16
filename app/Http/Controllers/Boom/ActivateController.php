<?php
/**
 * Created by Jin
 * Date: 7/13/20
 */

namespace App\Http\Controllers\Boom;


use App\Lib\boom;
use App\Lib\ConsignmentProcessor;
use App\Lib\gen;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\gss;
use App\Mail\SendPhoneNumber;
use App\Model\Account;
use App\Model\Denom;
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

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ActivateController
{
    public function show(Request $request) {


        $states = State::all();

        return view('boom.activate')->with([
            'states'   => $states
        ]);

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        if (empty($trans)) {
            return redirect('/boom/activate');
        }

        $trans->product = Product::where('id', $trans->product_id)->first();

        $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();
        if (!empty($sim_obj)) {
            $trans->hide_plan = $sim_obj->hide_plan_amount;
        } else {
            $trans->hide_plan = '';
        }

        return view('boom.activate')->with([
            'trans' => $trans
        ]);

        // return self::post($request);

    }

    public function sim(Request $request) {
        try {

            $sim_obj = StockSIM::where('sim_serial', $request->sim)->where('sim_group', 'BoomBlue')->first();
            $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;

            if (empty($sim_obj)) {
                $p = Product::where('id', 'WBMBA')->first();
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WBMBA', $p->carrier, $p->sim_group, $acct_id, 'C-Store RTR');
                $c_store_id = null;
            }else{
                if(!empty($sim_obj->c_store_id)){
                    $c_store_id = $sim_obj->c_store_id;
                }else{
                    $c_store_id = null;
                }
            }

            if ($sim_obj->status !== 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                ]);
            }

            $mapping = StockMapping::where('product', 'WBMBA')->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();

            $product_id = 'WBMBA';

            $product_obj = Product::where('id', $product_id)->first();
            if ($product_obj->status !='A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Product is not Active.'
                ]);
            }
            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'BOOM Blue activation is not ready.'
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

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim'       => $sim_obj->sim_serial,
                    'imei'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim_obj->sub_carrier,
                    'product_id' => $product_id,
                    'plans'     => $plans,
                    'hide_plan' => $sim_obj->hide_plan_amount,
                    'plan_description' => $sim_obj->plan_description,
                    'button' => empty($c_store_id) ? 'paypal' : 'normal',
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
            $esn_obj = StockESN::where('product', 'WBMBA')->where('esn', $request->esn)->first();
            if (empty($esn_obj)) {
                return response()->json([
                    'code' => '0',
                    'sub_carrier' => ''
                ]);
            }
            $esn_mappings = StockMapping::where('product', 'WBMBA')->where('esn', $request->esn)->where('status', 'A')->count();
            if ($esn_mappings > 0) {
                $mapping = StockMapping::where('product', 'WBMBA')->where('esn', $request->esn)->where('sim', $request->sim)->where('status', 'A')->count();

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

    public function commission_blue(Request $request) {
        try {
            $spiff = 0;
            $rebate = 0;
            $product_id = 'WBMBA';
            $special_spiffs = null;
            $denom   = Denom::find($request->denom_id);
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', 'BoomBlue')->first();

            $account_id = $sim_obj->c_store_id;

            if(empty($account_id)){
                $account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            }

//            if(empty($account_id)) {
//                return response()->json([
//                    'code'  => '-9',
//                    'msg'   => 'No Cstore id'
//                ]);
//            }

            // BYOD allow or not..?
            if($request->imei) {
                $esn_obj = StockESN::where('esn', $request->imei)->where('product', $product_id)->first();

//                if (empty($esn_obj)) {
//                    $account = Account::find(Auth::user()->account_id);
//                    StockESN::upload_byod($request->imei, 'WBMBA', 'Boom Mobile', $account->id, $account->name);
//                }
            }else{
                $esn_obj =null;
            }


            if (!empty($denom)) {

                $account = Account::find($account_id);
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'BoomBlue');
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
                    $product_id, $denom->denom, 'S', $account_id, $sim_obj, $esn_obj, $terms
                );
            }

            $spiff_labels = Array();
            $sim_label = '';
            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);

            if($sim_obj) {

                if ($sim_obj->type == 'P') {
                    if ($spiff == 0) {
                        $sim_label = 'Credit Already Paid' . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = '$ 0';
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

            $activation_fee = number_format($fee + $pm_fee, 2);

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
//                    'spiff_labels'    => $spiff_labels,
                    'activation_fee'  => $activation_fee,
                    'sim_charge'      => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'      => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'      => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'      => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate,
                    'total'           => $total
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
                'denom_id' => 'required',
                'sim' => 'nullable|regex:/^\d{5,30}$/',
                'zip_code' => 'required|regex:/^\d{5}$/'
            ], [
                'denom_id.required' => 'Please select product',
                'zip_code.required' => 'Valid zip code is required'
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

            $sim_obj = StockSim::where('product', 'WBMBA')->where('sim_serial', $request->sim)->where('status', 'A')->first();

            if (empty($sim_obj)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'Please enter valid sim number.'
                    ]
                ]);
            }

            if (!in_array($sim_obj->type, ['P', 'R'])) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'Only Wallet or Regular SIM is allowed'
                    ]
                ]);
            }

            $sim_type = $sim_obj->type;
            $c_store_id = $sim_obj->c_store_id;

            if(!empty($c_store_id)){
                $account_id = $c_store_id;
            }else{
                $account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            }

            $account = Account::find($account_id);

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

            $esn_obj = StockESN::where('product', 'WBMBA')->where('esn', $request->imei)->first();

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

            if (!empty($esn_obj)) {
                if (!in_array($esn_obj->status, ['A', 'U'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Invalid ESN/IMEI provided.'
                        ]
                    ]);
                }
            }else{
                $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
                $esn_obj = StockESN::upload_byod($request->imei, 'WBMBA', $product->carrier, $acct_id, 'C-Store');
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

            ### fee ###
            $rtr_month = 1;
            $fee = $vendor_denom->fee;
            $pm_fee = $vendor_denom->pm_fee;
            ### get collection amount ###
            $collection_amt = $sim_type == 'R' ? $denom->denom : 0;

            ### check sales limit ###
            $net_revenue = 0;

            $rebate_type = empty($esn_obj) ? 'B' : 'R';
            $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->imei);
            $rebate_amt = $ret_rebate['rebate_amt'];

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->imei);
            $spiff_amt = $ret_spiff['spiff_amt'];

            ### Special Spiff
//            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($denom->product_id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
//            if (!empty($special_spiffs)) {
//                foreach ($special_spiffs as $ss) {
//                    $spiff_amt += $ss['spiff'];
//                }
//            }

//            $limit_amount_to_check = $collection_amt + $fee + $pm_fee - $rebate_amt - $spiff_amt;

//            if ($limit_amount_to_check > 0) {
//                $ret = PaymentProcessor::check_limit($c_store_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
//                if (!empty($ret['error_msg'])) {
//                    return response()->json([
//                        'code' => '-2',
//                        'data' => [
//                            'fld'   => 'exception',
//                            'msg'   => $ret['error_msg']
//                        ]
//                    ]);
//                }
//
//                $net_revenue = $ret['net_revenue'];
//            }

            $trans = new Transaction;
            $trans->account_id = $account->id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->imei;
            $trans->zip = $request->zip_code;
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
            $trans->save();

            $params = new \stdClass();
            $params->trans_id   = $trans->id;
            $params->zip        = $trans->zip_code;
            $params->esn        = $trans->esn;
            $params->sim        = $trans->sim;
            $params->act_pid    = $vendor_denom->act_pid;
            $params->first_name = $request->first_name;
            $params->last_name  = $request->last_name;
            $params->address    = $request->address;
            $params->city       = $request->city;
            $params->state      = $request->state;
            $params->email      = 'ops@softpayplus.com';

            ### Call BOOM API ###
            $ret = boom::activationBlue($params);

            $trans->vendor_tx_id = $ret['vendor_tx_id'];

            if ($ret['mdn'] != ''){

                $boom_mdn = $ret['mdn'];
                $cnt = 0;
                /*
                 * 30 * 5 = 100 sec. almost 2 min.
                 */
                while($cnt < 30){
                    $ret2 = boom::getServiceStatus($boom_mdn, 'BLUE');
                    if($ret2['error_code'] == '') {
                        // Activation is complete!
                        $cnt = $cnt+30;
                    }else{
                        $cnt++;
                        sleep(5);
                    }
                }

                if($ret2['error_code'] != ''){
                    // Status check few times but didn't get status complete
                    // But we should deal with complete.
                    // Just sent email
                    $msg_boom = '';
                    $msg_boom .= ' - MDN : ' . $boom_mdn . '<br/>';
                    $msg_boom .= ' - error code : ' . $ret2['error_code'] . '<br/>';
                    $msg_boom .= ' - error msg : ' . $ret2['error_msg'] . '<br/>';
                    $msg_boom .= ' - cnt : ' . $cnt;
                    Helper::send_mail('it@jjonbp.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to obtain Service Status.', $msg_boom);
                }
            }

            Helper::log('### Boom API RESULT ###', [
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
                        'fld'   => 'exception',
                        'msg'   => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = 'C';
            $trans->note    = $ret['error_msg'];
            $trans->phone   = $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = 'cstore';
            $trans->api = 'Y';
            $trans->save();

            ### Pay extra spiff and sim charge, sim rebate
            Promotion::create_by_order($sim_obj, $account, $trans->id);

            if (!empty($sim_obj)) {
                ### Update Sim status
                StockSim::where('sim_serial', $sim_obj->sim_serial)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
                        'product'       => $denom->product_id,
                        'status'        => 'U'
                    ]);
            }

            ### Pay extra spiff and esn charge, esn rebate
            Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

            if (!empty($esn_obj)) {
                $esn_obj->used_trans_id = $trans->id;
                $esn_obj->used_date = Carbon::now();
                $esn_obj->esn_charge = null;
                $esn_obj->esn_rebate = null;
                $esn_obj->status = 'U';
                $esn_obj->save();

                $mapping = StockMapping::where('product', 'WBMBA')->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();
                if (!empty($mapping)) {
                    $mapping->status = 'U';
                    $mapping->update();
                }
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);
            if (!empty($ret['error_msg'])) {
                ### send message only ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                Helper::send_mail('it@perfectmobileinc.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
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

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                }
            }

            Helper::log('### API call completed ###');

            $ret = RTRProcessor::applyRTR(
                1,
                '',
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


    public function process(Request $request){

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'sim' => 'required',
                'esn' => 'required',
                'zip_code' => 'required|regex:/^\d{5}$/',
                'call_back_phone' => 'required',
                'email' => 'required'
            ], [
                'denom_id.required' => 'Please select product',
                'zip_code.required' => 'Valid zip code is required',
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
                if(empty($esn_obj)){
                    $esn_obj = StockESN::upload_byod($request->esn, 'WBMBA', $product->carrier, $account->id, $account->name);
                }
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
//            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product->id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
//            if (!empty($special_spiffs)) {
//                foreach ($special_spiffs as $ss) {
//                    $spiff_amt += $ss['spiff'];
//                }
//            }

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
            $trans->zip = $request->zip_code;
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
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;

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

            Helper::send_mail('it@perfectmobileinc.com', 'Boom Blue Mobile Activation (E-Com) Failed', $msg);

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public static function process_after_pay($invoice_number) {
        Helper::log('##### process_after_pay (Boom - Activation) ###' , $invoice_number );
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

            $params->trans_id   = $trans->id;
            $params->zip        = $trans->zip;
            $params->esn        = $trans->esn;
            $params->sim        = $trans->sim;
            $params->act_pid    = $vendor_denom->act_pid;
            $params->first_name = $trans->first_name;
            $params->last_name  = $trans->last_name;
            $params->address    = $trans->address;
            $params->city       = $trans->city;
            $params->state      = $trans->state;
            $params->email      = 'ops@softpayplus.com';

            Helper::log('##### Start Boom Activation (eCom) ###', $vendor_denom->act_pid);

            $ret = boom::activationBlue($params);

            Helper::log('### Boom API RESULT ###', [
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
                Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
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

            Helper::send_mail('it@perfectmobileinc.com', 'Boom Mobile Activation (eCom) Failed', $msg);

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