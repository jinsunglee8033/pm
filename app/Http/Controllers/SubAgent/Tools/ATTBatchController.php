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
use App\Model\AccountAuthority;
use App\Model\ATTBatch;
use App\Model\ATTBatchMDNAvailability;
use App\Model\ATTSimSwap;
use App\Model\Denom;
use App\Model\Product;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use App\Model\ChangePlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class ATTBatchController
{
    public function show(Request $request) {
        if (!Helper::has_att_batch_authority()) {
            return redirect('/sub-agent');
        }

        $user = Auth::user();
        $account = Account::find($user->account_id);

        $sdate = Carbon::today();
        $edate = Carbon::today()->addDays(1)->addSeconds(-1);

        $s_expire_date = Carbon::today();
        $e_expire_date = Carbon::today()->addDays(7)->addSeconds(-1);

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        if (!empty($request->s_expire_date)) {
            $s_expire_date = Carbon::createFromFormat('Y-m-d H:i:s', $request->s_expire_date . ' 00:00:00');
        }

        if (!empty($request->e_expire_date)) {
            $e_expire_date = Carbon::createFromFormat('Y-m-d H:i:s', $request->e_expire_date . ' 23:59:59');
        }

        $query = ATTBatch::where('account_id', $account->id)
            ->where('process_date', '>=', $sdate)->where('process_date', '<=', $edate)
            ->whereRaw('((expire_date >= \'' . $s_expire_date . '\' and expire_date <= \'' . $e_expire_date . '\') or expire_date is null)');

        if (!empty($request->phone)) {
            $query->where('mdn', $request->phone);
        }

        if (!empty($request->sim)) {
            $query->where('sim', $request->sim);
        }

        if (!empty($request->notes)) {
            $query->whereRaw("lower(notes) like ?", '%' . strtolower($request->notes) . '%');
        }

        if (!empty($request->status)) {
            $query->where('status', $request->status);
        }

        $batchs = $query->orderBy('process_date')->paginate(15);
        $plans = Denom::where('product_id', 'WATTA')->where('status', 'A')->orderBy('denom')->get();
        $authority = Auth::user()->authority;

        return view('sub-agent.tools.att-batch', [
            'batchs'    => $batchs,
            'plans'     => $plans,
            'authority' => $authority,
            'phone'     => $request->phone,
            'sim'       => $request->sim,
            'sdate'     => $sdate->format('Y-m-d'),
            'edate'     => $edate->format('Y-m-d'),
            'status'    => $request->status,
            's_expire_date' => $s_expire_date,
            'e_expire_date' => $e_expire_date,
            'notes'       => $request->notes,
         ]);
    }

    public function add(Request $request) {
        try {

          if (!Helper::has_att_batch_authority()) {
              return response()->json([
                  'code'  => '-1',
                  'msg'   => 'You do not have authority !!'
              ]);
          }

          $v = Validator::make($request->all(), [
              'mdn'     => 'required|numeric|regex:/^\d{10}$/',
              'plan'    => 'required',
              'sim'     => 'required_if:for_sim_swap,Y|regex:/^\d{20}$/',
              'process_date' => 'required',
          ], [
              'mdn.regex' => 'The MDN format is invalid or (less than 10 digits)',
              'sim.regex' => 'The SIM format is invalid or (less than 20 digits)'
          ]);

          if ($v->fails()) {
              $msg = '';
              foreach ($v->messages()->toArray() as $k => $v) {
                $msg .= (empty($msg) ? '' : "|") . $v[0];
              }

              return response()->json([
                'msg' => $msg
              ]);
          }

          if (empty($request->process_date)) {
              return response()->json([
                  'code'  => 'process_date',
                  'msg'   => 'Process date is required'
              ]);
          }

          $user = Auth::user();
          $authority = Auth::user()->authority;

          $is_rechard_scheduled = false;
          $mdn_availability = ATTBatchMDNAvailability::where('account_id', $user->account_id)
            ->where('mdn', $request->mdn)
            ->where('sdate', '<=', $request->process_date)
            ->where('edate', '>=', $request->process_date)
            ->first();
          if (!empty($mdn_availability)) {
              $is_rechard_scheduled = true;
          }


          if ($authority->auth_batch_rtr == 'Y') {
              if (empty($request->for_rtr)) {
                  return response()->json([
                    'code'  => 'for_rtr',
                    'msg'   => 'For rtr is required'
                  ]);
              }

              $check_max = ATTBatch::check_max_rtr($authority, $user->account_id, $request->process_date);
              if ($check_max['code'] != '0') {
                  return response()->json([
                    'code'  => 'for_rtr',
                    'msg'   => $check_max['msg']
                  ]);
              }

              if ($request->for_rtr == 'Y') {
                  $is_rechard_scheduled = true;
              }
          }

          if ($authority->auth_batch_sim_swap == 'Y') {
              if (empty($request->for_sim_swap)) {
                  return response()->json([
                    'code'  => 'for_sim_swap',
                    'msg'   => 'For sim swap is required'
                  ]);
              }

              if ($request->for_sim_swap == 'Y') {

                  if (empty($request->sim)) {
                      return response()->json([
                        'code' => 'sim',
                        'msg' => 'Sim is required'
                      ]);
                  }

                  if (!$is_rechard_scheduled) {
                      return response()->json([
                        'code' => 'rtr',
                        'msg' => 'You need schedule recharge first. SIM'
                      ]);
                  }

                  $check_max = ATTBatch::check_max_sim_swap($authority, $user->account_id, $request->process_date);
                  if ($check_max['code'] != '0') {
                      return response()->json([
                        'code' => 'for_sim_swap',
                        'msg' => $check_max['msg']
                      ]);
                  }
              }
          }

          if ($authority->auth_batch_plan_change == 'Y') {
              if (empty($request->for_plan_change)) {
                  return response()->json([
                    'code'  => 'for_plan_change',
                    'msg'   => 'For plan change is required'
                  ]);
              }

              if ($request->for_plan_change == 'Y') {

                  if( !$is_rechard_scheduled) {
                      return response()->json([
                        'code' => 'rtr',
                        'msg' => 'You need schedule recharge first. CHA'
                      ]);
                  }

                  $check_max = ATTBatch::check_max_plan_change($authority, $user->account_id, $request->process_date);
                  if ($check_max['code'] != '0') {
                      return response()->json([
                        'code' => 'for_plan_change',
                        'msg' => $check_max['msg']
                      ]);
                  }
              }
          }

          $batch = new ATTBatch();
          $batch->account_id = $user->account_id;
          $batch->mdn = $request->mdn;
          $batch->sim = $request->sim;
          $batch->plan = $request->plan;
          if ($authority->auth_batch_rtr == 'Y') {
              $batch->for_rtr = $request->for_rtr;
          }
          if ($authority->auth_batch_sim_swap == 'Y') {
              $batch->for_sim_swap = $request->for_sim_swap;
          }
          if ($authority->auth_batch_plan_change == 'Y') {
              $batch->for_plan_change = $request->for_plan_change;
          }
          $batch->process_date = $request->process_date;
          $batch->expire_date = $request->expire_date;
          $batch->notes = $request->notes;
          $batch->status = 'N';
          $batch->cdate = Carbon::now();
          $batch->save();

          if ($batch->for_rtr == 'Y') {
              $asdate = Carbon::createFromFormat('Y-m-d', $request->process_date)->subDays(44);
              $aedate = Carbon::createFromFormat('Y-m-d', $request->process_date)->addDays(29);

              $mdn_availability = new ATTBatchMDNAvailability();
              $mdn_availability->account_id = $user->account_id;
              $mdn_availability->mdn    = $request->mdn;
              $mdn_availability->sdate  = $asdate;
              $mdn_availability->edate  = $aedate;
              $mdn_availability->save();
          }

          return response()->json([
              'code'  => '0',
              'msg'   => 'ATT Batch saved successfully !!'
          ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'msg' => $ex->getMessage()
            ]);
        }

    }

    public function delete(Request $request) {
        $batch = ATTBatch::find($request->id);

        if (!empty($batch)) {
            if ($batch->for_rtr == 'Y') {
                return response()->json([
                  'code'  => '-1',
                  'msg'   => 'ATT Batch RTR is not allowed to delete !!'
                ]);
            }
            $batch->status = 'X';
            $batch->update();
        }

        return response()->json([
            'code'  => '0',
            'msg'   => 'ATT Batch deleted successfully !!'
        ]);
    }
}