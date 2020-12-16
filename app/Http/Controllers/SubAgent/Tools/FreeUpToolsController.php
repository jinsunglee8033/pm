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

class FreeUpToolsController
{
    public function show(Request $request) {

        $denoms = Denom::where('product_id', 'WFRUPR')->where('status', 'A')->get();

        return view('sub-agent.tools.freeup', [
            'denoms'    => $denoms
         ]);
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
            $changeplan->account_id = $account->id;
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