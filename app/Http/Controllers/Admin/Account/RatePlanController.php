<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 8/29/17
 * Time: 10:07 AM
 */

namespace App\Http\Controllers\Admin\Account;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\Permission;
use App\Model\RateDetail;
use App\Model\RatePlan;
use App\Model\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RatePlanController extends Controller
{

    public function remove(Request $request) {

        if (!Permission::can($request->path(), 'modify')) {
            return response()->json([
                'msg' => 'You are not authorized to modify any information'
            ]);
        }

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'rate_plan_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                DB::rollback();
                return response()->json([
                    'msg' => $msg
                ]);
            }

            $rate_plan = RatePlan::where('id', $request->rate_plan_id)
                ->where('owner_id', $request->account_id)
                ->first();

            if (empty($rate_plan)) {
                DB::rollback();
                return response()->json([
                    'msg' => 'Invalid rate plan ID provided'
                ]);
            }

            if ($rate_plan->assigned_qty != 0) {
                DB::rollback();
                return response()->json([
                    'msg' => 'There is account that are assigned to this rate plan. Unable to remove!'
                ]);
            }

            $rate_plan->delete();

            $ret = DB::statement("
                delete from rate_detail
                where rate_plan_id = :rate_plan_id
            ", [
                'rate_plan_id' => $rate_plan->id
            ]);

            if ($ret < 1) {
                DB::rollback();
                return response()->json([
                    'msg' => 'Failed to remove rates detail'
                ]);
            }

            DB::commit();

            return [
                'msg' => ''
            ];

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function copy(Request $request) {

        if (!Permission::can($request->path(), 'modify')) {
            return response()->json([
                'msg' => 'You are not authorized to modify any information'
            ]);
        }

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'rate_plan_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                DB::rollback();
                return response()->json([
                    'msg' => $msg
                ]);
            }

            $rate_plan = RatePlan::where('id', $request->rate_plan_id)
                ->where('owner_id', $request->account_id)
                ->first();

            if (empty($rate_plan)) {
                DB::rollback();
                return response()->json([
                    'msg' => 'Invalid rate plan ID provided'
                ]);
            }

            $new_plan = new RatePlan;
            $new_plan->owner_id = $request->account_id;
            $new_plan->name = $rate_plan->name .' - Copy';
            $new_plan->type = $rate_plan->type;
            $new_plan->status = 'A';
            $new_plan->created_by = Auth::user()->user_id;
            $new_plan->cdate = Carbon::now();
            $new_plan->save();

            $ret = DB::statement("
                insert into rate_detail (
                    rate_plan_id, denom_id, rates, created_by, cdate
                )
                select 
                    :new_rate_plan_id,
                    denom_id,
                    rates,
                    :created_by,
                    current_timestamp
                from rate_detail
                where rate_plan_id = :rate_plan_id
            ", [
                'new_rate_plan_id' => $new_plan->id,
                'created_by' => Auth::user()->user_id,
                'rate_plan_id' => $rate_plan->id
            ]);

            if ($ret < 1) {
                DB::rollback();
                return response()->json([
                    'msg' => 'Failed to copy rates detail'
                ]);
            }

            DB::commit();

            return [
                'msg' => ''
            ];

        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update(Request $request) {
        if (!Permission::can($request->path(), 'modify')) {
            return response()->json([
                'msg' => 'You are not authorized to modify any information'
            ]);
        }

        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'owner_id' => 'required',
                'type' => 'required',
                'name' => 'required',
                'status' => 'required'
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

            $cnt = RatePlan::where('type', $request->type)
                ->where('name', $request->name)
                ->where('owner_id', $request->owner_id)
                ->where('id', '!=', $request->id)
                ->count();

            if ($cnt > 0) {
                return response()->json([
                    'msg' => 'Duplicated rate plan with same name and type found'
                ]);
            }

            $rp = RatePlan::find($request->id);
            if (empty($rp)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            if ($rp->type != $request->type) {
                $cnt = Account::where('rate_plan_id', $rp->id)
                    ->count();
                if ($cnt > 0) {
                    return response()->json([
                        'msg' => 'Rate plan type cannot be changed when there are accounts bound to it.'
                    ]);
                }
            }

            $rp->owner_id = $request->owner_id;
            $rp->type = $request->type;
            $rp->name = $request->name;
            $rp->status = $request->status;
            $rp->modified_by = Auth::user()->user_id;
            $rp->mdate = Carbon::now();
            $rp->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function add(Request $request) {

        if (!Permission::can($request->path(), 'modify')) {
            return response()->json([
                'msg' => 'You are not authorized to modify any information'
            ]);
        }

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'owner_id' => 'required',
                'type' => 'required',
                'name' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                DB::rollback();

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $cnt = RatePlan::where('type', $request->type)
                ->where('name', $request->name)
                ->where('owner_id', $request->owner_id)
                ->count();

            if ($cnt > 0) {
                DB::rollback();

                return response()->json([
                    'msg' => 'Duplicated rate plan with same name and type found'
                ]);
            }

            if (!empty($request->copy_from)) {
                ### validate the plan ID ####

                $plan_to_copy = RatePlan::find($request->copy_from);
                if (empty($plan_to_copy)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Invalid rate plan ID provided'
                    ]);
                }

                if ($plan_to_copy->type != $request->type) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Plan type mismatch with copy from plan'
                    ]);
                }

                # 1. it should be owned by logged in user or child accounts
                $login_account = Account::find(Auth::user()->account_id);
                if (empty($login_account)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Session expired. Please login agaon.'
                    ]);
                }

                $plan_to_copy_owner = Account::where('id', $plan_to_copy->owner_id)
                    ->where('path', 'like', $login_account->path . '%')
                    ->first();
                if (empty($plan_to_copy_owner)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'The rate plan ID does not belong to your account or children'
                    ]);
                }

                # 2. any of its rates should not be higher than target account's assigned plan rates.
                $new_owner = Account::find($request->owner_id);
                if (empty($new_owner)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Invalid owner ID provided'
                    ]);
                }

                $ret = DB::select("
                    select 
                        d.name as product_name,
                        d.id as product_id,
                        a.action, 
                        a.rates as owner_rates, 
                        b.rates as copy_from_rates
                    from rate_detail a 
                        inner join rate_detail b on a.denom_id = b.denom_id 
                            and a.action = b.action
                        inner join denomination c on a.denom_id = c.id
                        inner join product d on c.product_id = d.id
                    where a.rates < b.rates 
                    and a.rate_plan_id = :new_owner_plan_id
                    and b.rate_plan_id = :plan_to_copy_id
                ", [
                    'new_owner_plan_id' => $new_owner->rate_plan_id,
                    'plan_to_copy_id' => $plan_to_copy->id
                ]);

                if (count($ret) > 0) {
                    DB::rollback();

                    $msg = 'Copy from plan has higher rates than owner rates for below products:<br/>';
                    foreach ($ret as $o) {
                        $msg .= ' - ' . $o->product_name . ' ( ' . $o->product_id . ' ) => Owner : ' > $o->onwer_rates . ' < Copy From : ' . $o->copy_from_rates . '<br/>';
                    }
                    return response()->json([
                        'msg' => $msg
                    ]);
                }
            }

            $rp = new RatePlan;
            $rp->owner_id = $request->owner_id;
            $rp->type = $request->type;
            $rp->name = $request->name;
            $rp->status = $request->status;
            $rp->created_by = Auth::user()->user_id;
            $rp->cdate = Carbon::now();
            $rp->save();


            if (!empty($request->copy_from)) {

                $ret = DB::insert("
                    insert into rate_detail (
                        rate_plan_id,
                        denom_id,
                        action,
                        rates,
                        created_by,
                        cdate
                    )
                    select 
                        :new_id as rate_plan_id,
                        denom_id,
                        action,
                        rates,
                        :user_id as created_by, 
                        current_timestamp as cdate
                    from rate_detail
                    where rate_plan_id = :copy_from
                ", [
                    'new_id' => $rp->id,
                    'user_id' => Auth::user()->user_id,
                    'copy_from' => $request->copy_from
                ]);

                if ($ret < 1) {
                    DB::rollback();

                    return response()->json([
                        'msg' => 'Failed to copy rates from : ' . $request->copy_from
                    ]);
                }

            }

            DB::commit();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            DB::rollback();

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ]);
        }
    }

    public function loadOwnedPlans(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'owner_id' => 'required'
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

            $owned_plans = RatePlan::where('owner_id', $request->owner_id)
                ->get();

            if (count($owned_plans) > 0) {
                foreach ($owned_plans as $o) {
                    $o->last_updated = $o->last_updated;
                    $o->type_img = Helper::get_hierarchy_img($o->type);
                }
            }

            return response()->json([
                'msg' => '',
                'owned_plans' => $owned_plans
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadPlan(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
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

            $rp = RatePlan::find($request->id);
            if (empty($rp)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $rp->last_updated = $rp->last_updated;

            return response()->json([
                'msg' => '',
                'rate_plan' => $rp
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}