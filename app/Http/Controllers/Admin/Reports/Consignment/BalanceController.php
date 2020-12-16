<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/7/18
 * Time: 3:26 PM
 */

namespace App\Http\Controllers\Admin\Reports\Consignment;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{

    public function show(Request $request) {

        $type = $request->get('type');
        $sim_esn = $request->get('sim_esn');

        $user = Auth::user();
        $login_account = Account::find($user->account_id);

        $balance_column = '';
        switch ($login_account->type) {
            case 'M':
                $balance_column = 'charge_amount_m';
                break;
            case 'D':
                $balance_column = 'charge_amount_d';
                break;
        }

        $query = "
            select
                b.path,
                'ESN' as type,
                a.esn as sim_esn,
                b.parent_id,
                b.id as account_id,
                b.type as account_type,
                b.name as account_name,
                a." . $balance_column . " as charge_amt
            from stock_esn a
                inner join accounts b on a.owner_id = b.id
                    and b.path like concat(?, '%')
            where a.type = 'C'
            and a.status = 'A'
        ";

        if (!empty($type)) {
            $query .= " and 'ESN' = '$type' ";
        }

        if (!empty($sim_esn)) {
            $query .= " and a.esn = '$sim_esn' ";
        }

        $query .= "
            union 
            select 
                b.path,
                'SIM' as type,
                a.sim_serial as sim_esn,
                b.parent_id,
                b.id as account_id,
                b.type as account_type,
                b.name as account_name,
                a." . $balance_column . " as charge_amt
            from rok_sims a
                inner join accounts b on a.owner_id = b.id
                    and b.path like concat(?, '%')
            where a.type = 'C'
            and a.status = 'A'
        ";

        if (!empty($type)) {
            $query .= " and 'SIM' = '$type' ";
        }

        if (!empty($sim_esn)) {
            $query .= " and a.sim_serial = '$sim_esn' ";
        }

        $query .= " order by 1 asc ";

        $data = DB::select($query,[
            $login_account->path, $login_account->path
        ]);

        $data = Helper::arrayPaginator($data, $request);

        $amt = 0;
        foreach ($data as $o) {
            $amt += $o->charge_amt;
        }

        return view('admin.reports.consignment.balance', [
            'data' => $data,
            'type' => $type,
            'sim_esn' => $sim_esn,
            'amt' => $amt
        ]);
    }

}