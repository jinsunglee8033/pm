<?php
/**
 * Created by Royce
 * Date: 6/22/18
 */

namespace App\Http\Controllers\ATT;


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


        return view('att.activate');

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        if (empty($trans)) {
            return redirect('/att/activate');
        }
        
        $trans->product = Product::where('id', $trans->product_id)->first();

        $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();
        if (!empty($sim_obj)) {
            $trans->hide_plan = $sim_obj->hide_plan_amount;
        } else {
            $trans->hide_plan = '';
        }

        return view('att.activate')->with([
            'trans' => $trans
        ]);

        // return self::post($request);

    }

    public function sim(Request $request) {
        try {
            $sim_obj = StockSim::where('product', 'WATTA')->where('sim_serial', $request->sim)->first();

            if (empty($sim_obj) || $sim_obj->status !== 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is not available.'
                ]);
            }

            if (empty($sim_obj->c_store_id)) {
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'sim',
                    'msg'   => 'SIM is not available. Please contact our customer support.'
                  ]
                ]);
            }

            $mapping = StockMapping::where('product', 'WATTA')->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();

            $product_id = 'WATTA';

            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'ATT activation is not ready.'
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

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim'       => $sim_obj->sim_serial,
                    'imei'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim_obj->sub_carrier,
                    'product_id' => $product_id,
                    'plans'     => $plans,
                    'hide_plan' => $sim_obj->hide_plan_amount,
                    'plan_description' => $sim_obj->plan_description
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
            $esn_obj = StockESN::where('product', 'WATTA')->where('esn', $request->esn)->first();

            if (empty($esn_obj)) {
                return response()->json([
                    'code'  => '0',
                    'sub_carrier' => ''
                ]);
            }

            $esn_mappings = StockMapping::where('product', 'WATTA')->where('esn', $request->esn)->where('status', 'A')->count();
            if ($esn_mappings > 0) {
                $mapping = StockMapping::where('product', 'WATTA')->where('esn', $request->esn)->where('sim', $request->sim)->where('status', 'A')->count();

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
                'zip_code' => 'required|regex:/^\d{5}$/',
                'area_code' => 'required|regex:/^\d{3}$/',
            ], [
                'denom_id.required' => 'Please select product',
                'zip_code.required' => 'Valid zip code is required',
                'area_code.required' => 'Valid area code is required'
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

            $sim_obj = StockSim::where('product', 'WATTA')->where('sim_serial', $request->sim)->where('status', 'A')->first();
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

            $esn_obj = StockESN::where('product', 'WATTA')->where('esn', $request->imei)->first();
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

                if ($c_store_id != $esn_obj->c_store_id) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'esn',
                            'msg'   => 'SIM & ESN are belong to different C.Store.ID. Please contact our customer support.'
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

            $tid = Helper::get_att_tid($account);
            if (empty($tid)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'Cannot activate the sim card. Please contact our customer support.'
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

            $sim_type = StockSim::get_sim_type($request->imei, $request->sim, $denom->product_id);
            if ($sim_type == 'X') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Unable to determine SIM type.'
                    ]
                ]);
            }

            $sim_obj = null;
            $esn_obj = null;

            $sim_obj = StockSim::where('product', 'WATTA')->where('sim_serial', $request->sim)->where('status', 'A')->first();
            if (empty($sim_obj)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'sim',
                        'msg'   => 'Please enter valid SIM.'
                    ]
                ]);
            }


            $esn_obj = StockESN::where('product', 'WATTA')->where('esn', $request->imei)->first();

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

                if ($sim_obj->rtr_month != $esn_obj->rtr_month) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'imei',
                            'msg'   => 'SIM and IMEI have different RTR Months'
                        ]
                    ]);
                }
            }

            ### fee ###
            $rtr_month = 1;
            $fee = $sim_type == 'R' ? $vendor_denom->fee : 0;
            $pm_fee = $sim_type == 'R' ? $vendor_denom->pm_fee : 0;
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
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($denom->product_id, $denom->denom, 'S', $account->id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }
            
            $limit_amount_to_check = $collection_amt + $fee + $pm_fee - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check > 0) {
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
            $trans->esn = $request->imei;
            $trans->zip = $request->zip_code;
            $trans->npa = $request->area_code;
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

            ### Call GSS API ###
            // ActivatePhone($trans_id, $pid, $sim, $imei, $zip, $area_code, $tid)
            $ret = gss::ActivatePhone($trans->id, $vendor_denom->act_pid, $request->sim, $request->imei,  
                $request->zip_code, $request->area_code, $tid);

            Helper::log('### EMIDA API RESULT ###', [
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

            ### FreeUP might have no SIM for CDMAV / CDMAS
            if (!empty($sim_obj)) {
                ### Update Sim status
                StockSim::where('sim_serial', $sim_obj->sim_serial)
                  ->update([
                    'used_trans_id' => $trans->id,
                    'used_date'     => Carbon::now(),
                    'status'        => 'U'
                  ]);
            }

            if (!empty($esn_obj)) {
                $esn_obj->used_trans_id = $trans->id;
                $esn_obj->used_date = Carbon::now();
                $esn_obj->esn_charge = null;
                $esn_obj->esn_rebate = null;
                $esn_obj->status = 'U';
                $esn_obj->save();

                $mapping = StockMapping::where('product', 'WATTA')->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();
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

                Helper::send_mail('it@perfectmobileinc.com', '[PM][ROK][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
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