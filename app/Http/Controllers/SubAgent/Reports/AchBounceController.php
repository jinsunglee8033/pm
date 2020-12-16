<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/23/18
 * Time: 10:57 AM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\ACHPosting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AchBounceController extends Controller
{

    public function show(Request $request) {
        try {
            if(!Auth::check() || Auth::user()->account_type != 'S') {
                return redirect('/sub-agent');
            }

            $sdate = Carbon::today()->subYear();
            $edate = Carbon::today()->addDays(1)->addSeconds(-1);

            if (!empty($request->sdate) && empty($request->id)) {
                $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
            }

            if (!empty($request->edate) && empty($request->id)) {
                $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
            }

            $account = Account::find(Auth::user()->account_id);

            $query = ACHPosting::join('accounts', 'accounts.id', 'ach_posting.account_id')
                ->where('accounts.path', 'like', $account->path . '%')
                ->where('ach_posting.bounce_date', '>=', $sdate)
                ->where('ach_posting.bounce_date', '<', $edate);

            if (!empty($request->account_id)) {
                $query = $query->where('ach_posting.account_id', $request->account_id);
            }

            $bounce_amt = $query->sum('ach_posting.amt');
            $bounce_fee = $query->count() * PaymentProcessor::$bounce_fee;

            $data = $query->orderBy('ach_posting.bounce_date', 'desc')->select('ach_posting.*')->paginate(20);

            return view('sub-agent.reports.ach-bounce', [
                'data' => $data,
                'sdate' => $sdate->format('Y-m-d'),
                'edate' => $edate->format('Y-m-d'),
                'account_id' => $request->account_id,
                'bounce_amt' => $bounce_amt,
                'bounce_fee' => $bounce_fee
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}