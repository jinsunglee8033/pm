<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/12/17
 * Time: 2:22 PM
 */

namespace App\Http\Controllers\Admin\Account;


use App\Http\Controllers\Controller;
use App\Lib\Permission;
use App\Model\Account;
use App\Model\RateDetail;
use App\Model\RatePlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class RateDetailController extends Controller
{

    public function update(Request $request) {

        if (!Permission::can($request->path(), 'modify')) {
            return response()->json([
                'msg' => 'You are not authorized to modify any information'
            ]);
        }

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'rate_plan_id' => 'required',
                'denom_id' => 'required',
                'action' => 'required',
                'rates' => 'nullable|numeric|max:100'
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

            $rate_plan = RatePlan::find($request->rate_plan_id);
            if (empty($rate_plan)) {
                DB::rollback();
                return response()->json([
                    'msg' => 'Invalid rate plan ID provided'
                ]);
            }

            if (is_null($rate_plan->owner_id)) {
                $parent_rates = 100;
            } else {
                $parent = Account::find($rate_plan->owner_id);
                if (empty($parent)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Something is wrong. Invalid owner ID found.'
                    ]);
                }

                $parent_rate_detail = RateDetail::where('rate_plan_id', $parent->rate_plan_id)
                    ->where('denom_id', $request->denom_id)
                    ->first();
                if (empty($parent_rate_detail)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Something is wrong. Parent rate is empty'
                    ]);
                }

                $parent_rates = $parent_rate_detail->rates;
            }

            if ($request->rates != '') {
                ### check parent rates ###
                if ($parent_rates < $request->rates) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'New rates exceed parent rates'
                    ]);
                }

                ### check children max rates - owned by me or my children ###
                $ret = DB::select("
                    select d.rate_plan_id
                    from accounts a 
                        inner join accounts b on b.path like concat(a.path, '%') 
                        inner join rate_plan c on b.id = c.owner_id 
                        inner join rate_detail d on c.id = d.rate_plan_id 
                            and d.denom_id = :denom_id
                    where a.rate_plan_id = :rate_plan_id
                    and d.rates > :rates
                    limit 1  
                ", [
                    'rate_plan_id' => $rate_plan->id,
                    'denom_id' => $request->denom_id,
                    'rates' => $request->rates
                ]);

                $children_max_rates_plan_id = null;
                if (count($ret) > 0) {
                    $children_max_rates_plan_id = $ret[0]->rate_plan_id;
                }

                if (!is_null($children_max_rates_plan_id)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Child rate plan ID [ ' . $children_max_rates_plan_id . ' ] has higher rates for this denomination. Please check!'
                    ]);
                }

                $rate_detail = RateDetail::where('rate_plan_id', $request->rate_plan_id)
                    ->where('denom_id', $request->denom_id)
                    ->where('action', $request->action)
                    ->first();

                if (empty($rate_detail)) {
                    $rate_detail = new RateDetail;
                    $rate_detail->cdate = Carbon::now();
                    $rate_detail->created_by = Auth::user()->user_id;
                } else {
                    $rate_detail->mdate = Carbon::now();
                    $rate_detail->modified_by = Auth::user()->user_id;
                }

                $rate_detail->rate_plan_id = $request->rate_plan_id;
                $rate_detail->denom_id = $request->denom_id;
                $rate_detail->action = $request->action;
                $rate_detail->rates = $request->rates;
                $rate_detail->save();

            } else {
                $rate_detail = RateDetail::where('rate_plan_id', $request->rate_plan_id)
                    ->where('denom_id', $request->denom_id)
                    ->where('action', $request->action)
                    ->first();

                if (empty($rate_detail)) {
                    DB::rollback();
                    return response()->json([
                        'msg' => 'Unable to find rate to remove'
                    ]);
                }

                ### remove all children ###
                $ret = DB::statement("
                    delete e
                    from rate_detail a 
                        inner join accounts b on a.rate_plan_id = b.rate_plan_id 
                        inner join accounts c on c.path like concat(b.path, '%')
                        inner join rate_plan d on c.id = d.owner_id 
                        inner join rate_detail e on d.id = e.rate_plan_id
                            and a.denom_id = e.denom_id
                            and e.action = :action
                    where a.rate_plan_id = :rate_plan_id
                    and d.id != a.rate_plan_id
                    and a.denom_id = :denom_id        
                ", [
                    'rate_plan_id' => $rate_detail->rate_plan_id,
                    'denom_id' => $rate_detail->denom_id,
                    'action' => $rate_detail->action
                ]);

                if ($ret < 1) {
                    response()->json([
                        'msg' => 'Failed to remove children rates'
                    ]);
                }

                $rate_detail->delete();

            }

            DB::commit();
            return response()->json([
                'msg' => ''
            ]);


        } catch (\Exception $ex) {
            DB::rollback();
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function load(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'rate_plan_id' => 'required',
                'action' => 'required',
                'show_type' => 'required|in:L,M,O'
            ], [
                'rate_plan_id.required' => 'Please assign rate plan first!'
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

            $rates = [];

            $rate_plan = RatePlan::find($request->rate_plan_id);
            if (empty($rate_plan)) {
                return response()->json([
                    'msg' => 'Invalid rate plan ID provided'
                ]);
            }

            $show_type = $request->show_type;
            $login_account = Account::find(Auth::user()->account_id);
            if (empty($login_account)) {
                return response()->json([
                    'msg' => 'Session expired!'
                ]);
            }

            if ($rate_plan->type == 'L' && $show_type == 'M') {
                $show_type = 'L';
            } else if ($show_type == 'M' && $rate_plan->id != $login_account->rate_plan_id) {
                $show_type = 'O';
                $parent = Account::find($rate_plan->owner_id);
                if (empty($parent)) {
                    return response()->json([
                        'msg' => 'Unable to find parent A'
                    ]);
                }
            } else if ($show_type != 'L') {
                $parent = Account::find($rate_plan->owner_id);
                if (empty($parent)) {
                    return response()->json([
                        'msg' => 'Unable to find parent B'
                    ]);
                }

            }

            $query = "";

            switch ($show_type) {
                case 'L':   # bind denomination left and rates right

                    if (!empty($request->vendor)) {
                        $query .= " and e.code = '$request->vendor' ";
                    }
                    if (!empty($request->product_id)) {
                        $query .= " and b.id like '%". strtoupper($request->product_id) ."%' ";
                    }
                    if (!empty($request->product_name)) {
                        $query .= " and upper(b.name) like '%". strtoupper($request->product_name) ."%' ";
                    }

                    $query_vd = '';
                    if ($request->action == 'RTR') {
                        $query_vd .= " and ifnull(d.rtr_pid, '') != '' ";
                    } else if ($request->action == 'PIN') {
                        $query_vd .= " and ifnull(d.pin_pid, '') != '' ";
                    }

                    $rates = DB::select("
                        select
                            c.id,
                            :rate_plan_id_show as rate_plan_id,
                            a.id as denom_id,
                            a.product_id, 
                            b.name as product_name, 
                            '{$request->action}' action,
                            a.denom,
                            e.name as vendor,
                            ifnull(d.cost, 0) as cost,
                            100 as parent_rates,
                            ifnull(c.rates, '') as rates, 
                            case when c.id is null then ''
                                 when c.mdate is null then concat(c.cdate, ' (', c.created_by, ')')
                                 else concat(c.mdate, ' (', c.modified_by, ')')
                            end last_updated
                        from denomination a
                            inner join product b on a.product_id = b.id
                            inner join vendor_denom d on a.product_id = d.product_id
                                and a.denom = d.denom 
                                and b.vendor_code = d.vendor_code
                                {$query_vd}
                            left join rate_detail c on a.id = c.denom_id
                                and c.rate_plan_id = :rate_plan_id
                                and c.action = :action
                            
                            left join vendor e on b.vendor_code = e.code 
                        where b.status = 'A'
                        and a.status = 'A'
                        {$query}
                        order by b.carrier, a.product_id, a.denom
                    ", [
                        'rate_plan_id_show' => $request->rate_plan_id,
                        'rate_plan_id' => $request->rate_plan_id,
                        'action' => $request->action
                    ]);
                    break;
                case 'M':   # bind parent rates left and rates right

                    if (!empty($request->vendor)) {
                        $query .= " and f.code = '$request->vendor' ";
                    }
                    if (!empty($request->product_id)) {
                        $query .= " and c.id like '%". strtoupper($request->product_id) ."%' ";
                    }
                    if (!empty($request->product_name)) {
                        $query .= " and upper(c.name) like '%". strtoupper($request->product_name) ."%' ";
                    }

                    $query_vd = '';
                    if ($request->action == 'RTR') {
                        $query_vd .= " and ifnull(e.rtr_pid, '') != '' ";
                    } else if ($request->action == 'PIN') {
                        $query_vd .= " and ifnull(e.pin_pid, '') != '' ";
                    }

                    if (empty($rate_plan)) {
                        $rates = [];
                    } else {
                        $rates = DB::select("
                            select 
                                d.id,
                                :rate_plan_id_show as rate_plan_id,
                                b.id as denom_id,
                                b.product_id,
                                c.name as product_name,
                                '{$request->action}' action,
                                b.denom,
                                f.name as vendor,
                                ifnull(e.cost, 0) as cost,
                                a.rates as parent_rates,
                                ifnull(d.rates, '') as rates,
                                case when d.id is null then ''
                                    when d.mdate is null then concat(d.cdate, ' (', d.created_by, ')')
                                    else concat(d.mdate, ' (', d.modified_by, ')')
                                end last_updated             
                            from rate_detail a
                                inner join denomination b on a.denom_id = b.id
                                inner join product c on b.product_id = c.id 
                                inner join rate_detail d on d.rate_plan_id = :rate_plan_id
                                    and a.denom_id = d.denom_id
                                    and d.action = :action
                                inner join vendor_denom e on b.product_id = e.product_id
                                    and b.denom = e.denom
                                    and c.vendor_code = e.vendor_code
                                    {$query_vd}
                                left join vendor f on c.vendor_code = f.code
                            where a.rate_plan_id = :parent_plan_id      
                            and b.status = 'A'
                            and c.status = 'A'
                            and a.action = :action_r
                            {$query}
                            order by c.carrier, b.product_id, b.denom        
                        ", [
                            'rate_plan_id_show' => $request->rate_plan_id,
                            'parent_plan_id' => $parent->rate_plan_id,
                            'rate_plan_id' => $request->rate_plan_id,
                            'action' => $request->action,
                            'action_r' => $request->action
                        ]);
                    }
                    break;
                case 'O':   # bind parent rates left and rates right

                    if (!empty($request->vendor)) {
                        $query .= " and f.code = '$request->vendor' ";
                    }
                    if (!empty($request->product_id)) {
                        $query .= " and c.id like '%". strtoupper($request->product_id) ."%' ";
                    }
                    if (!empty($request->product_name)) {
                        $query .= " and upper(c.name) like '%". strtoupper($request->product_name) ."%' ";
                    }

                    $query_vd = '';
                    if ($request->action == 'RTR') {
                        $query_vd .= " and ifnull(e.rtr_pid, '') != '' ";
                    } else if ($request->action == 'PIN') {
                        $query_vd .= " and ifnull(e.pin_pid, '') != '' ";
                    }

                    if (empty($rate_plan)) {
                        $rates = [];
                    } else {
                        $rates = DB::select("
                            select 
                                d.id,
                                :rate_plan_id_show as rate_plan_id,
                                b.id as denom_id,
                                b.product_id,
                                c.name as product_name,
                                '{$request->action}' action,
                                b.denom,
                                f.name as vendor,
                                ifnull(e.cost, 0) as cost,
                                a.rates as parent_rates,
                                ifnull(d.rates, '') as rates,
                                case when d.id is null then ''
                                    when d.mdate is null then concat(d.cdate, ' (', d.created_by, ')')
                                    else concat(d.mdate, ' (', d.modified_by, ')')
                                end last_updated             
                            from rate_detail a
                                inner join denomination b on a.denom_id = b.id
                                inner join product c on b.product_id = c.id
                                inner join vendor_denom e on b.product_id = e.product_id
                                    and b.denom = e.denom
                                    and c.vendor_code = e.vendor_code
                                    {$query_vd} 
                                left join rate_detail d on d.rate_plan_id = :rate_plan_id
                                    and a.denom_id = d.denom_id        
                                    and d.action = :action
                                left join vendor f on c.vendor_code = f.code
                            where a.rate_plan_id = :parent_plan_id       
                            and b.status = 'A'
                            and c.status = 'A' 
                            and a.action = :action_r
                            {$query}
                            order by c.carrier, b.product_id, b.denom
                        ", [
                            'rate_plan_id_show' => $request->rate_plan_id,
                            'parent_plan_id' => $parent->rate_plan_id,
                            'rate_plan_id' => $request->rate_plan_id,
                            'action' => $request->action,
                            'action_r' => $request->action
                        ]);
                    }
                    break;
            }
            
            $login_acct_type = Auth::user()->account_type;

            return response()->json([
                'msg' => '',
                'rates' => $rates,
                'login_acct_type' => $login_acct_type,
                'rate_plan_name' => $rate_plan->name . ' ( ' . $rate_plan->id . ' )',
                'show_type' => $show_type,
                'product_id' => '',
                'product_name' => '',
                'vendor' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function load_excel(Request $request) {

        $rate_plan = RatePlan::find($request->cur_rate_plan);
        if (empty($rate_plan)) {
            return response()->json([
                'msg' => 'Invalid rate plan ID provided'
            ]);
        }

        $parent = Account::find($rate_plan->owner_id);

        $query = "";

        if (!empty($request->vendor)) {
            $query .= " and f.code = '$request->vendor' ";
        }
        if (!empty($request->product_id)) {
            $query .= " and c.id like '%". strtoupper($request->product_id) ."%' ";
        }
        if (!empty($request->product_name)) {
            $query .= " and upper(c.name) like '%". strtoupper($request->product_name) ."%' ";
        }

        $query_vd = '';
        if ($request->action == 'RTR') {
            $query_vd .= " and ifnull(e.rtr_pid, '') != '' ";
        } else if ($request->action == 'PIN') {
            $query_vd .= " and ifnull(e.pin_pid, '') != '' ";
        }

        $result = DB::select("
            select 
                d.id,
                :rate_plan_id_show as rate_plan_id,
                b.id as denom_id,
                b.product_id,
                c.name as product_name,
                '{$request->action}' action,
                b.denom,
                f.name as vendor,
                ifnull(e.cost, 0) as cost,
                a.rates as parent_rates,
                ifnull(d.rates, '') as rates,
                case when d.id is null then ''
                    when d.mdate is null then concat(d.cdate, ' (', d.created_by, ')')
                    else concat(d.mdate, ' (', d.modified_by, ')')
                end last_updated             
            from rate_detail a
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom e on b.product_id = e.product_id
                    and b.denom = e.denom
                    and c.vendor_code = e.vendor_code
                    {$query_vd} 
                left join rate_detail d on d.rate_plan_id = :rate_plan_id
                    and a.denom_id = d.denom_id        
                    and d.action = :action
                left join vendor f on c.vendor_code = f.code
            where a.rate_plan_id = :parent_plan_id       
            and b.status = 'A'
            and c.status = 'A' 
            and a.action = :action_r
            {$query}
            order by c.carrier, b.product_id, b.denom
        ", [
            'rate_plan_id_show' => $request->cur_rate_plan,
            'parent_plan_id' => $parent->rate_plan_id,
            'rate_plan_id' => $request->cur_rate_plan,
            'action' => $request->action,
            'action_r' => $request->action
        ]);

        Excel::create('discountRate', function($excel) use($result) {

            $excel->sheet('reports', function($sheet) use($result) {

                $reports = [];

                foreach ($result as $a) {

                    $reports[] = [
                        'Id'            => $a->id,
                        'Product'       => $a->product_name . ' (' . $a->product_id . ')',
                        'Action'        => $a->action,
                        'Denom'         => $a->denom,
                        'Rates'         => $a->rates,
                        'Download.Date' => date("m/d/Y h:i:s A")
                    ];
                }
                $sheet->fromArray($reports);

            });
        })->export('xlsx');
    }

}