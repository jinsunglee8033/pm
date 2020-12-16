<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/28/17
 * Time: 3:23 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Model\RebateTrans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RebateController extends Controller
{

    public function show(Request $request) {

        $sdate = $request->get('sdate', Carbon::today()->startOfWeek()->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->format('Y-m-d');
        }

        $query = RebateTrans::where('account_id', Auth::user()->account_id);

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->phone)) {
            $query = $query->where('phone', $request->phone);
        }

        if (!empty($request->trans_id)) {
            $query = $query->where('trans_id', $request->trans_id);
        }

        $rebate_amt = $query->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

        $data = $query->orderBy('cdate', 'desc')->paginate();


        return view('sub-agent.reports.rebate', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'phone' => $request->phone,
            'trans_id' => $request->trans_id,
            'rebate_amt' => $rebate_amt
        ]);
    }

}