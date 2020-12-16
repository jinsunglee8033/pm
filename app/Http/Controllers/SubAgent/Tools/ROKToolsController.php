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
use App\Lib\reup;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\ROKESN;
use App\Model\ROKSim;
use App\Model\ROKMapping;
use App\Model\ROKSimSwap;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ROKToolsController
{
    public function show(Request $request) {

        return view('sub-agent.tools.rok-tools', [
         ]);
    }

    public function simswap(Request $request) {

        try {
            $carrier_id = $request->carrier_id;
            $new_sim = $request->new_sim;
            $mdn = $request->mdn;
            $is_recharge = $request->is_recharge;

            if (!in_array($carrier_id, [52, 53, 57])) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Invalid carrier provided'
                ]);
            }

            if (empty($new_sim)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is required'
                ]);
            }

            $pattern = '/^\d{10}$/';
            if (empty($mdn) || !preg_match($pattern, $mdn)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Please enter valid phone # to swap'
                ]);
            }

            $info = reup::get_mdn_info($mdn);

            if (empty($info)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Unknown Error'
                ]);
            }

            if (!empty($info['error_code'])) {
                return response()->json([
                    'code' => '-1',
                    'msg' => 'Failed to get MDN information !!'
                ]);
            }

            if ($carrier_id != $info['carrier_id']) {
                return response()->json([
                    'code' => '-1',
                    'msg' => 'The mdn do not belong the carrier !!'
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

            $fee = 0;
            $pm_fee = 0;
            $collection_amt = 0;
            $net_revenue = 0;

            $vendor_denom = null;
            $denom = $info['denom'];

            if ($is_recharge == 'Y') {
                if (empty($denom)) {
                    return back()->withInput()->withErrors([
                        'code' => '-1',
                        'msg' => 'Cannot recharge the MDN !!'
                    ]);
                }

                $vendor_denom = VendorDenom::where('vendor_code', 'RUP')
                    ->where('product_id', $denom->product_id)
                    ->where('denom_id', $denom->id)
                    ->where('status', 'A')
                    ->first();

                if (empty($vendor_denom)) {
                    return back()->withInput()->withErrors([
                        'code' => '-1',
                        'msg' => '$' . number_format($denom->denom) . ' is not supported by the vendor [RUP]'
                    ]);
                }

                $fee = $vendor_denom->fee;
                $pm_fee = $vendor_denom->pm_fee;

                $collection_amt = $denom->denom;
                $net_revenue = 0;
                if ($collection_amt > 0) {
                    $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true);
                    if (!empty($ret['error_msg'])) {
                        return back()->withInput()->withErrors([
                            'code' => '-1',
                            'msg' => $ret['error_msg']
                        ]);
                    }

                    $net_revenue = $ret['net_revenue'];
                }
            }

            ### SWAP SIM ###
            $ret = reup::simswap($carrier_id, $new_sim, $mdn);

            if (empty($ret)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Unknown Error'
                ]);
            }

            $simswap = new ROKSimSwap();
            $simswap->carrier_id    = $carrier_id;
            $simswap->new_sim       = $new_sim;
            $simswap->mdn           = $mdn;
            $simswap->referenceId   = $ret['referenceId'];
            $simswap->comment       = (empty($ret['error_code']) ? '' : '[' . $ret['error_code'] . '] ') . $ret['error_msg'];
            $simswap->status        = empty($ret['error_code']) ? 'S' : 'F';
            $simswap->save();


            if ($simswap->status == 'S') {

                if ($is_recharge == 'Y') {
                    ### now create order ###
                    $trans = new Transaction;
                    $trans->type = 'S';
                    $trans->account_id = $account->id;
                    $trans->product_id = $denom->product_id;
                    $trans->action = 'RTR';
                    $trans->denom = $denom->denom;
                    $trans->phone = $mdn;
                    $trans->status = 'I';
                    $trans->cdate = Carbon::now();
                    $trans->created_by = $user->user_id;
                    $trans->api = 'Y';
                    $trans->collection_amt = $collection_amt;
                    $trans->rtr_month = 1;
                    $trans->net_revenue = $net_revenue;
                    $trans->fee = $fee;
                    $trans->pm_fee = $pm_fee;
                    $trans->save();


                    $ret = reup::rtr($vendor_denom->rtr_pid, $trans->phone);
                    $vendor_tx_id = empty($ret['tx_id']) ? '' : $ret['tx_id'];


                    if (!empty($ret['error_msg'])) {
                        $trans->status = 'F';
                        $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                        $trans->save();

                        return back()->withInput()->withErrors([
                            'code' => 'ERR',
                            'msg' => $trans->note
                        ]);
                    }

                    if (empty($vendor_tx_id)) {
                        $trans->status = 'F';
                        $trans->note = 'Unable to retrieve vendor Tx.ID';
                        $trans->save();

                        return back()->withInput()->withErrors([
                            'code' => 'ERR',
                            'msg' => $trans->note
                        ]);
                    }

                    ### mark as success ###
                    $trans->status = 'C';
                    $trans->vendor_tx_id = $vendor_tx_id;
                    $trans->mdate = Carbon::now();
                    $trans->modified_by = 'system';
                    $trans->save();

                    ### commission ###
                    if ($collection_amt > 0) {
                        $ret = CommissionProcessor::create($trans->id);
                        if (!empty($ret['error_msg'])) {
                            $msg = ' - trans ID : ' . $trans->id . '<br/>';
                            $msg .= ' - vendor : RUP<br/>';
                            $msg .= ' - product : ' . $denom->product_id . '<br/>';
                            $msg .= ' - denom : ' . $denom->denom . '<br/>';
                            $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - create commission failed', $msg);
                        }
                    }

                    ### refresh balance ###
                    Helper::update_balance();
                }

                return response()->json([
                    'code' => '0',
                    'msg' => 'Sim swap success. New sim ' . $new_sim . ' assigned to the MDN ' . $mdn
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

    public function mdninfo(Request $request) {

        try {
            $mdn = $request->mdn;

            $pattern = '/^\d{10}$/';
            if (empty($mdn) || !preg_match($pattern, $mdn)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Please enter valid phone # to swap'
                ]);
            }


            $ret = reup::get_mdn_info($mdn);

            if (empty($ret)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Unknown Error'
                ]);
            }

            if (empty($ret['error_code'])) {
                return response()->json([
                    'code'  => '0',
                    'data'  => [
                        'Carrier' => $ret['carrier_name'],
                        'Plan' => $ret['plan_cost'],
                        'Last Payment Date' => $ret['last_payment_date'],
                        'Description' => $ret['description']
                    ]
                ]);
            } else {
                return response()->json([
                    'code' => '-1',
                    'msg' => 'Failed to get MDN information !!'
                ]);
            }

        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'msg' => $ex->getMessage()
            ]);
        }

    }


    public function get_plans(Request $request) {

        try {
            $mdn = $request->mdn;

            $pattern = '/^\d{10}$/';
            if (empty($mdn) || !preg_match($pattern, $mdn)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Please enter valid phone # to swap'
                ]);
            }

            $ret = reup::get_mdn_info($mdn);

            if (empty($ret)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Unknown Error'
                ]);
            }

            if (!empty($ret['error_code'])) {
                return response()->json([
                    'code' => '-1',
                    'msg' => 'Failed to get MDN information !!'
                ]);
            }

            $carrier_id = $ret['carrier_id'];

            $retc = reup::get_plans($carrier_id);

            if (empty($retc)) {
                return response()->json([
                    'code' => '-3',
                    'msg' => 'Unknown Error'
                ]);
            }

            if (!empty($retc['error_code'])) {
                return response()->json([
                    'code' => '-3',
                    'msg' => 'Failed to get plans !!'
                ]);
            }

            return response()->json([
                'code' => '0',
                'info' => [
                        'Carrier' => $ret['carrier_name'],
                        'Plan' => $ret['plan_cost'],
                        'Last Payment Date' => $ret['last_payment_date'],
                        'Description' => $ret['description']
                    ],
                'data' => $retc['plans']
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'msg' => $ex->getMessage()
            ]);
        }
    }

    public function portin_status(Request $request) {

        try {
            $mdn = $request->mdn;

            $pattern = '/^\d{10}$/';
            if (empty($mdn) || !preg_match($pattern, $mdn)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Please enter valid phone # to check'
                ]);
            }

            $trans = Transaction::where('action', 'Port-In')->where('phone', $mdn)->orderBy('id', 'desc')->first();

            if (empty($trans)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'No portin request found !!'
                ]);
            }

            // Return Our status record or call API ?

        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'msg' => $ex->getMessage()
            ]);
        }
    }
}