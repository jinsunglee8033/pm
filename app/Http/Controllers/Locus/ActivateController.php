<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 11/13/17
 * Time: 10:41 AM
 */

namespace App\Http\Controllers\Locus;


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

use App\Model\FreeUPSim;
use App\Model\FreeUPESN;
use App\Model\FreeUPMapping;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ActivateController
{
    public function show(Request $request) {

        print_r('<br>LocusActivateGSMAfcode:<br>');
        # LocusActivateGSMAfcode($trans_id, $act_prod_id, $pin_prod_id, $afcode, $npa, $city, $zip)
        //$ret = emida2::LocusActivateGSMAfcode(333, '93000020', '388015', '353534534', '201', 'Fort Lee', '07024');
        $ret = emida2::LocusActivateGSMAfcode(333, '93000020', '388015', '353534534', '201', 'Fort Lee', '07024');
        print_r($ret);

        print_r('<br>LocusActivateGSMAfcode:<br>');
        # LocusActivateGSMsim($trans_id, $act_prod_id, $pin_prod_id, $sim, $npa, $city, $zip)
        //$ret = emida2::LocusActivateGSMAfcode(333, '93000020', '388015', '168465404705132', '201', 'Fort Lee', '07024');
        $ret = emida2::LocusActivateGSMAfcode(333, '93000020', '388015', '168465404705132', '201', 'Fort Lee', '07024');
        print_r($ret);

        print_r('<br>LocusCreateMultiLine2Lines:<br>');
        # LocusCreateMultiLine2Lines($trans_id, $product_id, $sim1, $sim2)
        //$ret = emida2::LocusCreateMultiLine2Lines(333, '92000002', '1684654047051321', '1684654047051322');
        $ret = emida2::LocusCreateMultiLine2Lines(333, '92000002', '1684654047051321', '1684654047051322');
        print_r($ret);

        print_r('<br>LocusCreateMultiLine4Lines:<br>');
        # LocusCreateMultiLine4Lines($trans_id, $product_id, $sim1, $sim2, $sim3, $sim4)
        //$ret = emida2::LocusCreateMultiLine4Lines(333, '92000002', '1684654047051321', '1684654047051322', '1684654047051323', '1684654047051324');
        $ret = emida2::LocusCreateMultiLine4Lines(333, '92000002', '1684654047051321', '1684654047051322', '1684654047051323', '1684654047051324');
        print_r($ret);

        // return view('locus.activate');

        // return self::post($request);

    }
    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();

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
                $sim = FreeUPSim::where('sim_serial', $request->code)->first();
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

                $sim = FreeUPSim::where('afcode', $request->code)->first();
            }

            if (empty($sim) || $sim->status !== 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is not available.'
                ]);
            }

            $mapping = FreeUPMapping::where('sim', $sim->sim_serial)->where('status', 'A')->first();

            $account = Account::find($sim->c_store_id);
            if (empty($account)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM does not belong to C.Store. Please contact our customer support.'
                ]);
            }

            if (!in_array($sim->type, ['P', 'R'])) {
                return response()->json([
                    'code' => '-2',
                    'exception' => 'Only Wallet or Regular SIM is allowed'
                ]);
            }

            $product_id = $sim->sub_carrier == 'ATT' ? 'WFRUPA' : 'WFRUPS';

            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'FreeUP activation is not ready.'
                ]);
            }

            $plans = Array();
            if (empty($sim->amount)) {
                foreach ($denoms as $d) {
                    $plans[] = [
                        'denom_id' => $d->id,
                        'denom' => $d->denom,
                        'name'  => $d->name
                    ];
                }
            } else {
                $ds = explode('|', $sim->amount);
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
                    'afcode'    => $sim->afcode,
                    'sim'       => $sim->sim_serial,
                    'imei'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim->sub_carrier,
                    'product_id' => $product_id,
                    'plans'     => $plans
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
            $esn_obj = FreeUPESN::find($request->esn);

            if (empty($esn_obj)) {
                return response()->json([
                    'code'  => '0',
                    'sub_carrier' => ''
                ]);
            }

            $esn_mappings = FreeUPMapping::where('esn', $request->esn)->where('status', 'A')->count();
            if ($esn_mappings > 0) {
                $mapping = FreeUPMapping::where('esn', $request->esn)->where('sim', $request->sim)->where('status', 'A')->count();

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
                $sim_obj = FreeUPSim::where('afcode', $request->afcode)->where('status', 'A')->first();

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
                    $sim_obj = FreeUPSim::where('sim_serial', $request->sim)->where('status', 'A')->first();

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
                }

                $esn_obj = FreeUPESN::where('esn', $request->imei)->first();

            // END if ($request->sub_carrier == 'ATT') 
            } else {
                if (empty($request->afcode) && !empty($request->sim)) {
                    $sim_obj = FreeUPSim::where('sim_serial', $request->sim)->where('status', 'A')->first();

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

                $esn_obj = FreeUPESN::where('esn', $request->esn)->first();

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

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'denom_id',
                        'msg'   => 'Invalid denomination provided.'
                    ]
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product)) {
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
            if (empty($vendor_denom)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Vendor configuration incomplete.'
                    ]
                ]);
            }


            $sim_type = FreeUPSim::get_sim_type($denom->product_id, $request->esn, $request->sim);
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
            $fee = $sim_type == 'R' ? $vendor_denom->fee : 0;
            $pm_fee = $sim_type == 'R' ? $vendor_denom->pm_fee : 0;
            ### get collection amount ###
            $collection_amt = $sim_type == 'R' ? $denom->denom : 0;

            ### check sales limit ###
            $net_revenue = 0;

            $rebate_type = empty($esn_obj) ? 'B' : 'R';
            $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
            $rebate_amt = $ret_rebate['rebate_amt'];

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn);
            $spiff_amt = $ret_spiff['spiff_amt'];
            
            $limit_amount_to_check = $denom->denom * $rtr_month + $fee + $pm_fee - $rebate_amt - $spiff_amt;

            if ($limit_amount_to_check > 0) {
                if ($sim_type == 'R') {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => 'The SIM or ESN is not allowed for C.Store activation with current denom $' . $denom->denom . ' CAM $' . $limit_amount_to_check
                        ]
                    ]);
                }

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
            $trans->sim = $request->sim;
            $trans->esn = $denom->product_id == 'WFRUPA' ? $request->imei : $request->esn;
            $trans->zip = $request->zip_code;
            $trans->created_by = 'cstore';
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

            ### Call EMIDA API ###
            //$ret = emida2::freeUp_activation($request->sub_carrier, $vendor_denom->act_pid, $vendor_denom->pin_pid,
            $ret = emida2::freeUp_activation($request->sub_carrier, $vendor_denom->act_pid, $vendor_denom->pin_pid,
                $request->afcode, $request->sim , $request->handset_os, $request->imei, $request->esn, 
                $request->zip_code, $trans->id);

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
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn);
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
}