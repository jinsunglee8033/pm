<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/11/17
 * Time: 9:33 AM
 */

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Lib\Permission;
use App\Model\Carrier;
use App\Model\Product;
use App\Model\RTRQueue;
use App\Model\Vendor;
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
use Illuminate\Support\Facades\DB;

class RTRQueueController extends Controller
{

    public function show(Request $request) {
        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        # show excel export button only to 'thomas', 'admin'
        $admin_users = ['thomas', 'admin', 'system'];
        $current_user = Auth::user()->user_id;
        $show_export = false;

        if (in_array($current_user, $admin_users)) {
            $show_export = true;
        }

        $sdate = Carbon::today();
        $edate = Carbon::today()->addDays(90)->subSeconds(1);

        if (!empty($request->input('sdate'))) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate);
        }

        if (!empty($request->input('edate'))) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate);
        }

        $records = RTRQueue::leftJoin('transaction', 'transaction.id', '=', 'rtr_queue.trans_id')
            ->leftJoin('stock_sim', 'stock_sim.sim_serial', '=', 'transaction.sim')
            ->leftJoin('product', 'product.id', '=', 'transaction.product_id')
            ->leftJoin('accounts', 'accounts.id', '=', 'transaction.account_id')
            ->where('rtr_queue.run_at', '>=', $sdate)
            ->where('rtr_queue.run_at', '<=', $edate);

        $records2 = Transaction::where('action', 'PIN')
            ->leftJoin('accounts', 'accounts.id', '=', 'transaction.account_id')
            ->leftJoin('product', 'product.id', '=', 'transaction.product_id')
            ->leftJoin('stock_sim', 'stock_sim.sim_serial', '=', 'transaction.sim')
            ->where('transaction.status', 'C')
            ->where('transaction.cdate', '>=', $sdate)
            ->where('transaction.cdate', '<=', $edate);

        $r_sdate = null;
        $r_edate = null;

        if (!empty($request->r_sdate)) {
            $r_sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->r_sdate);
            $records = $records->where('rtr_queue.result_date', '>=', $r_sdate);
        }

        if (!empty($request->r_edate)) {
            $r_edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->r_edate);
            $records = $records->where('rtr_queue.result_date', '<=', $r_edate);
        }

        if (!Permission::can($request->path(), 'non-at&t product')) {
            $records = $records->where('product.carrier', 'AT&T');
        }

        if (!empty($request->input('carrier', old('carrier')))) {
            $records = $records->where('product.carrier', $request->input('carrier', old('carrier')));
            $records2 = $records2->where('product.carrier', $request->input('carrier', old('carrier')));
        }

//        if (!empty($request->input('phone', old('phone')))) {
//            $records = $records->where('rtr_queue.phone', $request->input('phone', old('phone')));
//            $records2 = $records2->where('transaction.phone', $request->input('phone', old('phone')));
//        }
        if (!empty($request->input('phones', old('phones')))) {
            $phones = preg_split('/[\ \r\n\,]+/', $request->phones);
            $records = $records->whereIn('rtr_queue.phone', $phones);
            $records2 = $records2->whereIn('transaction.phone', $phones);
        }

        if (!empty($request->input('result', old('result')))) {
            $records = $records->where('rtr_queue.result', $request->input('result', old('result')));

            $result = $request->input('result', old('result'));
            if($result == 'S'){
                $status = 'C';
            }else{
                $status = 'F';
            }
            $records2 = $records2->where('transaction.status', $status);
        }

        if (!empty($request->input('sim_type', old('sim_type')))) {
            $records = $records->where('stock_sim.type', $request->input('sim_type', old('sim_type')));
        }

