<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/11/17
 * Time: 9:33 AM
 */

namespace App\Http\Controllers\SubAgent\Reports;

use App\Http\Controllers\Controller;
use App\Model\RTRQueue;
use Illuminate\Http\Request;
use App\Model\Transaction;
use App\Model\TransactionLog;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use App\Events\TransactionStatusUpdated;
use Excel;
use App\Jobs\ProcessRTR;

class RTRQueueController extends Controller
{

    public function show(Request $request) {

        $sdate = Carbon::today();
        $edate = Carbon::today()->addDays(90)->addSeconds(-1);

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $records = RTRQueue::join('transaction', 'transaction.id', '=', 'rtr_queue.trans_id')
            ->leftjoin('h2o_sims', 'h2o_sims.sim_serial', 'transaction.sim')
            ->where('rtr_queue.run_at', '>=', $sdate)
            ->where('rtr_queue.run_at', '<', $edate);

        if (!empty($request->carrier)) {
            $records = $records->where('rtr_queue.category', $request->carrier);
        }

        if (!empty($request->phone)) {
            $records = $records->where('rtr_queue.phone', $request->phone);
        }

        if (!empty($request->result)) {
            $records = $records->where('rtr_queue.result', $request->result);
        }

        if (!empty($request->sim_type)) {
            $records = $records->where('h2o_sims.type', $request->sim_type);
        }

        if (!empty($request->sim)) {
            $records = $records->where('transaction.sim', $request->sim);
        }

        if (!empty($request->seq)) {
            $records = $records->where('rtr_queue.seq', $request->seq);
        }

        $records = $records->where('transaction.account_id', Auth::user()->account_id)->select('rtr_queue.*', 'transaction.denom as denom')->orderBy('id', 'desc')->paginate(20);

        return view('sub-agent.reports.rtr-q', [
            'records' => $records,
            'sdate' => $sdate->format('Y-m-d'),
            'edate' => $edate->format('Y-m-d'),
            'carrier' => $request->carrier,
            'phone' => $request->phone,
            'result' => $request->result,
            'sim_type' => $request->sim_type,
            'sim' => $request->sim,
            'seq' => $request->seq
        ]);
    }

}