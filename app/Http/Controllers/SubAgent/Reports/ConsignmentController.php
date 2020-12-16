<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/7/18
 * Time: 3:07 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsignmentController extends Controller
{
    public function show(Request $request) {

        $type = $request->get('type');

        $user = Auth::user();

        $query = "
            select
                'ESN' as type,
                esn as sim_esn,
                charge_amount_r as charge_amt
            from stock_esn 
            where type = 'C'
            and owner_id = ?
            and status = 'A'
        ";

        if (!empty($type)) {
            $query .= " and 'ESN' = '$type' ";
        }

        $query .= "
            union 
            select 
                'SIM' as type,
                sim_serial as sim_esn,
                charge_amount_r as charge_amt
            from stock_sim
            where type = 'C'
            and owner_id = ?
            and status = 'A'
        ";

        if (!empty($type)) {
            $query .= " and 'SIM' = '$type' ";
        }

        $data = DB::select($query,[
           $user->account_id, $user->account_id
        ]);

        $data = Helper::arrayPaginator($data, $request);

        $amt = 0;
        foreach ($data as $o) {
            $amt += $o->charge_amt;
        }

        return view('sub-agent.reports.consignment-balance', [
            'data' => $data,
            'type' => $type,
            'amt' => $amt
        ]);
    }
}