//        if (!empty($request->input('sim', old('sim')))) {
//            $records = $records->where('transaction.sim', $request->input('sim', old('sim')));
//        }
        if (!empty($request->input('sims', old('sims')))) {
            $sims = preg_split('/[\ \r\n\,]+/', $request->sims);
            $records = $records->whereIn('transaction.sim', $sims);
        }

        if (!empty($request->input('esns', old('esns')))) {
            $esns = preg_split('/[\ \r\n\,]+/', $request->esns);
            $records = $records->whereIn('transaction.esn', $esns);
        }

        if (!empty($request->input('seq', old('seq')))) { //RTR month 1/1, 1/2, etc.
            $records = $records->where('rtr_queue.seq', $request->input('seq', old('seq')));
        }

        if (!empty($request->input('account_id', old('account_id')))) {
            $records = $records->where('accounts.id', $request->input('account_id', old('account_id')));
            $records2 = $records2->where('accounts.id', $request->input('account_id', old('account_id')));
        }

        if (!empty($request->input('account_name', old('account_name')))) {
            $records = $records->whereRaw("lower(accounts.name) like ?", '%'. strtolower($request->input('account_name', old('account_name'))) . '%');
        }

        if (!empty($request->input('product_id', old('product_id')))) {
            $records = $records->where('product.id', $request->input('product_id', old('product_id')));
            $records2 = $records2->where('product_id', $request->input('product_id', old('product_id')));
        }

        if (!empty($request->input('category', old('category')))) {
            $records = $records->where('rtr_queue.category', $request->input('category', old('category')));
        }

        if (!empty($request->input('vendor', old('vendor')))) {
            $records = $records->where('rtr_queue.vendor_code', $request->input('vendor', old('vendor')));
            $records2 = $records2->where('transaction.vendor_code', $request->input('vendor', old('vendor')));
        }

        if (!empty($request->input('account_ids', old('account_ids')))) {
            $account_ids = preg_split('/[\ \r\n\,]+/', $request->account_ids);
            $records = $records->whereIn('accounts.id', $account_ids);
            $records2 = $records2->whereIn('accounts.id', $account_ids);
        }

        if (!empty($request->action)) {
            switch ($request->action) {
                case 'Activation,Port-In':
                    $records = $records->whereIn('transaction.action', ['Activation', 'Port-In']);
                    break;
                case 'RTR,PIN':
                    $records = $records->whereIn('transaction.action', ['RTR', 'PIN']);
                    break;
                default:
                    $records = $records->where('transaction.action', $request->action);
                    break;
            }
            switch ($request->action) {
                case 'Activation,Port-In':
                    $records2 = $records2->whereIn('transaction.action', ['Activation', 'Port-In']);
                    break;
                case 'RTR,PIN':
                    $records2 = $records2->whereIn('transaction.action', ['RTR', 'PIN']);
                    break;
                default:
                    $records2 = $records2->where('transaction.action', $request->action);
                    break;
            }
        }

        $records = $records->select('rtr_queue.id as id',
            'rtr_queue.trans_id as trans_id',
            'rtr_queue.category as category',
            'rtr_queue.phone as phone',
            // if waiting, point to product's vendor ,,, else point to rtr_queue's vendor (7/21/20)
            DB::raw("if(rtr_queue.result = 'N', product.vendor_code, rtr_queue.vendor_code) as vendor_code"),
            'rtr_queue.vendor_pid as vendor_pid',
            'rtr_queue.amt as amt_q',
            'rtr_queue.fee as fee',
            'rtr_queue.run_at as run_at',
            'rtr_queue.seq as seq',
            'rtr_queue.result as result',
            'rtr_queue.result_msg as result_msg',
            'rtr_queue.result_date as result_date',
            'rtr_queue.cdate as cdate',
            'rtr_queue.created_by as created_by',
            'product.carrier as carrier_q',
            'product.name as prod_name',
            'accounts.id as acct_id',
            'accounts.type as acct_type',
            'accounts.name as acct_name',
            'stock_sim.type as sim_type_q',
            'transaction.sim as sim_q',
            'transaction.esn as esn_q',
            'transaction.denom as denom');

        $records2 = $records2->select(DB::raw(
            ' "0" as id,
            transaction.id as trans_id,
            "-" as category,
            transaction.phone as phone,
            transaction.vendor_code as vendor_code,
             "-" as vendor_pid,
            transaction.collection_amt as amt_q,
            transaction.fee as fee,
            transaction.cdate as run_at,
             "-" as seq,
             transaction.status as result,
             transaction.note as result_msg,
            transaction.cdate as result_date,
            transaction.cdate as cdate,
            transaction.created_by as created_by,
            product.carrier as carrier_q,
            product.name as prod_name,
            accounts.id as acct_id,
            accounts.type as acct_type,
            accounts.name as acct_name,
            stock_sim.type as sim_type_q,
            transaction.sim as sim_q,
            transaction.esn as esn_q,
            transaction.denom as denom'));

        $records3 = $records->union($records2)->orderBy('cdate', 'desc');

        $records4 = $records3->get();

        if ($request->excel == 'Y') {
            $rtr_queues = $records4;
            Excel::create('RTRQueues', function($excel) use($rtr_queues) {
                ini_set('memory_limit', '2048M');
                $excel->sheet('reports', function($sheet) use($rtr_queues) {
                    $data = [];
                    foreach ($rtr_queues as $o) {

                        if($o->id != 0) {
                            $id = $o->id;
                            $result = $o->getResultNameAttribute();
                        }else{
                            $id = 'PIN';
                            if($o->result == 'C'){
                                $result = 'Success';
                            }else{
                                $result = 'Fail';
                            }
                        }

                        if($o->sim_type_q == 'B'){
                            $sim_type_q = 'Bundle';
                        }elseif($o->sim_type_q == 'P'){
                            $sim_type_q = 'Quick-Spiff';
                        }elseif($o->sim_type_q == 'R'){
                            $sim_type_q = 'Regular';
                        }else{
                            $sim_type_q = '';
                        }

                        $row = [
                            'Q.ID' => $id,
                            'Act.Tx.ID' => $o->trans_id,
                            'Account.ID' => $o->acct_id,
                            'Account.Type' => $o->acct_type,
                            'Account.Name' => $o->acct_name,
                            'Carrier' => $o->carrier_q,
                            'Phone' => $o->phone,
                            'SIM' => $o->sim_q,
                            'SIM.Type' => $sim_type_q,
                            'ESN' => $o->esn_q,
                            'Product' => $o->prod_name,
                            'Amt($)' => $o->amt_q,
                            'Scheduled.On' => $o->run_at,
                            'Seq' => $o->seq,
                            'Vendor' => $o->vendor_code,
                            'Source' => $o->category,
                            'Result' => $result,
                            'Result.Msg' => $o->result_msg,
                            'Ran.At' => $o->result_date
                        ];
                        $data[] = $row;
                    }
                    $sheet->fromArray($data);
                });
            })->export('xlsx');
        }

        $counts = count($records4);

        $carriers = Carrier::query();
        if (!Permission::can($request->path(), 'non-at&t product')) {
            $carriers->where('name', 'AT&T');
        }

        $carriers = $carriers->get();

        $products = Product::query();
        if (!empty($request->input('carrier', old('carrier')))) {
            $products = $products->where('carrier', $request->input('carrier', old('carrier')));
        }

        if (!Permission::can($request->path(), 'non-at&t product')) {
            $products->where('carrier', 'AT&T');
        }

        $products = $products->get();

        $vendors = Vendor::where('status', 'A')->get();

        return view('admin.reports.rtr-q', [
            'records' => $records4,
            'sdate' => $sdate->format('Y-m-d HH:00:00'),
            'edate' => $edate->format('Y-m-d HH:00:00'),
            'r_sdate' => $r_sdate,
            'r_edate' => $r_edate,
            'carrier' => $request->input('carrier', old('carrier')),
            'phone' => $request->input('phone', old('phone')),
            'result' => $request->input('result', old('result')),
            'sim_type' => $request->input('sim_type', old('sim_type')),
            'sim' => $request->input('sim', old('sim')),
            'seq' => $request->input('seq', old('seq')),
            'account_id' => $request->input('account_id', old('account_id')),
            'account_name' => $request->input('account_name', old('account_name')),
            'show_export' => $show_export,
            'carriers' => $carriers,
            'products' => $products,
            'product_id' => $request->input('product_id', old('product_id')),
            'category' => $request->input('category', old('category')),
            'counts'    => $counts,
            'vendors'   => $vendors,
            'vendor'    => $request->vendor,
            'account_ids' => $request->account_ids,
            'sims'      => $request->sims,
            'phones'      => $request->phones,
            'esns'      => $request->esns,
            'action'    => $request->action
        ]);
    }

    public function retry(Request $request) {
        try {
            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                return back()->withErrors([
                    $v
                ])->withInput();
            }

            $q = RTRQueue::find($request->id);
            if (empty($q)) {
                return back()->withErrors([
                    'exception' => 'Invalid RTR Queue ID provided'
                ])->withInput();
            }

            $q->result = 'N';
            $q->save();

            $job = (new ProcessRTR($q))
                ->onQueue('RTR')
                ->delay(Carbon::now()->addMinutes(0));
            dispatch($job);

            return back()->withInput();

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode(). ']'
            ])->withInput();
        }
    }

}