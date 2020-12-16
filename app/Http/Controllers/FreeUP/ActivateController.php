<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 11/13/17
 * Time: 10:41 AM
 */

namespace App\Http\Controllers\FreeUP;


use App\Lib\ConsignmentProcessor;
use App\Lib\emida2;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\emida;
use App\Mail\SendPhoneNumber;
use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\Promotion;
use App\Model\RTRPayment;
use App\Model\SpiffSetupSpecial;
use App\Model\Transaction;
use App\Model\VendorDenom;

use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;

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

        // emida::freeUp_portin_status('1112223333', 41);

        return view('freeup.activate');

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);

        if (empty($trans)) {
            return redirect('/freeup/activate');
        }

        $trans->product = Product::where('id', $trans->product_id)->first();

        $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();
        if (!empty($sim_obj)) {
            $trans->hide_plan = $sim_obj->hide_plan_amount;
        } else {
            $trans->hide_plan = '';
        }

        return view('freeup.activate')->with([
            'trans' => $trans
        ]);

        // return self::post($request);

    }

    public function sim(Request $request, $type) {
        try {
            if (empty($request->code)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM or Activation code is required'
                ]);
            }

            if ($type == 'sim') {
                $sim_obj = StockSim::where('sim_serial', $request->code)->whereIn('product', ['WFRUPA', 'WFRUPS'])->first();
            } else {
                $pattern = '/^\d{7}$/';
                if (!preg_match($pattern, $request->code)) {
                    $pattern = '/^\d{9}$/';
                    if (!preg_match($pattern, $request->code)) {
                        return response()->json([
                            'code' => '-2',
                            'msg' => 'Please enter valid activation code.'
                        ]);
                    }
                }
                $sim_obj = StockSim::where('afcode', $request->code)->whereIn('product', ['WFRUPA', 'WFRUPS'])->first();
            }

            if (empty($sim_obj)){
                $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
                $p = Product::where('id', 'WFRUPA')->first();
                $sim_obj = StockSim::upload_byos($request->code, '', 'WFRUPA', $p->carrier, $p->sim_group, $acct_id, 'C-Store RTR');
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
                    'msg' => 'SIM is not available.'
                ]);
            }

            $mapping = StockMapping::where('sim', $sim_obj->sim_serial)->whereIn('product', ['WFRUPA', 'WFRUPS'])->where('status', 'A')->first();

            if (!in_array($sim_obj->type, ['P', 'R'])) {
                return response()->json([
                    'code' => '-2',
                    'exception' => 'Only Wallet or Regular SIM is allowed'
                ]);
            }

            $product_id = $sim_obj->sub_carrier == 'ATT' ? 'WFRUPA' : 'WFRUPS';

            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'FreeUP activation is not ready.'
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
                    'afcode'    => $sim_obj->afcode,
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

    public function imei(Request $request) {
        try {
            $esn_obj = StockESN::where('esn', $request->imei)->whereIn('product', ['WFRUPA', 'WFRUPS'])->first();

            if (empty($esn_obj)) {
                $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
                $p = Product::where('id', 'WFRUPA')->first();
                $esn_obj = StockESN::upload_byod($request->imei, 'WFRUPA', $p->carrier, $acct_id, 'C-Store RTR');
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

            $special_spiffs = null;

            $denom   = Denom::find($request->denom_id);

            $product_id = $denom->product_id;
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('product', $product_id)->first();
            $esn_obj = StockESN::where('esn', $request->imei)->where('product', $product_id)->first();

            ### Special Spiff
            $terms = array();

            if (!empty($sim_obj)) {
                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                    $product_id, $denom->denom, 'S', $sim_obj->c_store_id, $sim_obj, $esn_obj, $terms
                );
            }

            $spiff_labels = Array();

            if (!empty($sim_obj)) {
                $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
                if ($extra_spiff > 0) {
                    $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';
                }
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
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->first();

            if($vendor_denom->fee == null || $vendor_denom->fee == 0){
                $activation_fee = 0;
            }else{
                $activation_fee = $vendor_denom->fee;
            }

            $sim_type = StockSim::get_sim_type($request->imei, $request->sim, $denom->product_id);
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
                    'total'             => $total,
                    'button'            => '',
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
                'sim' => 'nullable|regex:/^\d{5,30}$/',
                'handset_os' => 'required',
                'imei' => 'required_if:sub_carrier,ATT',
                'esn' => 'required_if:sub_carrier,SPRINT',
                'zip_code' => 'required|regex:/^\d{5}$/'
            ], [
                'denom_id.required' => 'Please select product',
                'handset_os.required' => 'Please select Handset OS',
                'imei.required_if' => 'IMEI is required',
                'esn.required_if' => 'ESM/MEID is required',
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

            if (!empty($request->afcode)) {
                $sim_obj = StockSim::where('afcode', $request->afcode)->whereIn('product', ['WFRUPA', 'WFRUPS'])->where('status', 'A')->first();

                if (empty($sim_obj)) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'afcode',
                            'msg'   => 'Please enter valid activation code.'
                        ]
                    ]);
                }
                $c_store_id = $sim_obj->c_store_id;
            }

            if ($request->sub_carrier == 'ATT') {
                if (empty($request->afcode)) {
                    $sim_obj = StockSim::where('sim_serial', $request->sim)->whereIn('product', ['WFRUPA', 'WFRUPS'])->where('status', 'A')->first();

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
                    $c_store_id = $sim_obj->c_store_id;
                }

                $esn_obj = StockESN::where('esn', $request->imei)->whereIn('product', ['WFRUPA', 'WFRUPS'])->first();

            // END if ($request->sub_carrier == 'ATT') 
            } else {
                if (empty($request->afcode) && !empty($request->sim)) {
                    $sim_obj = StockSim::where('sim_serial', $request->sim)->whereIn('product', ['WFRUPA', 'WFRUPS'])->where('status', 'A')->first();

                    if (empty($sim_obj)) {
                        return response()->json([
                            'code' => '-2',
                            'data' => [
                                'fld'   => 'sim',
                                'msg'   => 'Please enter valid sim number.'
                            ]
                        ]);
                    }
                }

                $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', ['WFRUPA', 'WFRUPS'])->first();

                if (!empty($esn_obj) && !in_array($esn_obj->status, ['A', 'U'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Invalid ESN/IMEI provided.'
                        ]
                    ]);
                }

            // END if ($request->sub_carrier == 'SPRINT') 
            }

            if (!empty($esn_obj)) {
//                if ($c_store_id != $esn_obj->c_store_id) {
//                    return response()->json([
//                        'code' => '-2',
//                        'data' => [
//                            'fld'   => 'esn',
//                            'msg'   => 'SIM & ESN are belong to different C.Store.ID. Please contact our customer support.'
//                        ]
//                    ]);
//                }

                if (!in_array($esn_obj->type, ['P', 'R'])) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'Only Wallet or Regular ESN/IMEI is allowed.'
                        ]
                    ]);
                }
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


            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            if ($sim_type == 'X') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Unable to determine SIM type.'
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
            $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
            $rebate_amt = $ret_rebate['rebate_amt'];

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn);
            $spiff_amt = $ret_spiff['spiff_amt'];
            
            $limit_amount_to_check = $collection_amt + $fee + $pm_fee - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check > 0) {

//                if ($sim_type == 'R') {
//                    return response()->json([
//                        'code' => '-2',
//                        'data' => [
//                            'fld'   => 'exception',
//                            'msg'   => 'The SIM or ESN is not allowed for C.Store activation with current denom $' . $denom->denom . ' CAM $' . $limit_amount_to_check
//                        ]
//                    ]);
//                }

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
            $trans->esn = $denom->product_id == 'WFRUPA' ? $request->imei : $request->esn;
            $trans->zip = $request->zip_code;
            $trans->created_by = 'cstore';
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->call_back_phone = $request->call_back_phone;
            $trans->email = $request->email;
            $trans->phone_type = empty($request->sim) ? '3g' : '4g';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;
            $trans->save();

            ### Call EMIDA API ###
            $ret = emida2::freeUp_activation($request->sub_carrier, $vendor_denom->act_pid, $vendor_denom->pin_pid,
            //$ret = emida::freeUp_activation($request->sub_carrier, $vendor_denom->act_pid, $vendor_denom->pin_pid,
                $request->afcode, $request->sim , $request->handset_os, $request->imei, $request->esn, 
                $request->zip_code, $trans->id);

            Helper::log('### EMIDA API RESULT ###', [
                'ret' => $ret
            ]);

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
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

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    = $ret['error_msg'];
            $trans->phone   = $ret['min'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = 'cstore';
            $trans->api = 'Y';
            $trans->save();

            ### FreeUP might have no SIM for CDMAV / CDMAS
            if (!empty($sim_obj)) {
                $sim_obj->used_trans_id = $trans->id;
                $sim_obj->used_date = Carbon::now();
                $sim_obj->status = 'U';
                $sim_obj->save();
            }

            if (!empty($esn_obj)) {
                $esn_obj->used_trans_id = $trans->id;
                $esn_obj->used_date = Carbon::now();
                $esn_obj->esn_charge = null;
                $esn_obj->esn_rebate = null;
                $esn_obj->status = 'U';
                $esn_obj->save();

                if (!empty($sim_obj)) {
                    $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();
                    if (!empty($mapping)) {
                        $mapping->status = 'U';
                        $mapping->update();
                    }
                }
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);
            if (!empty($ret['error_code'])) {
                ### send message only ###
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                Helper::send_mail('it@perfectmobileinc.com', '[PM][ROK][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
            }

            ### rebate ###
            if (!empty($trans->esn)) {
                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $denom->denom * $rtr_month - $spiff_amt, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                if (!empty($ret['error_code'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ROK][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
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
                    'msg'   => $trans->note . ' [MDN: ' . $trans->phone . ']'
                ]
            ]);
        } catch (\Exception $ex) {
            $msg = $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();

            Helper::log('### EXCEPTION ###', [
                'exception' => $msg
            ]);

            return response()->json([
                'code' => '-9',
                'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'System error. Please try later.'
                ]
            ]);
        }
    }

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
                'denom_id' => 'required',
                'sim' => 'required',
                'imei' => 'required',
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
                ->where('status', 'A')
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

            if(!empty($request->imei)) {
                $esn_obj = StockESN::where('esn', $request->imei)->where('product', $product->id)->first();
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

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->imei);
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
            $trans->esn = $request->imei;
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
            $trans->phone_type = $request->handset_os;
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
                'imei' => $trans->esn,
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

            Helper::send_mail('it@perfectmobileinc.com', 'FreeUP Mobile Activation (E-Com) Failed', $msg);

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public static function process_after_pay($invoice_number) {
        Helper::log('##### process_after_pay (FreeUP - Activation) ###' , $invoice_number );
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

            Helper::log('##### Start FreeUP Activation (eCom) ###', $vendor_denom->act_pid);

            $ret = emida2::freeUp_activation('ATT', $vendor_denom->act_pid, $vendor_denom->pin_pid,
                '', $trans->sim , $trans->phone_type, $trans->esn, '',
                $trans->zip, $trans->id);

            Helper::log('### Emida2 API RESULT ###', [
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

                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] FreeUP E-commerce Activation Failed. Please be sure to refund Paypal, or make completion by contacting the vendor.', $msg );

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
            $trans->phone   = $ret['min'];
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
                $sim_obj->used_date = Carbon::now();
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
                Helper::send_mail('it@perfectmobileinc.com', '[PM][FreeUP][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
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

            Helper::send_mail('it@perfectmobileinc.com', 'FreeUP Mobile Activation (eCom) Failed', $msg);

            return;
        }

    }
}