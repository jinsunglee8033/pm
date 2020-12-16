<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 3/18/19
 * Time: 10:41 AM
 */

namespace App\Http\Controllers\SubAgent\Reports;

use App\Http\Controllers\Controller;
use App\Model\Carrier;
use App\Model\VendorDenom;
use Illuminate\Http\Request;
use App\Model\Transaction;
use App\Model\Product;
use App\Model\Denom;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use Excel;
use App\Events\TransactionStatusUpdatedRoot;
use App\Events\TransactionStatusUpdated;
use App\Lib\Helper;
use App\Lib\h2o;
use App\Lib\gss;
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/27/17
 * Time: 1:15 PM
 */
class GENController extends Controller
{

    public function show(Request $request) {
        try {
            if (!in_array(Auth::user()->account_id, [100037, 105124])) {
                return back();
            }

            $sdate = Carbon::today()->startOfMonth();
            $edate = Carbon::today()->addDays(1)->addSeconds(-1);

            if (!empty($request->sdate)) {
                $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
            }

            if (!empty($request->edate)) {
                $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
            }

            $query = Transaction::join('product', 'transaction.product_id', 'product.id')
              ->join("accounts", 'transaction.account_id', 'accounts.id')
              ->join("accounts as master", "accounts.master_id", "master.id")
              ->Leftjoin("accounts as dist", function($join) {
                  $join->on('accounts.parent_id', 'dist.id')
                    ->where('dist.type', 'D');
              })->where('transaction.status', 'C');

            if (!empty($sdate)) {
                $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) >= ?', [$sdate]);
            }

            if (!empty($edate)) {
                $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) <= ?', [$edate]);
            }

            $query = $query->where('product.carrier', 'GEN Mobile');

            if (!empty($request->master_id)) {
                $query = $query->where('master.id', $request->master_id);
            }

            if (!empty($request->action)) {
                switch ($request->action) {
                    case 'Activation,Port-In':
                        $query = $query->whereIn('transaction.action', ['Activation', 'Port-In']);
                        break;
                    case 'RTR,PIN':
                        $query = $query->whereIn('transaction.action', ['RTR', 'PIN']);
                        break;
                    default:
                        $query = $query->where('transaction.action', $request->action);
                        break;
                }
            }

            if ($request->excel == 'Y') {
                $transactions = $query->orderByRaw('transaction.cdate desc')
                  ->select(
                    'transaction.id',
                    \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                    'master.id as master_id',
                    'master.name as master_name',
                    'transaction.denom',
                    'transaction.action',
                    'transaction.sim',
                    'transaction.esn',
                    'transaction.phone',
                    'transaction.cdate'
                  )
                  ->get();
                Excel::create('transactions', function($excel) use($transactions) {

                    $excel->sheet('reports', function($sheet) use($transactions) {

                        $data = [];
                        foreach ($transactions as $o) {
                            $row = [
                              'Tx.ID' => $o->id,
                              'Type' => $o->type,
                              'Account.Name' => $o->master_name,
                              'Denom($)' => $o->denom,
                              'Action' => $o->action,
                              'SIM' => empty($o->sim) ? '' : $o->sim,
                              'ESN' => empty($o->esn) ? '' : $o->esn,
                              'Phone' => $o->phone,
                              'Created.At' => $o->cdate
                            ];

                            $data[] = $row;

                        }

                        $sheet->fromArray($data);

                    });

                })->export('xlsx');

            }

            $masters = Account::where('type', 'M')->orderBy('name')->get();

            $transactions = $query->orderByRaw('transaction.cdate desc')
              ->select(
                'transaction.id',
                \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                'master.id as master_id',
                'master.name as master_name',
                'transaction.denom',
                'transaction.action',
                'transaction.sim',
                'transaction.esn',
                'transaction.phone',
                'transaction.cdate'
              )->paginate(20);

            return view('sub-agent.reports.gen', [
                'transactions' => $transactions,
                'sdate'     => $sdate->format('Y-m-d'),
                'edate'     => $edate->format('Y-m-d'),
                'action'    => $request->action,
                'master_id' => $request->master_id,
                'masters'   => $masters
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
              'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

}