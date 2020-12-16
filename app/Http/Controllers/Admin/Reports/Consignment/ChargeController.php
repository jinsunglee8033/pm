<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/7/18
 * Time: 11:05 AM
 */

namespace App\Http\Controllers\Admin\Reports\Consignment;


use App\Http\Controllers\Controller;
use App\Model\ConsignmentCharge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChargeController extends Controller
{
    public function show(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas',
              'admin', 'system']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $sdate = $request->get('sdate', Carbon::today()->startOfWeek()->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        $account_type = $request->get('account_type');
        $account_id = $request->get('account_id');
        $trans_id = $request->get('trans_id');

        $user = Auth::user();

        $query = ConsignmentCharge::query();

        if ($user->account_type != 'L') {
            $query = $query->where('account_id', $user->account_id);
        }

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<=', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($account_type)) {
            $query = $query->where('account_type', $account_type);
        }

        if (!empty($account_id)) {
            $query = $query->where('account_id', $account_id);
        }

        if (!empty($trans_id)) {
            $query = $query->where('trans_id', $trans_id);
        }

        $amt = $query->sum(DB::raw("if(type = 'S', amt, -amt)"));

        $data = $query->orderBy('cdate', 'desc')->paginate();

        return view('admin.reports.consignment.charge', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'quick' => $request->quick,
            'account_type' => $account_type,
            'trans_id' => $trans_id,
            'amt' => $amt,
            'account_id' => $account_id
        ]);
    }
}