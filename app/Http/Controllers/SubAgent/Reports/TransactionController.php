<?php

namespace App\Http\Controllers\SubAgent\Reports;

use App\Http\Controllers\Controller;
use App\Lib\boom;
use App\Lib\emida;
use App\Lib\emida2;
use App\Lib\lyca;
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
class TransactionController extends Controller
{

    public function show(Request $request) {
        try {
            $sdate = Carbon::today();
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
                })
                ->Leftjoin("denomination as denom", function($join) {
                    $join->on('transaction.denom_id', 'denom.id');
                    $join->on('transaction.product_id', 'denom.product_id');
                });

            if (!empty($sdate) && empty($request->id)) {
                $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) >= ?', [$sdate]);
            }

            if (!empty($edate) && empty($request->id)) {
                $query = $query->whereRaw('ifnull(transaction.mdate, transaction.cdate) <= ?', [$edate]);
            }

            $query = $query->where('transaction.account_id', Auth::user()->account_id);

            if (!empty($request->carrier)) {
                $query = $query->where('product.carrier', $request->carrier);
            }

            if (!empty($request->phone)) {
                $query = $query->where('transaction.phone', 'like', '%' . $request->phone . '%');
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

            if (!empty($request->sim)) {
                $query = $query->where('transaction.sim', 'like', '%' . $request->sim . '%');
            }

            if (!empty($request->esn)) {
                $query = $query->where('transaction.esn', 'like', '%' . $request->esn . '%');
            }

            if (!empty($request->user_id)) {
                $query = $query->where('transaction.created_by', 'like', '%' . $request->user_id . '%');
            }

            if (!empty($request->id)) {
                $query = $query->where('transaction.id', $request->id);
            }

            if (!empty($request->sales_type)) {
                $query = $query->where('transaction.type' ,$request->sales_type);
            }

            if (!empty($request->note)) {
                $query = $query->whereRaw("lower(transaction.note) like '%" . strtolower($request->note) . "%' or lower(transaction.note2) like '%" . strtolower($request->note) . "%'");
            }

            if ($request->excel == 'Y') {
                $transactions = $query->orderByRaw('transaction.cdate desc')
                    ->select(
                        'transaction.id',
                        \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
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
                        'transaction.net_revenue',
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
                        \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                        'transaction.dc',
                        'transaction.dp',
                        'transaction.status',
                        \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
                        'transaction.note2',
                        'transaction.created_by',
                        'transaction.cdate',
                        'transaction.mdate',
                        'denom.name as denom_name',
                        \DB::raw("f_get_sim_type_name(transaction.id) as sim_type_name")
                    )
                    ->get();
                Excel::create('transactions', function($excel) use($transactions) {

                    $excel->sheet('reports', function($sheet) use($transactions) {

                        $data = [];
                        foreach ($transactions as $o) {
                            $row = [
                                'Tx.ID' => $o->id,
                                'Type' => $o->type,
                                'Account.ID' => $o->account_id,
                                'Account.Name' => $o->account_name,
                                'Carrier' => $o->carrier,
                                'Product' => $o->product_name,
                                'Denom($)' => $o->denom,
                                'Denom.Name' => $o->denom_name,
                                'RTR.M' => $o->rtr_month,
                                'Total($)' => $o->collection_amt,
                                'Vendor.Fee($)' => $o->fee + $o->pm_fee,
                                'Payable.($)' => $o->net_revenue,
                                'Action' => $o->action,
                                'SIM' => empty($o->sim) ? '' : ($o->carrier == 'GEN Mobile' ? substr($o->sim, 0, 18) . 'XX' : $o->sim),
                                'ESN' => empty($o->esn) ? '' : ($o->carrier == 'GEN Mobile' ? substr($o->esn, 0, strlen($o->esn) - 2) . 'XX' : $o->esn),
                                'NPA' => $o->npa,
                                'Phone' => $o->action == 'PIN' ? $o->pin : $o->phone
                            ];

                            $row['status'] = $o->status_name();
                            $row['note'] = $o->note;
                            $row['note2'] = $o->note2;
                            $row['User.ID'] = $o->created_by;
                            $row['Created.At'] = $o->cdate;

                            $data[] = $row;

                        }

                        $sheet->fromArray($data);

                    });

                })->export('xlsx');

            }

            $collection_amt = $query->sum(\DB::raw("if(transaction.type = 'S', transaction.collection_amt, -transaction.collection_amt)"));
            $net_revenue = $query->sum(\DB::raw("if(transaction.type = 'S', transaction.net_revenue, -transaction.net_revenue)"));
            $fee = $query->sum(\DB::raw("if(transaction.type = 'S', transaction.fee + transaction.pm_fee, -(transaction.fee + transaction.pm_fee))"));

            $transactions = $query->orderByRaw('transaction.cdate desc')
                ->select(
                    'transaction.id',
                    \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
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
                    'transaction.net_revenue',
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
                    \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                    'transaction.dc',
                    'transaction.dp',
                    'transaction.status',
                    \DB::raw('case when (transaction.note like \'%[EX-%\' ) then \'Connection Refused\' else transaction.note end as note'),
                    'transaction.note2',
                    'transaction.created_by',
                    'transaction.cdate',
                    'transaction.mdate',
                    'denom.name as denom_name',
                    \DB::raw("f_get_sim_type_name(transaction.id) as sim_type")
                )->paginate(20);

            $carriers = Carrier::all();

            return view('sub-agent.reports.transaction', [
                'transactions' => $transactions,
                'sdate' => $sdate->format('Y-m-d'),
                'edate' => $edate->format('Y-m-d'),
                'carrier' => $request->carrier,
                'phone' => $request->phone,
                'action' => $request->action,
                'status' => $request->status,
                'sim' => $request->sim,
                'user_id' => $request->user_id,
                'esn' => $request->esn,
                'id' => $request->id,
                'collection_amt' => $collection_amt,
                'net_revenue' => $net_revenue,
                'fee' => $fee,
                'carriers' => $carriers,
                'sales_type' => $request->sales_type,
                'note' => $request->note
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function detail($id) {
        $detail = Transaction::find($id);
        if (empty($detail)) {
            return redirect('/sub-agent/reports/transaction')->withErrors([
                'exception' => 'Invalid transaction ID provided'
            ]);
        }

        if ($detail->status != 'R') {
            return redirect('/sub-agent/reports/transaction')->withErrors([
                'exception' => 'Only action required transaction can be re-submitted'
            ]);
        }

        if ($detail->account_id != Auth::user()->account_id) {
            return redirect('/sub-agent/reports/transaction')->withErrors([
                'exception' => 'You are not authorized to re-submit this transaction'
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
            return redirect('/sub-agent/reports/transaction')->withErrors([
                'exception' => 'Invalid denomination found'
            ]);
        }

        $products = Product::where('carrier', $carrier)
            ->where('status', 'A')
            ->get();

        return view('sub-agent.reports.transaction-detail', [
            'detail' => $detail,
            'products' => $products,
            'sim_length' => $sim_length,
            'esn_length' => $esn_length,
            'denom_id' => $denom->id
        ]);
    }

    public function update(Request $request, $id) {
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

                    if($detail->product_id == 'WBMBA'|| $detail->product_id == 'WBMBAR'){
                        $network = 'BLUE';
                    }elseif ($detail->product_id == 'WBMRA' || $detail->product_id == 'WBMRAR' ){
                        $network = 'RED';
                    }elseif ($detail->product_id == 'WBMPA' || $detail->product_id == 'WBMPOA' || $detail->product_id == 'WBMPAR' || $detail->product_id == 'WBMPOAR'){
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

            return redirect('/sub-agent/reports/transaction')->with([
                'success' => 'Y'
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' (' . $ex->getCode() . ')'
            ])->withInput();
        }
    }
}