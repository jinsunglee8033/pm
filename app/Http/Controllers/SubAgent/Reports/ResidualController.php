<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/5/17
 * Time: 4:32 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Model\Residual;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResidualController extends Controller
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

        $query = Residual::where('account_id', Auth::user()->account_id);

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->phone)) {
            $query = $query->where('mdn', $request->phone);
        }

        $amt = $query->sum('amt');

        $data = $query->orderBy('cdate', 'desc')->paginate();


        return view('sub-agent.reports.residual', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'phone' => $request->phone,
            'trans_id' => $request->trans_id,
            'amt' => $amt
        ]);
    }
}