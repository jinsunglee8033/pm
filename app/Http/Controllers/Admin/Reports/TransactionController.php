<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Events\TransactionStatusUpdatedRoot;
use App\Http\Controllers\Controller;
use App\Lib\boom;
use App\Lib\emida2;
use App\Lib\gss;
use App\Lib\Helper;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\Permission;
use App\Lib\VoidProcessor;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\GenFee;
use App\Model\H2OSim;
use App\Model\Product;
use App\Model\RebateTrans;
use App\Model\RTRQueue;
use App\Model\SpiffSetupSpecial;
use App\Model\SpiffTrans;
use App\Model\StockESN;
use App\Model\StockMapping;
use App\Model\Vendor;
use Illuminate\Http\Request;
use App\Model\Transaction;
use App\Model\TransactionLog;
use App\Model\Account;
use App\Model\StockSim;
use App\Model\VendorDenom;
use App\Model\Promotion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use App\Events\TransactionStatusUpdated;
use App\Lib\h2o;

/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/27/17
 * Time: 2:30 PM
 */
class TransactionController extends Controller
{
    public function show(Request $request) {
        try {

            $sdate = Carbon::today();
            $edate = Carbon::today()->addDays(1)->subSeconds(1);

            if (!empty($request->sdate) && empty($request->id)) {
                $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate);
            }

            if (!empty($request->edate) && empty($request->id)) {
                $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate);
            }

            $account = Account::find(Auth::user()->account_id);

            $query = Transaction::join('product', 'transaction.product_id', 'product.id')
                ->join("accounts", 'transaction.account_id', 'accounts.id')
                ->join("accounts as master", "accounts.master_id", "master.id")
                ->Leftjoin("stock_sim as sim_obj", function($join) {
                    $join->on('transaction.sim', 'sim_obj.sim_serial')
                        ->where('transaction.product_id', 'sim_obj.product');
                })
              ->Leftjoin("stock_esn as esn_obj", function($join) {
                  $join->on('transaction.esn', 'esn_obj.esn');
                  $join->on('transaction.product_id', 'esn_obj.product');
              })
                ->Leftjoin("accounts as dist", function($join) {
                    $join->on('accounts.parent_id', 'dist.id')
                        ->where('dist.type', 'D');
                })
                ->Leftjoin("denomination as denom", function($join) {
                    $join->on('transaction.denom_id', 'denom.id');
                    $join->on('transaction.product_id', 'denom.product_id');
                });

            if (!Permission::can($request->path(), 'non-at&t product')) {
                $query = $query->where('product.carrier', 'AT&T');
            }

            if (!empty($sdate) && empty($request->id)) {
                $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) >= ?', [$sdate]);
            }

            if (!empty($edate) && empty($request->id)) {
                $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) <= ?', [$edate]);
            }

            $query = $query->where('accounts.path', 'like', $account->path . '%');

            if (!empty($request->carrier)) {
                $query = $query->where('product.carrier', $request->carrier);
            }

