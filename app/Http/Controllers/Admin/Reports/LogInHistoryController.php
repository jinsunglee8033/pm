<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 8/10/20
 * Time: 9:41 AM
 */

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\LoginHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LogInHistoryController extends Controller
{

    public function show(Request $request) {
        try {
            if(!Auth::check() || Auth::user()->account_type == 'S') {
                return redirect('/admin');
            }

            $sdate = Carbon::today()->subYear();
            $edate = Carbon::today()->addDays(1)->addSeconds(-1);

            if (!empty($request->sdate) && empty($request->id)) {
                $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
            }

            if (!empty($request->edate) && empty($request->id)) {
                $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
            }

            $query = LoginHistory::LeftJoin('users', function ($join) {
                $join->on('users.user_id', 'login_history.user_id');
            })->leftJoin('accounts', function ($join){
                $join->on('accounts.id', 'users.account_id');
            })->where('login_history.cdate', '>=', $sdate)
                ->where('login_history.cdate', '<=', $edate)
                ->whereNotIn('users.user_id', ['admin', 'demo_sa']);

            if (!empty($request->ip)) {
                $query = $query->where('login_history.ip', $request->ip);
            }

            if (!empty($request->user_id)) {
                $query = $query->where('login_history.user_id', $request->user_id);
            }

            if (!empty($request->account_id)) {
                $query = $query->where('accounts.id', $request->account_id);
            }

            if (!empty($request->result)) {
                $query = $query->where('login_history.result', $request->result);
            }

            if (!empty($request->no_login)) {

                $data = Account::where('accounts.status', '!=', 'C')
                    ->whereRaw(' accounts.id not in (select distinct b.account_id from login_history a
                    inner join users b on a.user_id = b.user_id
                    where a.result = "S"
                    and a.cdate >= "' .$sdate. '"
                    and a.cdate <= "' .$edate. '") ')
                    ->orderBy('accounts.id', 'asc')
                    ->select(DB::raw(' "-" as id,
                        "-" as user_id,
                        "-" as result,
                        "-" as result_msg,
                        "-" as ip,
                        "-" as cdate,
                        accounts.id as account_id,
                        accounts.name as account_name,
                        accounts.type as account_type '
                        ));

            }else{
                $data = $query->orderBy('login_history.cdate', 'desc')
                    ->select('login_history.id as id',
                        'login_history.user_id as user_id',
                        'login_history.result as result',
                        'login_history.result_msg as result_msg',
                        'login_history.ip as ip',
                        'login_history.cdate as cdate',
                        'accounts.id as account_id',
                        'accounts.name as account_name',
                        'accounts.type as account_type'
                    );
            }

            if ($request->excel == 'Y') {
                $data = $data->get();
                Excel::create('Login_history' . date("mdY_h:i:s_A"), function($excel) use($data) {

                    $excel->sheet('reports', function($sheet) use($data) {

                        $reports = [];

                        foreach ($data as $a) {

                            $reports[] = [
                                'ID' => $a->id,
                                'Account.Type' => $a->account_type,
                                'Account.Name' => $a->account_name,
                                'Account.ID' => $a->account_id,
                                'User.ID' => $a->user_id,
                                'Result' => $a->result,
                                'Error.MSG' => $a->result_msg,
                                'IP' => $a->ip,
                                'Login.Date' => $a->cdate
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }

            $data = $data->paginate(20);

            return view('admin.reports.login-history', [
                'data' => $data,
                'sdate' => $sdate->format('Y-m-d'),
                'edate' => $edate->format('Y-m-d'),
                'quick' => $request->quick,
                'user_id' => $request->user_id,
                'account_id' => $request->account_id,
                'result'    => $request->result,
                'ip'    => $request->ip,
                'no_login'  => $request->no_login
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}