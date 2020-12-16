<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/5/18
 * Time: 4:58 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Model\Credit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditController extends Controller
{

    public function show(Request $request) {
        $sdate = $request->get('sdate', Carbon::today()->subDays(30)->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->subDays(30)->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->format('Y-m-d');
        }

        $type = $request->get('type');
        $comments = $request->get('comments');

        $user = Auth::user();

        $query = Credit::where('account_id', $user->account_id);

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($type)) {
            $query = $query->where('type', $type);
        }

        if (!empty($comments)) {
            $query = $query->whereRaw('lower(comments) like ?', ['%' . strtolower($comments) . '%']);
        }

        $amt = $query->sum(DB::raw("if(type = 'C', amt, -amt)"));

        $data = $query->orderBy('cdate', 'desc')->paginate();

        return view('sub-agent.reports.credit', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'type' => $type,
            'amt' => $amt,
            'comments' => $comments
        ]);
    }

}