//            if (!empty($request->phone)) {
//                $query = $query->where('transaction.phone', 'like', '%' . $request->phone . '%');
//            }
            if (!empty($request->phones)) {
                $phones = preg_split('/[\ \r\n\,]+/', $request->phones);
                $query = $query->whereIn('transaction.phone', $phones);
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

            if (!empty($request->status)) {
                $query = $query->where('transaction.status', $request->status);
            }
//            if (!empty($request->sim)) {
//                $query = $query->where('transaction.sim', 'like', '%' . $request->sim . '%');
//            }
            if (!empty($request->sims)) {
                $sims = preg_split('/[\ \r\n\,]+/', $request->sims);
                $query = $query->whereIn('transaction.sim', $sims);
            }
            if (!empty($request->user_id)) {
                $query = $query->where('transaction.created_by', 'like', '%' . $request->user_id . '%');
            }

            if (!empty($request->account_id)) {
                $search_account = Account::find($request->account_id);
                if (!empty($search_account)) {
                    $query = $query->where('accounts.path', 'like', $search_account->path . '%');
                }
            }
            if (!empty($request->account_ids)) {
                $account_ids = preg_split('/[\ \r\n\,]+/', $request->account_ids);
                $query = $query->whereIn('transaction.account_id', $account_ids);
            }

            if (!empty($request->account_name)) {
                $query = $query->whereRaw("lower(accounts.name) like ?", '%' . strtolower($request->account_name) . '%');
            }

//            if (!empty($request->esn)) {
//                $query = $query->whereRaw("(transaction.esn like '%" . $request->esn. "%' or lower(esn_obj.supplier_model) like '%" . strtolower($request->esn) . "%')");
//            }
            if (!empty($request->esns)) {
                $esns = preg_split('/[\ \r\n\,]+/', $request->esns);
                $query = $query->whereIn('transaction.esn', $esns);
            }
            if (!empty($request->id)) {
                $query = $query->where('transaction.id', $request->id);
            }

            if (!empty($request->sim_type)) {
                $query = $query->where(DB::raw("f_get_sim_type(transaction.id)"), $request->sim_type);
            }

            if (!empty($request->seq)) {
                $query = $query->whereRaw("
                    transaction.id in (
                        select distinct trans_id
                        from rtr_queue
                        where lower(seq) like ? 
                    )
                ", '%' . strtolower($request->seq) . '%');
            }

            if (!empty($request->product)) {
                $query = $query->whereRaw("lower(product.name) like '%" . strtolower($request->product) . "%'");
            }

            if (!empty($request->api_vendor)) {
                $query = $query->where('transaction.vendor_code' ,$request->api_vendor);
            }

            if (!empty($request->sales_type)) {
                $query = $query->where('transaction.type' ,$request->sales_type);
            }

            if (!empty($request->note)) {
                $query = $query->whereRaw("lower(transaction.note) like '%" . strtolower($request->note) . "%' or lower(transaction.note2) like '%" . strtolower($request->note) . "%'");
            }

            if (!empty($request->denomination)) {
                $query = $query->where('transaction.denom', $request->denomination);
            }

            if ($request->excel == 'Y') {
                $transactions = $query->orderByRaw('transaction.cdate desc')
                    ->select(
                        'transaction.id',
                        DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                        'master.id as master_id',
                        'master.name as master_name',
                        'dist.id as dist_id',
                        'dist.name as dist_name',
                        'accounts.id as account_id',
                        'accounts.type as account_type',
                        'accounts.name as account_name',
                        'product.carrier',
                        'product.name as product_name',
                        'transaction.product_id',
                        'transaction.denom',
                        'denom.name as denom_name',
                        'transaction.rtr_month',
                        'transaction.collection_amt',
                        'transaction.fee',
                        'transaction.pm_fee',
                        'transaction.action',
                        'transaction.api',
                        'transaction.sim',
                        'transaction.esn',
                        'transaction.npa',
                        'transaction.phone',
                        'transaction.pin',
                        'accounts.loc_id',
                        'accounts.outlet_id',
                        'accounts.state as loc_state',
                        DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                        'transaction.dc',
                        'transaction.dp',
                        'transaction.status',
                        'transaction.portstatus',
                        DB::raw('case when (transaction.note like \'%[EX-%\' ) then \'Connection Refused\' else transaction.note end as note'),
                        'transaction.note2',
                        'transaction.created_by',
                        'transaction.cdate',
                        'transaction.mdate'
                    )
                    ->get();

                Excel::create('transactions', function($excel) use($transactions) {

                    ini_set('memory_limit', '2048M');

                    $excel->sheet('reports', function($sheet) use($transactions) {


                        $login_user_account_type = Auth::user()->account_type;

                        $data = [];
                        foreach ($transactions as $o) {
                            $sim_obj = null;
                            $esn_obj = null;
                            if (in_array($o->action, ['Activation', 'Port-In'])) {
                                if (!empty($o->sim)) {
                                    $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
                                }

                                if (!empty($o->esn)) {
                                    $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();
                                }
                            }

                            $is_byos = '';
                            if(!empty($sim_obj)){
                                $is_byos = $sim_obj->is_byos;
                            }

                            $row = [
                                'Tx.ID' => $o->id,
                                'Type' => $o->type,
                                'Master.ID' => $o->master_id,
                                'Master.Name' => $o->master_name,
                                'Distributor.ID' => $o->dist_id,
                                'Distributor.Name' => $o->dist_name,
                                'Account.ID' => $o->account_id,
                                'Account.Name' => $o->account_name,
                                'Carrier' => $o->carrier,
                                'Product' => $o->product_name,
                                'Denom($)' => $o->denom,
                                'Denom.Name' => $o->denom_name,
                                'RTR.M' => $o->rtr_month,
                                'Total($)' => $o->collection_amt,
                                'Vendor.Fee($)' => $o->fee + $o->pm_fee,
                                'Action' => $o->action,
                                'API.Activated?' => $o->api == 'Y' ? 'YES' : '-',
                                'SIM' => $o->sim,
                                'SIM.Type' => empty($sim_obj) ? '' : $sim_obj->type,
                                'SIM.Is.BYOS' => $is_byos != 'Y' ? 'F' : 'T',
                                'SIM.Supplier.Name' => !empty($sim_obj->supplier) ? $sim_obj->supplier : 'BYOS',
                                'R.M' => $o->seq,
                                'ESN' => $o->esn,
                                'ESN.Model' => empty($esn_obj) ? '' : $esn_obj->supplier_model,
                                'ESN.Supplier.Name' => !empty($esn_obj->supplier_model) ? $esn_obj->supplier_model : 'BYOD',
                                'NPA' => $o->npa,
                                'Phone/PIN' => $o->action == 'PIN' ? $o->pin : $o->phone
                            ];

                            if (in_array($login_user_account_type, ['L'])) {
                                $row['LOC.ID'] = $o->loc_id;
                                $row['Outlet.ID'] = $o->outlet_id;
                                $row['LOC.State'] = $o->loc_state;
                                $row['LOC.Address'] = $o->loc_address;
                                $row['Dealer.Code'] = $o->dc;
                                $row['Dealer.PWD'] = $o->dp;
                             }

                            $row['status'] = $o->status_name();
                            $row['note'] = $o->note;
                            $row['note2'] = $o->note2;
                            $row['User.ID'] = $o->created_by;
                            $row['Created.At'] = $o->last_updated;

                            $data[] = $row;

                        }

                        $sheet->fromArray($data);

                    });

                })->export('xlsx');

            }

            $collection_amt = $query->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt, -transaction.collection_amt)"));
            $fee = $query->sum(DB::raw("if(transaction.type = 'S', transaction.fee + transaction.pm_fee, -(transaction.fee + transaction.pm_fee))"));
            $total_count = $query->count();

            $transactions = $query->orderByRaw('transaction.cdate desc')
                ->select(
                    'transaction.id',
                    DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                    'master.id as master_id',
                    'master.name as master_name',
                    'dist.id as dist_id',
                    'dist.name as dist_name',
                    'accounts.id as account_id',
                    'accounts.type as account_type',
                    'accounts.name as account_name',
                    'product.carrier',
                    'product.name as product_name',
                    'transaction.product_id',
                    'transaction.denom',
                    'transaction.rtr_month',
                    'transaction.collection_amt',
                    'transaction.fee',
                    'transaction.pm_fee',
                    'transaction.action',
                    'transaction.api',
                    'transaction.vendor_code',
                    'transaction.sim',
                    'transaction.esn',
                    'transaction.npa',
                    'transaction.phone',
                    'transaction.pin',
                    'accounts.loc_id',
                    'accounts.outlet_id',
                    'accounts.state as loc_state',
                    DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                    'transaction.dc',
                    'transaction.dp',
                    'transaction.status',
                    'transaction.portstatus',
                    'transaction.pref_pin',
                    DB::raw('case when (transaction.note like \'%[EX-%\' ) then \'Connection Refused\' else transaction.note end as note'),
                    'transaction.note2',
                    'transaction.created_by',
                    'transaction.cdate',
                    'transaction.mdate',
                    'denom.name as denom_name'
                )
                ->paginate(20);


            $carriers = Carrier::query();
            if (!Permission::can($request->path(), 'non-at&t product')) {
                $carriers->where('name', 'AT&T');
            }

            $carriers = $carriers->get();

            $api_vendors = Vendor::query()->where('status', '=', 'A')->get();

            return view('admin.reports.transaction', [
                'transactions' => $transactions,
                'sdate' => $sdate->format('Y-m-d HH:00:00'),
                'edate' => $edate->format('Y-m-d HH:00:00'),
                'quick' => $request->quick,
                'carrier' => $request->carrier,
                'phones' => $request->phones,
                'action' => $request->action,
                'status' => $request->status,
                'sims' => $request->sims,
                'user_id' => $request->user_id,
                'account_id' => $request->account_id,
                'account_ids' => $request->account_ids,
                'account_name' => $request->account_name,
                'esns' => $request->esns,
                'id' => $request->id,
                'sim_type' => $request->sim_type,
                'seq' => $request->seq,
                'collection_amt' => $collection_amt,
                'fee' => $fee,
                'total_count' => $total_count,
                'carriers' => $carriers,
                'product' => $request->product,
                'api_vendor' => $request->api_vendor,
                'api_vendors' => $api_vendors,
                'sales_type' => $request->sales_type,
                'note' => $request->note,
                'denomination' => $request->denomination
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function detail(Request $request) {
        try {
            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $detail = Transaction::select(
                    'transaction.*',
                    'accounts.loc_id',
                    'accounts.outlet_id',
                    'accounts.state as loc_state')
                ->join("accounts", 'transaction.account_id', 'accounts.id')
                ->where('transaction.id', $request->id)
                ->first();

            if (empty($detail)) {
                return response()->json([
                    'msg' => 'Invalid Tx.ID provided'
                ]);
            }

            $detail->type_name = $detail->type == 'S' ? 'Sales' : 'Void';
            $detail->product = $detail->product();
            $detail->status_name = $detail->status_name();
            $detail->loc_id = $detail->loc_id;
            $detail->loc_state = $detail->loc_state;
            $detail->outlet_id = $detail->outlet_id;
            $detail->afcode    = $detail->afcode;
            //$detail->can_edit = Auth::user()->account_type == 'L' || !in_array($detail->status, ['C', 'F']);
            $detail->can_edit = Auth::user()->account_type == 'L' && (($detail->api != 'Y' && $detail->status != 'V' && $detail->status != 'C') || (in_array($detail->action, ['Activation', 'Port-In', 'RTR', 'PIN']) && in_array($detail->status, ['I', 'F', 'Q', 'R']) && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system'])));

            return response()->json([
                'msg' => '',
                'data' => $detail
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function action_required(Request $request) {
        try {

            $detail = Transaction::find($request->id);

            ### Delete Spiff using Trans ID
            SpiffTrans::where('trans_id', $detail->id)->where('type', 'S')->delete();

            ### Delete extra spiff and sim charge, sim rebate
            $sim_obj = StockSim::where('sim_serial', $detail->sim)->where('product', $detail->product_id)->first();
            if (!empty($sim_obj)) {
                Promotion::where('trans_id', $detail->id)->delete();
            }

            ### Delete Rebate using Trans ID
            RebateTrans::where('trans_id', $detail->id)->delete();

            ### Delete RTR Queue using Trans ID
            RTRQueue::where('trans_id', $detail->id)->delete();

            ### Update status to
            $detail->status = 'R';
            $detail->modified_by = 'admin';
            $detail->mdate = Carbon::now();
            $detail->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_boom(Request $request) {
        $v = Validator::make($request->all(), [
            'id' => 'required',
            'phone' => 'nullable|regex:/\d{10}$/',
            'note' => 'required'
        ], [
            'phone.required_if' => 'Phone number is required',
            'note.required_if' => 'Note is required'
        ]);

        if ($v->fails()) {
            $msg = '';
            foreach ($v->messages()->toArray() as $k => $v) {
                $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
            }

            return response()->json([
                'msg' => $msg
            ]);
        }

        $detail = Transaction::find($request->id);
        if (empty($detail)) {
            return response()->json([
                'msg' => 'Invalid Tx.ID provided'
            ]);
        }

        $detail->phone = $request->phone;
        $detail->note = $request->note;
        $detail->modified_by = Auth::user()->user_id;
        $detail->mdate = Carbon::now();
        $detail->save();

        return response()->json([
            'msg' => ''
        ]);

    }

    public function update_note2(Request $request) {
        
        $detail = Transaction::find($request->id);
        if (empty($detail)) {
            return response()->json([
                'msg' => 'Invalid Tx.ID provided'
            ]);
        }

        $detail->note2 = $request->note2;
        $detail->modified_by = Auth::user()->user_id;
        $detail->mdate = Carbon::now();
        $detail->save();

        return response()->json([
            'msg' => ''
        ]);

    }

    public function update(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'phone' => 'nullable|regex:/\d{10}$/|required_if:status,C',
                'status' => 'required',
                'note' => 'required_if:status,F,R'
            ], [
                'phone.required_if' => 'Phone number is required when marking as completed',
                'note.required_if' => 'Note is required when marking as failed / action required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $detail = Transaction::find($request->id);
            if (empty($detail)) {
                return response()->json([
                    'msg' => 'Invalid Tx.ID provided'
                ]);
            }

            $old_sim = $detail->sim;
            $old_esn = $detail->esn;
            $old_phone = $detail->phone;
            $old_status = $detail->status;
            $old_note = $detail->note;
            $old_note2 = $detail->note2;

//            if ($old_status != 'C' && $detail->status == 'C') {
//                if (in_array($detail->product_id, ['WBMRA', 'WBMBA', 'WBMPA', 'WBMPOA'])) {
//                    return response()->json([
//                        'msg' => 'Please User RETRY Button!'
//                    ]);
//                    die();
//                }
//            }

            $detail->phone = $request->phone;
            $detail->sim = $request->sim;
            $detail->esn = $request->esn;
            $detail->status = $request->status;
            $detail->note = $request->note;
            $detail->note2 = $request->note2;
            $detail->modified_by = Auth::user()->user_id;
            $detail->mdate = Carbon::now();
            $detail->save();

            $log = new TransactionLog;
            $log->transaction_id = $detail->id;
            $log->old_phone = $old_phone;
            $log->old_sim = $old_sim;
            $log->old_esn = $old_esn;
            $log->old_status = $old_status;
            $log->old_note = $old_note;
            $log->old_note2 = $old_note2;
            $log->new_phone = $detail->phone;
            $log->new_sim = $detail->sim;
            $log->new_esn = $detail->esn;
            $log->new_status = $detail->status;
            $log->new_note = $detail->note;
            $log->new_note2 = $detail->note2;
            $log->created_by = Auth::user()->user_id;
            $log->cdate = Carbon::now();
            $log->save();

            if ($old_status != 'C' && $detail->status == 'C') {
                if (in_array($detail->action, ['Activation', 'Port-In'])) {
                    $account = Account::find($detail->account_id);
                    $vendor_denom = VendorDenom::where('vendor_code', $detail->vendor_code)
                        ->where('product_id', $detail->product_id)
                        ->where('denom_id', $detail->denom_id)
                        ->where('status', 'A')
                        ->first();

                    if (empty($vendor_denom)) {
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Empty Vendor Denom issue $', $detail->denom);
                    }

                    ### Update Sim status
                    StockSim::where('sim_serial', $detail->sim)
                      ->update([
                        'used_trans_id' => $detail->id,
                        'used_date'     => $detail->created_at,
                        'status'        => 'U'
                      ]);

                    if (!empty($account) && !empty($vendor_denom)) {
                        if (in_array($detail->product_id, ['WATTA'])) {

                            $ret = SpiffProcessor::give_spiff($detail->account_id, $detail->product_id, $detail->denom, 1, $detail->phone, $detail->id, $detail->created_by, 1, null, $detail->sim, $detail->esn, $detail->denom_id);

                            if (!empty($ret['error_code'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            $sim_obj = StockSim::where('sim_serial', $detail->sim)->where('product', $detail->product_id)->first();
                            if (!empty($sim_obj)) {
                                ### Pay extra spiff and sim charge, sim rebate
                                Promotion::create_by_order($sim_obj, $account, $detail->id);
                            }

                            ### rebate ###
                            if (!empty($detail->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $detail->product_id, $detail->denom, 1, 1, $detail->phone_type, $detail->sim, $detail->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                $ret = RebateProcessor::give_rebate($rebate_type, $detail->account_id, $detail->product_id, $detail->denom, 1, $detail->phone, $detail->denom - $spiff_amt, $detail->id, $detail->created_by, 1, $detail->esn, $detail->denom_id);
                                if (!empty($ret['error_code'])) {
                                    ### send message only ###
                                    $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                }
                            }

                            $ret = RTRProcessor::applyRTR(
                                1,
                                isset($sim_type) ? $sim_type : '',
                                $detail->id,
                                'Carrier',
                                $detail->phone,
                                $detail->product_id,
                                $detail->vendor_code,
                                $vendor_denom->rtr_pid,
                                $vendor_denom->denom,
                                $detail->created_by,
                                false,
                                null,
                                1,
                                $vendor_denom->fee,
                                $detail->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                            }

                            if ($detail->rtr_month > 1) {
                                if($detail->product_id == 'WATTA'){
                                    $rtr_product_id = 'WATTR';
                                }else{
                                    $rtr_product_id = $detail->product_id;
                                }
                                $error_msg = RTRProcessor::applyRTR(
                                    $detail->rtr_month,
                                    isset($sim_type) ? $sim_type : '',
                                    $detail->id,
                                    'House',
                                    $detail->phone,
                                    $rtr_product_id,
                                    $vendor_denom->vendor_code,
                                    $vendor_denom->rtr_pid,
                                    $vendor_denom->denom,
                                    $detail->created_by,
                                    true,
                                    null,
                                    2,
                                    $vendor_denom->fee
                                );

                                if (!empty($error_msg)) {
                                    $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                    $msg .= ' - vendor : ' . $detail->vendor_code . '<br/>';
                                    $msg .= ' - product : ' . $detail->product_id . '<br/>';
                                    $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                                    $msg .= ' - error : ' . $error_msg;
                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ATT Activation - applyRTR remaining month failed', $msg);
                                }
                            }

                        } else if (in_array($detail->product_id, ['WGENA', 'WGENOA', 'WGENTA', 'WGENTOA'])) {

                            ### Pay extra spiff and sim charge, sim rebate
                            $sim_obj = StockSim::where('sim_serial', $detail->sim)->where('product', $detail->product_id)->first();
                            $esn_obj = StockESN::where('esn', $detail->esn)->where('product', $detail->product_id)->first();

                            ### Update ESN status
                            if (!empty($esn_obj)) {
                                $esn_obj->status = 'U';
                                $esn_obj->esn_charge = null;
                                $esn_obj->esn_rebate = null;
                                $esn_obj->update();
                            }

                            $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                              $detail->product_id, $detail->denom, 'S', $detail->account_id, $sim_obj, $esn_obj, []
                            );

                            $pay_activation_fee = true;
                            if (!empty($special_spiffs)) {
                                foreach ($special_spiffs as $s) {
                                    if (in_array($s['special_id'], [295, 296, 297, 298, 299])) {
                                        $pay_activation_fee = false;
                                        break;
                                    }
                                }
                            }

//                            if ($pay_activation_fee) {
//                                $account = Account::find($detail->account_id);
//                                ### Pay GEN Activation FEE ###
//                                GenFee::pay_fee($detail->account_id, 'A', $detail->id, $account);
//                            }

                            ### 1st spiff for port-in ###
                            $ret = SpiffProcessor::give_spiff($detail->account_id, $detail->product_id, $detail->denom, 1, $detail->phone, $detail->id, $detail->created_by, 1, null, $detail->sim, $detail->esn, $detail->denom_id);
                            if (!empty($ret['error_code'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            $account = \App\Model\Account::find($detail->account_id);
                            \App\Model\Promotion::create_by_order($sim_obj, $account, $detail->id);

                            ### rebate ###
                            if (!empty($detail->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $detail->product_id, $detail->denom, 1, 1, $detail->phone_type, $detail->sim, $detail->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                $ret = RebateProcessor::give_rebate($rebate_type, $detail->account_id, $detail->product_id, $detail->denom, 1, $detail->phone, $detail->denom * $detail->rtr_month - $spiff_amt, $detail->id, $detail->created_by, 1, $detail->esn, $detail->denom_id);
                                if (!empty($ret['error_code'])) {
                                    ### send message only ###
                                    $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                }
                            }

                            $ret = RTRProcessor::applyRTR(
                                1,
                                '',
                                $detail->id,
                                'Carrier',
                                $detail->phone,
                                $detail->product_id,
                                $detail->vendor_code,
                                '',
                                $detail->denom,
                                'system',
                                false,
                                null,
                                1,
                                $detail->fee,
                                $detail->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                            }
                        } else if (in_array($detail->product_id, ['WLYCA'])) {

                            ### 1st spiff for port-in ###
                            $ret = SpiffProcessor::give_spiff($detail->account_id, $detail->product_id, $detail->denom, 1, $detail->phone, $detail->id, $detail->created_by, 1, null, $detail->sim, $detail->esn, $detail->denom_id);
                            if (!empty($ret['error_code'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][FreeUP][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            ### Pay extra spiff and sim charge, sim rebate
                            $sim_obj = StockSim::where('product', $detail->product_id)->where('sim_serial', $detail->sim)->first();
                            $account = \App\Model\Account::find($detail->account_id);
                            \App\Model\Promotion::create_by_order($sim_obj, $account, $detail->id);

                            ### rebate ###
                            if (!empty($detail->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $detail->product_id, $detail->denom, 1, 1, $detail->phone_type, $detail->sim, $detail->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $esn_obj = StockESN::where('product', $detail->product_id)->where('esn', $detail->esn)->first();
                                if (!empty($esn_obj)) {
                                    $esn_obj->esn_charge = null;
                                    $esn_obj->esn_rebate = null;
                                    $esn_obj->status = 'U';
                                    $esn_obj->update();

                                    $mapping = StockMapping::where('product', $detail->product_id)->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->first();
                                    if (!empty($mapping)) {
                                        $mapping->status = 'U';
                                        $mapping->update();
                                    }
                                }
                            }

                            if($detail->product_id == 'WLYCA'){
                                $rtr_product_id = 'WLYCAN';
                            }else{
                                $rtr_product_id = $detail->product_id;
                            }
                            $ret = RTRProcessor::applyRTR(
                                1,
                                '',
                                $detail->id,
                                'Carrier',
                                $detail->phone,
                                $rtr_product_id,
                                $detail->vendor_code,
                                '',
                                $detail->denom,
                                'system',
                                false,
                                null,
                                1,
                                $detail->fee,
                                $detail->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][Lyca][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                            }

                            if ($detail->rtr_month > 1) {
                                $error_msg = RTRProcessor::applyRTR(
                                    $detail->rtr_month,
                                    isset($sim_type) ? $sim_type : '',
                                    $detail->id,
                                    'House',
                                    $detail->phone,
                                    $detail->product_id,
                                    $vendor_denom->vendor_code,
                                    $vendor_denom->rtr_pid,
                                    $vendor_denom->denom,
                                    $detail->created_by,
                                    true,
                                    null,
                                    2,
                                    $vendor_denom->fee
                                );

                                if (!empty($error_msg)) {
                                    $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                    $msg .= ' - vendor : ' . $detail->vendor_code . '<br/>';
                                    $msg .= ' - product : ' . $detail->product_id . '<br/>';
                                    $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                                    $msg .= ' - error : ' . $error_msg;
                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ATT Activation - applyRTR remaining month failed', $msg);
                                }
                            }
                        } else if ( in_array($detail->product_id, ['WBMBA']) ) {

                            ### 1st spiff for port-in ###
                            $ret = SpiffProcessor::give_spiff($detail->account_id, $detail->product_id, $detail->denom, 1, $detail->phone, $detail->id, $detail->created_by, 1, null, $detail->sim, $detail->esn, $detail->denom_id);
                            if (!empty($ret['error_code'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            ### Pay extra spiff and sim charge, sim rebate
                            $sim_obj = StockSim::where('product', $detail->product_id)->where('sim_serial', $detail->sim)->first();
                            $account = \App\Model\Account::find($detail->account_id);
                            \App\Model\Promotion::create_by_order($sim_obj, $account, $detail->id);

                            ### rebate ###
                            if (!empty($detail->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $detail->product_id, $detail->denom, 1, 1, $detail->phone_type, $detail->sim, $detail->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $esn_obj = StockESN::where('product', $detail->product_id)->where('esn', $detail->esn)->first();
                                if (!empty($esn_obj)) {
                                    $esn_obj->esn_charge = null;
                                    $esn_obj->esn_rebate = null;
                                    $esn_obj->status = 'U';
                                    $esn_obj->update();

                                    $mapping = StockMapping::where('product', $detail->product_id)->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->first();
                                    if (!empty($mapping)) {
                                        $mapping->status = 'U';
                                        $mapping->update();
                                    }
                                }
                            }


                            $ret = RTRProcessor::applyRTR(
                                1,
                                '',
                                $detail->id,
                                'Carrier',
                                $detail->phone,
                                $detail->product_id,
                                $detail->vendor_code,
                                '',
                                $detail->denom,
                                'system',
                                false,
                                null,
                                1,
                                $detail->fee,
                                $detail->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                            }

                            if ($detail->rtr_month > 1) {
                                if($detail->product_id == 'WBMBA'){
                                    $rtr_product_id = 'WBMBAR';
                                }else{
                                    $rtr_product_id = $detail->product_id;
                                }
                                $error_msg = RTRProcessor::applyRTR(
                                    $detail->rtr_month,
                                    isset($sim_type) ? $sim_type : '',
                                    $detail->id,
                                    'House',
                                    $detail->phone,
                                    $rtr_product_id,
                                    $vendor_denom->vendor_code,
                                    $vendor_denom->rtr_pid,
                                    $vendor_denom->denom,
                                    $detail->created_by,
                                    true,
                                    null,
                                    2,
                                    $vendor_denom->fee
                                );

                                if (!empty($error_msg)) {
                                    $msg = ' - trans ID : ' . $detail->id . '<br/>';
                                    $msg .= ' - vendor : ' . $detail->vendor_code . '<br/>';
                                    $msg .= ' - product : ' . $detail->product_id . '<br/>';
                                    $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                                    $msg .= ' - error : ' . $error_msg;
                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
                                }
                            }

                        }

                    }
                }

                $msg = "Transaction " . $detail->id . " is now successfully completed with new phone # " . $detail->phone .  "! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $detail->id . "'>here</a> to see result!";
                event(new TransactionStatusUpdated($detail, $msg));
            }

            if (!in_array($old_status, ['R', 'F']) && in_array($detail->status, ['R', 'F'])) {
                $msg = "Transaction " . $detail->id . " has some issue! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $detail->id . "'>here</a> to see detail info!";
                event(new TransactionStatusUpdated($detail, $msg));
            }

            if ($old_status != 'V' && $detail->status == 'V') {
                ### void transaction ###
                $ret = VoidProcessor::void($detail->id);
                if (!empty($ret['error_code'])) {
                    $msg = "Failed to process void\n";
                    $msg .= " - Tx.ID: " . $detail->id . "\n";
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Void Processing Failed', $msg);
                }
            }

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            Helper::log($ex->getMessage() . ' [' . $ex->getCode() . ']' . $ex->getTraceAsString());

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function void_transaction(Request $request) {

        $detail = Transaction::find($request->id);
        if (empty($detail)) {
            return response()->json([
              'msg' => 'Invalid Tx.ID provided'
            ]);
        }

        $void_tran = Transaction::where('type', 'V')->where('status', 'C')->where('orig_id', $request->id)->first();

        if (!empty($void_tran)) {
            return response()->json([
                'msg' => 'The Tx.ID already voided at [' . $void_tran->cdate . ']'
            ]);
        }

        ### void transaction ###
        $ret = VoidProcessor::void($detail->id);
        if (!empty($ret['error_code'])) {
            $msg = "Failed to process void\n";
            $msg .= " - Tx.ID: " . $detail->id . "\n";
            Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] Void Processing Failed', $msg);
        }

        return response()->json([
          'msg' => ''
        ]);
    }

    public function retry(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                return back()->withInput()->withErrors($v);
            }

            $trans = Transaction::find($request->id);
            if (empty($trans)) {
                return back()->withInput()->withErrors([
                    'exception' => 'Invalid transaction ID provided'
                ]);
            }

            if ($trans->product_id == 'WMLL') {

                if ($trans->status != 'F') {
                    return back()->withInput()->withErrors([
                        'exception' => 'Only failed H2O Multi-Line can be retried'
                    ]);
                }

                if (!in_array($trans->action,  ['Activation', 'Port-In'])) {
                    return back()->withInput()->withErrors([
                        'exception' => 'Only failed H2O Multi-Line activation / port-in can be retried'
                    ]);
                }

                ### prepare transaction records ###
                $user = Auth::user();
                $account = Account::find($user->account_id);
                if (empty($account)) {
                    return back()->withErrors([
                        'exception' => 'Session expired. Please login again'
                    ]);
                }

                $ret = h2o::rotateDealerCode($account);
                Helper::log('### rotateDealerCode result ###', $ret);

                if (!empty($ret['msg'])) {
                    return back()->withErrors([
                        'exception' => $ret['msg']
                    ]);
                }

                $dc = $ret['dc'];
                $dp = $ret['dp'];

                if (empty($dc) || empty($dp)) {
                    return back()->withErrors([
                        'exception' => 'Your account does not have dealer code information!'
                    ]);
                }

                $cid = $trans->id . 'T' . rand(1, 100);

                if ($trans->action == 'Activation') {
                    $ret = h2o::activateGSMSim($dc, $dp, $cid, 'W30', $trans->sim, $trans->npa, $trans->zip);
                } else {
                    $ret = h2o::createMDNPort($cid, 'W30', $trans->account_no, $trans->account_pin,
                        $trans->address1 . ' ' . $trans->address2, $trans->city, $trans->state, $trans->zip, $trans->first_name . ' ' . $trans->last_name,
                        $trans->email, $trans->call_back_phone, $dc, $dp,
                        $trans->esn, $trans->sim, $request->ip(), $trans->phone, $trans->current_carrier, $trans->carrier_contract,
                        $require_portability_check = false
                    );
                }

                if (!empty($ret['error_msg'])) {
                    $trans->status = 'F';
                    $trans->note = $ret['error_msg'] . '[' . $ret['error_code'] . ']';
                    $trans->mdate = Carbon::now();
                    $trans->modified_by = $user->user_id;
                    $trans->api = 'Y';
                    $trans->save();

                    return back()->withErrors([
                        'exception' => $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                    ])->withInput();
                }

                $trans->status = $trans->action == 'Activation' ? 'C' : 'Q';
                $trans->phone = $ret['min'];
                $trans->vendor_tx_id = $ret['serial'];
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                $sim = H2OSim::find($trans->sim);
                $sim->used_trans_id = $trans->id;
                $sim->used_date = Carbon::now();
                $sim->status = 'U';
                $sim->save();

            } else {
                if ($trans->status == 'R' && $trans->action == 'Port-In') {
                    $trans->status = 'Q';
                    $trans->update();
                }
            }

            return back()->withInput();

        } catch (\Exception $ex) {
            return back()->withInput()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function batchLookup(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'category' => 'required|in:MDN,SIM,ESN,ACT',
                'batch_lines' => 'required'
            ]);

            if ($v->fails()) {

                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                $this->output_error($msg);
            }

            $lines = trim($request->batch_lines);
            if (empty($lines)) {
                $this->output_error('Please enter batch items to lookup');
            }

            $item_array = explode(PHP_EOL, $lines);

            $data = [];
            $data2 = [];

            $login_user_account_type = Auth::user()->account_type;

            foreach ($item_array as $item) {
                $item = trim($item);

                $query = Transaction::join('product', 'transaction.product_id', 'product.id')
                    ->join("accounts", 'transaction.account_id', 'accounts.id')
                    ->join("accounts as master", "accounts.master_id", "master.id")
                    ->Leftjoin("accounts as dist", function($join) {
                        $join->on('accounts.parent_id', 'dist.id')
                            ->where('dist.type', 'D');
                    })->where("transaction.status", "C");

                if (!empty($request->sdate_batch)) {
                    $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) >= ?', [$request->sdate_batch]);
                }
                if (!empty($request->edate_batch)) {
                    $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) <= ?', [$request->edate_batch]);
                }
                if (!empty($request->carrier_batch)) {
                    $query = $query->where('product.carrier', $request->carrier_batch);
                }
                if (!empty($request->api_vendor_batch)) {
                    $query = $query->where('transaction.vendor_code', $request->api_vendor_batch);
                }
                if (!empty($request->action_batch)) {
                    switch ($request->action_batch) {
                        case 'Activation,Port-In':
                            $query = $query->whereIn('transaction.action', ['Activation', 'Port-In']);
                            break;
                        case 'RTR,PIN':
                            $query = $query->whereIn('transaction.action', ['RTR', 'PIN']);
                            break;
                        default:
                            $query = $query->where('transaction.action', $request->action_batch);
                            break;
                    }
                }

                switch ($request->category) {
                    case 'MDN':
                        $query = $query->where('transaction.phone', $item);
                        $sim = '';
                        $esn = '';
                        $mdn = $item;
                        $act = '';
                        break;
                    case 'SIM':
                        $query = $query->where('transaction.sim', $item);
                        $sim = $item;
                        $esn = '';
                        $mdn = '';
                        $act = '';
                        break;
                    case 'ESN':
                        $query = $query->where('transaction.esn', $item);
                        $sim = '';
                        $esn = $item;
                        $mdn = '';
                        $act = '';
                        break;
                    case 'ACT':
                        $query = $query->where('transaction.account_id', $item);
                        $sim = '';
                        $esn = '';
                        $mdn = '';
                        $act = $item;
                        break;
                    default:
                        throw new \Exception('Please select batch item category to lookup');
                }

                $rs = $query->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
                    ->select(
                        'transaction.id',
                        DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                        'master.id as master_id',
                        'master.name as master_name',
                        'dist.id as dist_id',
                        'dist.name as dist_name',
                        'accounts.id as account_id',
                        'accounts.type as account_type',
                        'accounts.name as account_name',
                        'product.carrier',
                        'product.name as product_name',
                        'transaction.product_id',
                        'transaction.denom',
                        'transaction.rtr_month',
                        'transaction.collection_amt',
                        'transaction.fee',
                        'transaction.pm_fee',
                        'transaction.action',
                        'transaction.api',
                        'transaction.sim',
                        'transaction.esn',
                        'transaction.npa',
                        'transaction.phone',
                        'transaction.pin',
                        'accounts.loc_id',
                        'accounts.outlet_id',
                        'accounts.state as loc_state',
                        DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                        'transaction.dc',
                        'transaction.dp',
                        'transaction.status',
                        'transaction.note',
                        'transaction.created_by',
                        'transaction.cdate',
                        'transaction.mdate',
                        'transaction.vendor_code'
                    )->get();

                if (!empty($rs) && count($rs) > 0) {
                    foreach ($rs as $r) {
                        $o = new \stdClass();
                        $o->item = $item;

                        $o->id = $r->id;
                        $o->result = 'Matched';
                        $o->type = $r->type;
                        $o->master_id = $r->master_id;
                        $o->master_name = $r->master_name;
                        $o->dist_id = $r->dist_id;
                        $o->dist_name = $r->dist_name;
                        $o->account_id = $r->account_id;
                        $o->account_name = $r->account_name;
                        $o->carrier = $r->carrier;
                        $o->product_name = $r->product_name;
                        $o->denom = $r->denom;
                        $o->rtr_month = $r->rtr_month;
                        $o->collection_amt = $r->collection_amt;
                        $o->fee = $r->fee;
                        $o->pm_fee = $r->pm_fee;
                        $o->action = $r->action;
                        $o->api = $r->api;
                        $o->vendor_code = $r->vendor_code;
                        $o->sim = $r->sim;
                        $o->sim_type_name = $r->sim_type_name;
                        $o->seq = $r->seq;
                        $o->esn = $r->esn;
                        $o->npa = $r->npa;
                        $o->pin = $r->pin;
                        $o->phone = $r->phone;
                        $o->loc_id = $r->loc_id;
                        $o->outlet_id = $r->outlet_id;
                        $o->loc_state = $r->loc_state;
                        $o->loc_address = $r->loc_address;
                        $o->dc = $r->dc;
                        $o->dp = $r->dp;
                        $o->status_name = $r->status_name();
                        $o->note = $r->note;
                        $o->created_by = $r->created_by;
                        $o->last_updated = $r->last_updated;

                        $data[] = $o;
                    }
                } else {
                    $o = new \stdClass();
                    $o->item = $item;

                    $o->id = '';
                    $o->result = 'Not Found';
                    $o->type = '';
                    $o->master_id = '';
                    $o->master_name = '';
                    $o->dist_id = '';
                    $o->dist_name = '';
                    $o->account_id = $act;
                    $o->account_name = '';
                    $o->carrier = '';
                    $o->product_name = '';
                    $o->denom = '';
                    $o->rtr_month = '';
                    $o->collection_amt = '';
                    $o->fee = '';
                    $o->pm_fee = '';
                    $o->action = '';
                    $o->api = '';
                    $o->vendor_code = '';
                    $o->sim = $sim;
                    $o->sim_type_name = '';
                    $o->seq = '';
                    $o->esn = $esn;
                    $o->npa = '';
                    $o->pin = '';
                    $o->phone = $mdn;
                    $o->loc_id = '';
                    $o->outlet_id = '';
                    $o->loc_state = '';
                    $o->loc_address = '';
                    $o->dc = '';
                    $o->dp = '';
                    $o->status_name = '';
                    $o->note = '';
                    $o->created_by = '';
                    $o->last_updated = '';

                    $data[] = $o;
                }
            }

            if ($request->spp_only != 'N') {
                $query2 = Transaction::join('product', 'transaction.product_id', 'product.id')
                    ->join("accounts", 'transaction.account_id', 'accounts.id')
                    ->join("accounts as master", "accounts.master_id", "master.id")
                    ->Leftjoin("accounts as dist", function ($join) {
                        $join->on('accounts.parent_id', 'dist.id')
                            ->where('dist.type', 'D');
                    })->where("transaction.status", "C");

                if (!empty($request->sdate_batch)) {
                    $query2 = $query2->whereRaw('ifnull(transaction.mdate, transaction.cdate) >= ?', [$request->sdate_batch]);
                }
                if (!empty($request->edate_batch)) {
                    $query2 = $query2->whereRaw('ifnull(transaction.mdate, transaction.cdate) <= ?', [$request->edate_batch]);
                }
                if (!empty($request->carrier_batch)) {
                    $query2 = $query2->where('product.carrier', $request->carrier_batch);
                }
                if (!empty($request->api_vendor_batch)) {
                    $query2 = $query2->where('transaction.vendor_code', $request->api_vendor_batch);
                }
                if (!empty($request->action_batch)) {
                    switch ($request->action_batch) {
                        case 'Activation,Port-In':
                            $query2 = $query2->whereIn('transaction.action', ['Activation', 'Port-In']);
                            break;
                        case 'RTR,PIN':
                            $query2 = $query2->whereIn('transaction.action', ['RTR', 'PIN']);
                            break;
                        default:
                            $query2 = $query2->where('transaction.action', $request->action_batch);
                            break;
                    }
                }

                $temp = (implode(",", $item_array));
                $temp = str_replace("\r", '', $temp);
                $temp = "'" . str_replace(",", "','", $temp) . "'";

                switch ($request->category) {
                    case 'MDN':
                        $query2 = $query2->whereRaw("transaction.phone not in (" . $temp . ")");
                        break;
                    case 'SIM':
                        $query2 = $query2->whereRaw("transaction.sim not in (" . $temp . ")");
                        break;
                    case 'ESN':
                        $query2 = $query2->whereRaw("transaction.esn not in (" . $temp . ")");
                        break;
                    case 'ACT':
                        $query2 = $query2->whereRaw("transaction.account_id not in (" . $temp . ")");
                        break;
                    default:
                        throw new \Exception('Please select batch item category to lookup');
                }

                $rs2 = $query2->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
                    ->select(
                        'transaction.id',
                        DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
                        'master.id as master_id',
                        'master.name as master_name',
                        'dist.id as dist_id',
                        'dist.name as dist_name',
                        'accounts.id as account_id',
                        'accounts.type as account_type',
                        'accounts.name as account_name',
                        'product.carrier',
                        'product.name as product_name',
                        'transaction.product_id',
                        'transaction.denom',
                        'transaction.rtr_month',
                        'transaction.collection_amt',
                        'transaction.fee',
                        'transaction.pm_fee',
                        'transaction.action',
                        'transaction.api',
                        'transaction.sim',
                        'transaction.esn',
                        'transaction.npa',
                        'transaction.phone',
                        'transaction.pin',
                        'accounts.loc_id',
                        'accounts.outlet_id',
                        'accounts.state as loc_state',
                        DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                        'transaction.dc',
                        'transaction.dp',
                        'transaction.status',
                        'transaction.note',
                        'transaction.created_by',
                        'transaction.cdate',
                        'transaction.mdate',
                        'transaction.vendor_code'
                    )->get();

                if (!empty($rs2) && count($rs2) > 0) {
                    foreach ($rs2 as $r) {
                        $o2 = new \stdClass();
                        $o2->item = '';
                        $o2->id = $r->id;
                        $o2->result = 'SPP Only';
                        $o2->type = $r->type;
                        $o2->master_id = $r->master_id;
                        $o2->master_name = $r->master_name;
                        $o2->dist_id = $r->dist_id;
                        $o2->dist_name = $r->dist_name;
                        $o2->account_id = $r->account_id;
                        $o2->account_name = $r->account_name;
                        $o2->carrier = $r->carrier;
                        $o2->product_name = $r->product_name;
                        $o2->denom = $r->denom;
                        $o2->rtr_month = $r->rtr_month;
                        $o2->collection_amt = $r->collection_amt;
                        $o2->fee = $r->fee;
                        $o2->pm_fee = $r->pm_fee;
                        $o2->action = $r->action;
                        $o2->api = $r->api;
                        $o2->vendor_code = $r->vendor_code;
                        $o2->sim = $r->sim;
                        $o2->sim_type_name = $r->sim_type_name;
                        $o2->seq = $r->seq;
                        $o2->esn = $r->esn;
                        $o2->npa = $r->npa;
                        $o2->pin = $r->pin;
                        $o2->phone = $r->phone;
                        $o2->loc_id = $r->loc_id;
                        $o2->outlet_id = $r->outlet_id;
                        $o2->loc_state = $r->loc_state;
                        $o2->loc_address = $r->loc_address;
                        $o2->dc = $r->dc;
                        $o2->dp = $r->dp;
                        $o2->status_name = $r->status_name();
                        $o2->note = $r->note;
                        $o2->created_by = $r->created_by;
                        $o2->last_updated = $r->last_updated;
                        $data2[] = $o2;
                    }
                }
                $data3 = array_merge($data, $data2);
            } else {
                $data3 = $data;
            }

            Excel::create('batch_lookup_transaction', function($excel) use($data3, $login_user_account_type, $request) {
                $excel->sheet('reports', function($sheet) use($data3, $login_user_account_type, $request) {
                    $reports = [];
                    foreach ($data3 as $o) {
                        $sim_obj = StockSim::where('sim_serial', $o->sim)->first();
                        $is_byos = '';
                        if (count((array)$sim_obj)){
                            $is_byos = $sim_obj->is_byos;
                        }
                        $row = [
                            'Lookup.Item' => $o->item,
                            'Category' => $request->category,
                            'Result'    => $o->result,
                            'Tx.ID' => $o->id,
                            'Type' => $o->type,
                            'Master.ID' => $o->master_id,
                            'Master.Name' => $o->master_name,
                            'Distributor.ID' => $o->dist_id,
                            'Distributor.Name' => $o->dist_name,
                            'Account.ID' => $o->account_id,
                            'Account.Name' => $o->account_name,
                            'Carrier' => $o->carrier,
                            'Product' => $o->product_name,
                            'Denom($)' => $o->denom,
                            'RTR.M' => $o->rtr_month,
                            'Total($)' => $o->collection_amt,
                            'Vendor.Fee($)' => $o->fee + $o->pm_fee,
                            'Action' => $o->action,
                            'API.Activated?' => $o->api == 'Y' ? 'YES' : '-',
                            'Vendor.Code' => $o->vendor_code,
                            'SIM' => $o->sim,
                            'SIM.Type' => $o->sim_type_name,
                            'SIM.Is.BYOS' => $is_byos != 'Y' ? 'F' : 'T',
                            'R.M' => $o->seq,
                            'ESN' => $o->esn,
                            'NPA' => $o->npa,
                            'Phone/PIN' => $o->action == 'PIN' ? $o->pin : $o->phone
                        ];
                        if (in_array($login_user_account_type, ['L'])) {
                            $row['LOC.ID'] = $o->loc_id;
                            $row['Outlet.ID'] = $o->outlet_id;
                            $row['LOC.State'] = $o->loc_state;
                            $row['LOC.Address'] = $o->loc_address;
                            $row['Dealer.Code'] = $o->dc;
                            $row['Dealer.PWD'] = $o->dp;
                        }
                        $row['status'] = $o->status_name;
                        $row['note'] = $o->note;
                        $row['User.ID'] = $o->created_by;
                        $row['Created.At'] = $o->last_updated;
                        $reports[] = $row;
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        } catch (\Exception $ex) {
            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString());
        }
    }

    public function rtrque($trans_id) {
        $trans = Transaction::find($trans_id);

        if (!empty($trans) && $trans->status == 'C') {
            if ($trans->action == 'Activation' || $trans->action == 'Port-In') {
                $ret = \App\Lib\RTRProcessor::applyRTR(
                    1,
                    '',
                    $trans->id,
                    'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $trans->vendor_code,
                    '',
                    $trans->denom,
                    'cstore',
                    false,
                    null,
                    1,
                    $trans->fee,
                    $trans->rtr_month
                );
            }
        }

        return redirect('/admin/reports/transaction');
    }

    private function output_error($msg) {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.myApp.showError(\"" . str_replace("\"", "'", $msg) . "\");";
        echo "</script>";
        exit;
    }

    private function output_success() {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.close_modal();";
        echo "</script>";
        exit;
    }

    public function detail_sub($id) {

        $detail = Transaction::find($id);

        if (empty($detail)) {
            return redirect('/admin/reports/transaction')->withErrors([
                'exception' => 'Invalid transaction ID provided'
            ]);
        }

        if ($detail->status != 'R') {
            return redirect('/admin/reports/transaction')->withErrors([
                'exception' => 'Only action required transaction can be re-submitted'
            ]);
        }

        $sim_length = 0;
        $esn_length = 16;
        $carrier = $detail->carrier();
        switch ($carrier) {
            case 'Lyca':
                $sim_length = 19;
                break;
            default:
                $sim_length = 20;
                break;
        }

        $denom = Denom::where('product_id', $detail->product_id)
            ->where('denom', $detail->denom)
            ->first();
        if (empty($denom)) {
            return redirect('/admin/reports/transaction')->withErrors([
                'exception' => 'Invalid denomination found'
            ]);
        }

        $products = Product::where('carrier', $carrier)
            ->where('status', 'A')
            ->get();

        return view('admin.reports.transaction-detail', [
            'detail' => $detail,
            'products' => $products,
            'sim_length' => $sim_length,
            'esn_length' => $esn_length,
            'denom_id' => $denom->id
        ]);
    }

    public function update_sub(Request $request, $id) {
        try {

            if ($request->zip == '' || strlen($request->zip) != 5) {
                return back()->withErrors([
                    'exception' => 'Valid Zip Code is required!'
                ])->withInput();
            }

            if ($request->sim == '' && $request->esn == '') {
                return back()->withErrors([
                    'exception' => 'Either SIM or ESN/IMEI is required'
                ])->withInput();
            }

            $detail = Transaction::find($request->id);
            if (empty($detail) || $id != $detail->id) {
                return back()->withErrors([
                    'exception' => 'Please enter valid transaction ID'
                ])->withInput();
            }

            if ($detail->carrier() == 'H2O' && empty($request->current_carrier)) {
                return back()->withErrors([
                    'exception' => 'Port-In From is required'
                ])->withInput();
            }

            $detail->sim = $request->sim;
            $detail->esn = $request->esn;

            if ($detail->action == 'Port-In') {
                $detail->phone = $request->number_to_port;
                $detail->current_carrier = $request->current_carrier;
                $detail->account_no = $request->account_no;
                $detail->account_pin = $request->account_pin;
                $detail->zip = $request->zip;
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return back()->withErrors([
                    'exception' => 'Please select product first'
                ])->withInput();
            }

            $detail->product_id = $denom->product_id;
            $detail->denom = $denom->denom;

            Helper::log('### carrier ##', $detail->carrier());
            Helper::log('### action ##', $detail->action);

            if ($detail->action == 'Port-In') {

                $account = Account::find($detail->account_id);
                if (empty($account)) {
                    return back()->withErrors([
                        'exception' => 'Account is empty. You should not see this message'
                    ])->withInput();
                }

                $product = Product::find($denom->product_id);
                if (empty($product)) {
                    return back()->withErrors([
                        'exception' => 'Please select product first'
                    ])->withInput();
                }

                $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                    ->where('product_id', $product->id)
                    ->where('denom_id', $denom->id)
                    ->where('status', 'A')
                    ->first();

                if (empty($vendor_denom)) {
                    return back()->withErrors([
                        'exception' => 'Incomplete vendor configuration'
                    ])->withInput();
                }

                if ($detail->carrier() == 'H2O') {
                    $ret = h2o::rotateDealerCode($account);
                    Helper::log('### rotateDealerCode result ###', $ret);

                    if (!empty($ret['msg'])) {
                        return back()->withErrors([
                            'exception' => $ret['msg']
                        ]);
                    }

                    $dc = $ret['dc'];
                    $dp = $ret['dp'];

                    if (empty($dc) || empty($dp)) {
                        return back()->withErrors([
                            'exception' => 'Your account does not have dealer code information!'
                        ]);
                    }

                    ### update port-in ###
                    $ret = h2o::updateMDNPort(
                        time(), $vendor_denom->act_pid, $detail->account_no, $detail->account_pin,
                        $detail->address1 . ' ' . $detail->address2, $detail->city, $detail->state, $detail->zip, $detail->first_name . ' ' . $detail->last_name,
                        $detail->email, $detail->call_back_phone, $dc, $dp,
                        $detail->esn, $detail->sim, $request->ip(), $detail->phone, $detail->current_carrier, $detail->carrier_contract
                    );

                    if (!empty($ret['error_code'])) {
                        return back()->withErrors([
                            'exception' => 'Updating Port-In failed: ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                        ])->withInput();
                    }

                } else if ($detail->carrier() == 'AT&T') {
                    // UpdatePort($pid, $req_number, $mdn, $first_name, $last_name, $street_number, $street_name, $city, $state, $zip, $account_no, $pin)
                    $ret = gss::UpdatePort($vendor_denom->act_pid, $detail->vendor_tx_id, $detail->phone, $detail->first_name, $detail->last_name, $detail->address1, $detail->address2, $detail->city, $detail->state, $detail->zip, $detail->account_no, $detail->account_pin);

                    if (!empty($ret['error_code'])) {
                        return back()->withErrors([
                            'exception' => 'Updating Port-In failed: ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                        ])->withInput();
                    }

                } else if ($detail->carrier() == 'Lyca') {
//                  LycaModifyPortIn($reference_no, $product_id, $sim, $mdn, $account_no, $account_psw, $zip)
                    //$ret = emida2::LycaModifyPortIn($detail->vendor_tx_id, $vendor_denom->act_pid, $detail->sim, $detail->phone, $detail->account_no, $detail->account_pin, $detail->zip);
                    $ret = emida2::LycaModifyPortIn($detail->vendor_tx_id, $vendor_denom->act_pid, $detail->sim, $detail->phone, $detail->account_no, $detail->account_pin, $detail->zip);

                    if (!empty($ret['error_code'])) {
                        return back()->withErrors([
                            'exception' => 'Updating Port-In failed: ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                        ])->withInput();
                    }
                } else if ($detail->carrier() == 'Boom Mobile') {

                    if($detail->product_id == 'WBMBA' || $detail->product_id == 'WBMBAR'){
                        $network = 'BLUE';
                    }elseif ($detail->product_id == 'WBMRA' || $detail->product_id == 'WBMRAR'){
                        $network = 'RED';
                    }elseif ($detail->product_id == 'WBMPA' || $detail->product_id == 'WBMPAR' || $detail->product_id == 'WBMPOA'){
                        // Pink to Purple (later)
                        $network = 'PINK';
                    }

                    $ret = boom::updatePendingPort($network, $detail->phone, $detail->first_name, $detail->last_name, $detail->address1, $detail->address2, $detail->city, $detail->state, $detail->zip, $detail->email, $detail->account_no, $detail->account_pin, $detail->current_carrier);

                    if (!empty($ret['error_code'])) {
                        return back()->withErrors([
                            'exception' => 'Updating Port-In failed: ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']'
                        ])->withInput();
                    }
                }

            } else {
                $ret = Helper::send_mail(env('ACT_NOTIFY_EMAIL'), '[' . $detail->carrier() . '] Action Required Activation Request Updated', ' - Tx.ID: ' . $detail->id);
                if (!empty($ret)) {
                    Helper::log('### SEND MAIL ERROR ###', [
                        'msg' => $ret
                    ]);
                }

                $msg = "Transaction  " . $detail->id . " has been re-submitted. Click <a style='color:yellow;' href='/admin/reports/transaction?id=" . $detail->id . "'>here</a> to see detail info!'";
                event(new TransactionStatusUpdatedRoot($detail, $msg));
            }

            if ($detail->action == 'Port-In') {
                if ($detail->carrier() == 'H2O') {
                    $detail->carrier_contract = $request->carrier_contract;
                }

                $detail->status = 'Q';
            } else {
                $detail->status = 'N';
            }

            $detail->modified_by = Auth::user()->user_id;
            $detail->mdate = Carbon::now();
            $detail->save();

            return redirect('/admin/reports/transaction')->with([
                'success' => 'Y'
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' (' . $ex->getCode() . ')'
            ])->withInput();
        }
    }
}