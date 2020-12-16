<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 11/13/17
 * Time: 10:41 AM
 */

namespace App\Http\Controllers\SubAgent\Tools;


use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\gss;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\ATTSimSwap;
use App\Model\State;
use App\Model\StockSim;
use App\Model\Transaction;
use App\Model\VendorDenom;
use App\Model\ChangePlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ATTToolsController
{
    public function show(Request $request) {

        $denoms = Denom::where('product_id', 'WATTA')->where('status', 'A')->get();

        return view('sub-agent.tools.att', [
            'denoms'    => $denoms
         ]);
    }

    public function simswap(Request $request) {

        try {

            if (empty($request->new_sim)) {
                return response()->json([
                    'code'  => '-2',
                    'msg'   => 'SIM is required'
                ]);
            }

            $pattern = '/^\d{10}$/';
            if (empty($request->mdn) || !preg_match($pattern, $request->mdn)) {
                return response()->json([
                    'code'  => '-2',
                    'msg'   => 'Please enter valid phone # to swap'
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withInput()->withErrors([
                    'code'  => '-1',
                    'msg'   => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            $tid = Helper::get_att_tid($account);
            if (empty($tid)) {
                return back()->withInput()->withErrors([
                    'code'  => '-1',
                    'msg'   => 'Your account is not authorized to do AT&T. Please contact your distributor.'
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'code'  => '-2',
                    'msg'   => 'Invalid denomination provided.'
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'code'  => '-2',
                    'msg'   => 'The product is not available.'
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $denom->product_id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom)) {
                return response()->json([
                    'code' => '-2',
                    'msg'   => 'Vendor configuration incomplete.'
                ]);
            }

            $fee = 0;
            $pm_fee = 0;
            $collection_amt = 0;
            $net_revenue = 0;

            // if ($request->is_recharge == 'Y') {
            //     $fee = $vendor_denom->fee;
            //     $pm_fee = $vendor_denom->pm_fee;

            //     $collection_amt = $denom->denom;
            //     $net_revenue = 0;
            //     if ($collection_amt > 0) {
            //         $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true);
            //         if (!empty($ret['error_msg'])) {
            //             return response()->json([
            //                 'code' => '-1',
            //                 'msg' => $ret['error_msg']
            //             ]);
            //         }

            //         $net_revenue = $ret['net_revenue'];
            //     }
            // }

            $simswap = new ATTSimSwap();
            $simswap->account_id    = $account->id;
            $simswap->new_sim       = $request->new_sim;
            $simswap->mdn           = $request->mdn;
            $simswap->status        = 'N';
            $simswap->att_tid       = $tid;
            $simswap->created_by    = $user->user_id;
            $simswap->cdate         = Carbon::now();
            $simswap->save();

            ### SWAP SIM ###
            // SwapEquipment($trans_id, $pid, $phone, $sim, $imei, $tid)
            $ret = gss::SwapEquipment($simswap->id, $vendor_denom->act_pid, $request->mdn, $request->new_sim, '', $tid);

            if (empty($ret)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Unknown Error'
                ]);
            }

            $simswap->comment       = (empty($ret['error_code']) ? '' : '[' . $ret['error_code'] . '] ') . $ret['error_msg'];
            $simswap->status        = empty($ret['error_code']) ? 'S' : 'F';
            $simswap->save();

            if (!empty($ret['error_code'])) {
                return response()->json([
                    'code' => '-2',
                    'msg' => $simswap->comment
                ]);
            }

            if ($simswap->status == 'S') {

                // if ($request->is_recharge == 'Y') {

                //     $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                //         ->where('product_id', 'WATTA')
                //         ->where('denom', $denom->denom)
                //         ->where('status', 'A')
                //         ->first();
                //     if (empty($vendor_denom)) {
                //         return response()->json([
                //             'code' => '-2',
                //             'msg'   => 'Vendor configuration incomplete.'
                //         ]);
                //     }

                //     ### now create order ###
                //     $trans = new Transaction;
                //     $trans->type = 'S';
                //     $trans->account_id = $account->id;
                //     $trans->product_id = $denom->product_id;
                //     $trans->action = 'RTR';
                //     $trans->denom = $denom->denom;
                //     $trans->phone = $request->mdn;
                //     $trans->status = 'I';
                //     $trans->cdate = Carbon::now();
                //     $trans->created_by = $user->user_id;
                //     $trans->api = 'Y';
                //     $trans->collection_amt = $collection_amt;
                //     $trans->rtr_month = 1;
                //     $trans->net_revenue = $net_revenue;
                //     $trans->fee = $fee;
                //     $trans->pm_fee = $pm_fee;
                //     $trans->save();


                //     $ret = gss::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $trans->denom);
                //     $vendor_tx_id = empty($ret['tx_id']) ? '' : $ret['tx_id'];


                //     if (!empty($ret['error_msg'])) {
                //         $trans->status = 'F';
                //         $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                //         $trans->save();

                //         return back()->withInput()->withErrors([
                //             'code' => 'ERR',
                //             'msg' => $trans->note
                //         ]);
                //     }

                //     if (empty($vendor_tx_id)) {
                //         $trans->status = 'F';
                //         $trans->note = 'Unable to retrieve vendor Tx.ID';
                //         $trans->save();

                //         return response()->json([
                //             'code' => 'ERR',
                //             'msg' => $trans->note
                //         ]);
                //     }

                //     ### mark as success ###
                //     $trans->status = 'C';
                //     $trans->vendor_tx_id = $vendor_tx_id;
                //     $trans->mdate = Carbon::now();
                //     $trans->modified_by = 'system';
                //     $trans->save();

                //     ### commission ###
                //     $ret = \App\Lib\CommissionProcessor::create($trans->id);
                //     if (!empty($ret['error_msg'])) {
                //         $msg = ' - trans ID : ' . $trans->id . '<br/>';
                //         $msg .= ' - vendor : RUP<br/>';
                //         $msg .= ' - product : ' . $denom->product_id . '<br/>';
                //         $msg .= ' - denom : ' . $denom->denom . '<br/>';
                //         $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                //         Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - create commission failed', $msg);
                //     }


                //     $ret = \App\Lib\RTRProcessor::applyRTR(
                //         1,
                //         '',
                //         $trans->id,
                //         'Refill',
                //         $trans->phone,
                //         $vendor_denom->vendor_code,
                //         $vendor_denom->rtr_pid,
                //         $vendor_denom->denom,
                //         $user->user_id,
                //         false,
                //         null,
                //         1,
                //         $vendor_denom->fee,
                //         $trans->rtr_month
                //     );

                //     ### refresh balance ###
                //     Helper::update_balance();
                // }

                return response()->json([
                    'code' => '0',
                    'msg' => 'Sim swap success. New sim ' . $request->new_sim . ' assigned to the MDN ' . $request->mdn
                ]);
            } else {
                return response()->json([
                    'code' => '-1',
                    'msg' => 'Sim swap failed !!'
                ]);
            }

        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'msg' => $ex->getMessage()
            ]);
        }

    }

    public function changeplan(Request $request) {

        try {

            $pattern = '/^\d{10}$/';
            if (empty($request->mdn) || !preg_match($pattern, $request->mdn)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Please enter valid phone # to swap'
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withInput()->withErrors([
                    'code' => '-1',
                    'msg' => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            $tid = Helper::get_att_tid($account);
            if (empty($tid)) {
                return back()->withInput()->withErrors([
                    'code' => '-1',
                    'msg' => 'Your account is not authorized to do AT&T. Please contact your distributor'
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'code' => '-2',
                    'msg'   => 'Invalid denomination provided.'
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg'   => 'The product is not available.'
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $denom->product_id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom)) {
                return response()->json([
                    'code' => '-2',
                    'msg'   => 'Vendor configuration incomplete.'
                ]);
            }

            $changeplan = new ChangePlan();
            $changeplan->account_id = $account->id;
            $changeplan->carrier    = $product->carrier;
            $changeplan->mdn        = $request->mdn;
            $changeplan->plan       = $denom->denom;
            $changeplan->status     = 'N';
            $changeplan->created_by = $user->user_id;
            $changeplan->cdate      = Carbon::now();
            $changeplan->save();

            ### SWAP SIM ###
            // UpgradePlan($trans_id, $pid, $phone, $tid)
            $ret = gss::UpgradePlan($changeplan->id, $vendor_denom->act_pid, $request->mdn, $tid);

            if (empty($ret)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Unknown Error'
                ]);
            }

            $changeplan->comment       = (empty($ret['error_code']) ? '' : '[' . $ret['error_code'] . '] ') . $ret['error_msg'];
            $changeplan->status        = empty($ret['error_code']) ? 'S' : 'F';
            $changeplan->save();

            if (!empty($ret['error_code'])) {
                return response()->json([
                    'code' => '-2',
                    'msg' => $changeplan->comment
                ]);
            }

            if ($changeplan->status == 'S') {
                return response()->json([
                    'code' => '0',
                    'msg' => 'Change plan success.'
                ]);
            } else {
                return response()->json([
                    'code' => '-1',
                    'msg' => 'Change plan failed !!'
                ]);
            }

        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'msg' => $ex->getMessage()
            ]);
        }

    }

    public function eprovision(Request $request) {

        try {

            $pattern = '/^\d{20}$/';
            if (empty($request->sim) || !preg_match($pattern, $request->sim)) {
                return response()->json([
                  'code' => '-2',
                  'msg' => 'Please enter valid sim #'
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withInput()->withErrors([
                  'code' => '-1',
                  'msg' => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            $sim_obj = StockSim::where('sim_serial', $request->sim)
                ->where('status', 'A')
                ->where('c_store_id', $user->account_id)
                ->first();
            if (empty($sim_obj)) {
                return response()->json([
                  'code' => '-2',
                  'msg' => 'Please enter valid sim #. The sim is not available.'
                ]);
            }

            $product = Product::find($sim_obj->product);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                  'code' => '-2',
                  'msg'   => 'The product is not available.'
                ]);
            }

            if (empty($sim_obj->amount)) {
                return response()->json([
                  'code' => '0',
                  'denom_id' => ''
                ]);
            } else {
                $denom = Denom::where('product_id', $sim_obj->product)->where('denom', $sim_obj->amount)->first();
                if (empty($denom)) {
                    return response()->json([
                      'code' => '0',
                      'denom_id' => ''
                    ]);
                }

                return response()->json([
                    'code' => '0',
                    'denom_id' => $denom->id
                ]);
            }

        } catch (\Exception $ex) {
            return response()->json([
              'code' => '-9',
              'msg' => $ex->getMessage()
            ]);
        }

    }

    public function eprovision_update(Request $request) {

        try {

            $pattern = '/^\d{20}$/';
            if (empty($request->sim) || !preg_match($pattern, $request->sim)) {
                return response()->json([
                  'code' => '-2',
                  'msg' => 'Please enter valid sim #'
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withInput()->withErrors([
                  'code' => '-1',
                  'msg' => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            if (!empty($request->denom_id)) {
                $denom = Denom::find($request->denom_id);
                if (empty($denom)) {
                    return response()->json([
                      'code' => '-2',
                      'msg'   => 'Invalid denomination provided.'
                    ]);
                }
            }

            $sim_obj = StockSim::where('sim_serial', $request->sim)
              ->where('status', 'A')
              ->where('c_store_id', $user->account_id)
              ->first();
            if (empty($sim_obj)) {
                return response()->json([
                  'code' => '-2',
                  'msg' => 'Please enter valid sim #. The sim is not available.'
                ]);
            }

            if ($sim_obj->type == 'P') {
                return response()->json([
                  'code' => '-2',
                  'msg' => 'You can not change the plan.'
                ]);
            }

            $product = Product::find($sim_obj->product);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                  'code' => '-2',
                  'msg'   => 'The product is not available.'
                ]);
            }

            $sim_obj->amount = empty($request->denom_id) ? null : $denom->denom;
            $sim_obj->save();

            $changeplan = new ChangePlan();
            $changeplan->carrier    = $product->carrier;
            $changeplan->sim        = $request->sim;
            $changeplan->plan       = empty($request->denom_id) ? null : $denom->denom;
            $changeplan->status     = 'A';
            $changeplan->created_by = $user->user_id;
            $changeplan->cdate      = Carbon::now();
            $changeplan->save();

            return response()->json([
              'code' => '0',
              'msg' => 'Plan assigned successfully.'
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'code' => '-9',
              'msg' => $ex->getMessage()
            ]);
        }

    }
}