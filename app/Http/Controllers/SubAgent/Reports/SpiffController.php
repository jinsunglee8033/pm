<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/28/17
 * Time: 7:57 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Model\SpiffTrans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SpiffController extends Controller
{

    public function show(Request $request) {

        $sdate = $request->get('sdate', Carbon::today()->addDays(-30)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->format('Y-m-d');
        }

        $query = SpiffTrans::join('transaction', 'spiff_trans.trans_id', '=', 'transaction.id')
            ->leftjoin("stock_sim", function($join) {
                $join->on('transaction.sim', 'stock_sim.sim_serial')
                    ->where('transaction.product_id', 'stock_sim.product');
            })
            ->where('spiff_trans.account_id', Auth::user()->account_id);

        $query2 = SpiffTrans::select("spiff_trans.id as id",
            "spiff_trans.note as account_name" ,
            "spiff_trans.type as type",
            "spiff_trans.trans_id as trans_id",
            "spiff_trans.phone as phone",
            "spiff_trans.product_id as product_id",
            "spiff_trans.denom as denom",
            "spiff_trans.account_type as spiff_account_type",
            "spiff_trans.spiff_month as spiff_month",
            "spiff_trans.spiff_amt as spiff_amt",
            "spiff_trans.type as is_byos",
            "spiff_trans.note as note",
            "spiff_trans.cdate as cdate",
            "spiff_trans.type as sim_type",
            "spiff_trans.special_id as special_id"
            )->where('spiff_trans.account_id', Auth::user()->account_id)
            ->where('trans_id',0)
            ->where('note','Bonus By Account');

        if (!empty($sdate)) {
            $query = $query->where('spiff_trans.cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
            $query2 = $query2->where('spiff_trans.cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('spiff_trans.cdate', '<', Carbon::parse($edate . ' 23:59:59'));
            $query2 = $query2->where('spiff_trans.cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->phone)) {
            $query = $query->where('spiff_trans.phone', $request->phone);
            $query2 = $query2->where('spiff_trans.phone', $request->phone);
        }

        if (!empty($request->trans_id)) {
            $query = $query->where('spiff_trans.trans_id', $request->trans_id);
        }

        if (!empty($request->spiff_month)) {
            $query = $query->where('spiff_trans.spiff_month', $request->spiff_month);
        }

        if (!empty($request->spiff_type)) {
            if($request->spiff_type == 'Regular_Spiff'){ // spiff
                $query = $query->whereNull('spiff_trans.special_id')
                                ->whereNull('spiff_trans.note')
                                ->orWhere('spiff_trans.note', 'like', '%' . 'Bonus Spiff' . '%')
                                ->where('spiff_trans.note', 'like', '%' . 'Extra Bonus spiff' . '%');
            }elseif ($request->spiff_type == 'Special_Spiff'){ // special spiff
                $query = $query->whereNotNull('spiff_trans.special_id');
            }elseif ($request->spiff_type == 'Bonus'){ // bonus
                $query = $query->where('spiff_trans.note', 'like', '%' . 'Bonus Spiff' . '%');
            }
        }

//        $spiff_amt = $query->sum(DB::raw("if(spiff_trans.type = 'S', spiff_trans.spiff_amt, -spiff_trans.spiff_amt)"));

//        $data = $query->orderBy('spiff_trans.cdate', 'desc')
//          ->paginate(15,[
//              'spiff_trans.*',
//              DB::raw("case stock_sim.type when 'C' then 'Consignment' when 'P' then 'Preload' else 'Regular' end as sim_type"),
//              DB::raw("case when spiff_trans.special_id is not null then 'Special Spiff' when spiff_trans.note like '%Bonus Spiff%' then 'Bonus' else 'Regular Spiff' end as spiff_type"),
//        ]);

        $data = $query->select(
            'spiff_trans.id as id',
            'spiff_trans.account_id as account_id',
            'spiff_trans.type as type',
            'spiff_trans.trans_id as trans_id',
            'spiff_trans.phone as phone',
            'spiff_trans.product_id as product_id',
            'spiff_trans.denom as denom',
            'spiff_trans.account_type as spiff_account_type',
            'spiff_trans.spiff_month as spiff_month',
            'spiff_trans.spiff_amt as spiff_amt',
            'stock_sim.is_byos as is_byos',
            'spiff_trans.note as note',
            'spiff_trans.cdate as cdate',
            DB::raw("case stock_sim.type when 'C' then 'Consignment' when 'P' then 'Preload' else 'Regular' end as sim_type"),
            DB::raw("case when spiff_trans.special_id is not null then 'Special Spiff' when spiff_trans.note like '%Bonus Spiff%' then 'Bonus' else 'Regular Spiff' end as spiff_type")
        )
            ->union($query2)
            ->orderBy('id', 'desc')
            ->get();




        return view('sub-agent.reports.spiff', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'phone' => $request->phone,
            'trans_id' => $request->trans_id,
            'spiff_month' => $request->spiff_month,
            'spiff_type' => $request->spiff_type,
//            'spiff_amt' => $spiff_amt
        ]);
    }

}