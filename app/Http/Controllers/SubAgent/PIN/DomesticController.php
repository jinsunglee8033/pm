<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/15/17
 * Time: 2:59 PM
 */

namespace App\Http\Controllers\SubAgent\PIN;


use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\emida;
use App\Lib\emida2;
use App\Lib\epay;
use App\Lib\h2o_rtr;
use App\Lib\DollarPhone;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\reup;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\telestar;
use App\Lib\gss;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\Product;
use App\Model\SpiffTrans;
use App\Model\StockPin;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DomesticController extends Controller
{

    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);

        ### carriers ###
        //$carriers = Carrier::all();
        $carrier = $request->old('carrier', $request->get('carrier'));

        $carriers = DB::select("
            select distinct  
                c.carrier as name
            from rate_detail a 
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom d on b.product_id = d.product_id 
                    and b.id = d.denom_id
                    and c.vendor_code = d.vendor_code
                    and ifnull(d.pin_pid, '') != '' 
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'PIN'
            and c.status = 'A' 
            and b.status = 'A'
            and d.status = 'A'
            order by 1 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id
        ]);

        ### products ###
        $products = DB::select(" 
            select distinct  
                c.id as product_id,
                c.name as product_name
            from rate_detail a 
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom d on b.product_id = d.product_id 
                    and b.id = d.denom_id
                    and c.vendor_code = d.vendor_code
                    and ifnull(d.pin_pid, '') != ''
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'PIN'
            and c.status = 'A'
            and b.status = 'A'
            and d.status = 'A'
            and c.carrier = :carrier
            and c.id not in ('WATTA', 'WATTPVA', 'WFRUPA')
            order by 2 asc 
        ", [
            'rate_plan_id' => $account->rate_plan_id,
            'carrier' => $carrier
        ]);

        $product_id = $request->old('product_id', $request->get('product_id'));
        if (empty($product_id) && count($products) == 1) {
            $product_id = $products[0]->product_id;
        }

        ### denominations ###
        $denominations = DB::select("
            select 
                b.id as denom_id,
                b.denom,
                b.name
            from rate_detail a 
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom d on b.product_id = d.product_id 
                    and b.id = d.denom_id
                    and c.vendor_code = d.vendor_code
                    and ifnull(d.pin_pid, '') != ''
            where a.rate_plan_id = :rate_plan_id
            and c.id = :product_id
            and c.status = 'A'
            and b.status = 'A'
            and d.status = 'A'
            and a.action = 'PIN'
            order by 2 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id,
            'product_id' => $product_id
        ]);

        ### open denom ###
        $open_denom = 'N';
        if (count($denominations) == 1 ) {
            $request->denom_id = $denominations[0]->denom_id;
            
            if ($denominations[0]->denom == 0) {
                $open_denom = 'Y';
            }
        }

        $total = 0;
        $amt = 0;
        $sub_total = 0;
        $fee = 0;
        $qty = empty($request->qty) ? 1 : $request->qty;
        if (!empty($request->denom_id)) {
            $denom = Denom::find($request->denom_id);
            if (!empty($denom)) {
                $amt = $denom->denom;

                $product = Product::find($product_id);
                if (!empty($product)) {
                    $vendor_denom = VendorDenom::where('product_id', $product_id)
                        ->where('denom_id', $denom->id)
                        ->where('vendor_code', $product->vendor_code)
                        ->where('status', 'A')
                        ->first();
                    if (!empty($vendor_denom)) {
                        $fee = ($vendor_denom->fee + $vendor_denom->pm_fee);
                    }
                }

                $sub_total = $qty * $denom->denom;
                $total = $sub_total + $fee;
            }
        } else if (!empty($request->denom)) {
            $amt = $request->denom;

            $product = Product::find($product_id);
            if (!empty($product)) {
                $vendor_denom = VendorDenom::where('product_id', $product_id)
                    ->where('denom_id', $denominations[0]->denom_id)
                    ->where('vendor_code', $product->vendor_code)
                    ->where('status', 'A')
                    ->first();
                if (!empty($vendor_denom)) {
                    $fee = ($vendor_denom->fee + $vendor_denom->pm_fee);
                }
            }

            $sub_total = $qty * $request->denom;
            $total = $sub_total + $fee;
        }


        $query = Transaction::join('product', 'transaction.product_id', 'product.id');

        $transactions = $query->where('transaction.account_id', Auth::user()->account_id)
          ->whereIn('action', ['RTR', 'PIN'])
          ->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
          ->select(
            'transaction.id',
            \DB::raw("if(transaction.type = 'S', 'Sales', 'Void') as type"),
            'product.carrier',
            'product.name as product_name',
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
            'transaction.dc',
            'transaction.dp',
            'transaction.status',
            \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
            'transaction.created_by',
            'transaction.cdate',
            'transaction.mdate'
          )->limit(10)->get();

        return view('sub-agent.pin.domestic', [
            'products' => $products,
            'denominations' => $denominations,
            'product_id' => $product_id,
            'denom_id' => $request->denom_id,
            'qty' => $qty,
            'amt' => $amt,
            'sub_total' => $sub_total,
            'fee' => $fee,
            'total' => $total,
            'carriers' => $carriers,
            'carrier' => $carrier,
            'open_denom' => $open_denom,
            'denom' => $request->denom,
            'transactions' => $transactions
        ]);
    }

    public function process(Request $request) {

        $trans = null;

        try {

            if (Helper::is_login_as()) {
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Login as is not allowed to do pin purchase.'
                  ]
                ]);
            }

            if (Helper::is_login_as()) {
                return back()->withInput()->withErrors([
                    'exception' => 'We are sorry. Login as user is not allowed to make any transaction'
                ]);
            }

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                //'denom_id' => 'required',
                'qty' => 'required|numeric|min:1,max:12'
            ], [
                'product_id.required' => 'Please select product first',
                'denom_id.required' => 'Please select amount first'
            ]);

            if ($v->fails()) {
                return back()->withInput()->withErrors($v);
            }

            ### check product ###
            $product = Product::find($request->product_id);
            if (empty($product)) {
                return back()->withInput()->withErrors([
                    'exception' => 'Please select product first'
                ]);
            }

            if ($product->status != 'A') {
                return back()->withInput()->withErrors([
                    'exception' => 'Product is not active. Please contact our customer care.'
                ]);
            }

            ### check denomination ###
            if (empty($request->denom_id) && empty($request->denom)) {
                return back()->withInput()->withErrors([
                    'exception' => 'Please select or enter amount.'
                ]);
            }

            if (!empty($request->denom_id)) {
                $denom = Denom::find($request->denom_id);
                if (empty($denom)) {
                    return back()->withInput()->withErrors([
                        'exception' => 'Please select amount first'
                    ]);
                }

                $face_value = $denom->denom;
            } else {
                $denom = Denom::where('product_id', $request->product_id)
                    ->where('min_denom', '<=', $request->denom)
                    ->where('max_denom', '>=', $request->denom)
                    ->first();

                if (empty($denom)) {
                    return back()->withInput()->withErrors([
                        'exception' => 'Please enter amount first'
                    ]);
                }

                $face_value = $request->denom;
            }


            if ($denom->status != 'A') {
                return back()->withInput()->withErrors([
                    'exception' => '$' . number_format($face_value) . ' is not active. Please contact our customer care.'
                ]);
            }

            ### check vendor setup ###
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            if (empty($vendor_denom)) {
                return back()->withInput()->withErrors([
                    'exception' => '$' . number_format($face_value) . ' is not supported by the vendor [' . $product->vendor_code . ']'
                ]);
            }

            ### check sales limit ###
            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withInput()->withErrors([
                    'exception' => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            $fee = $vendor_denom->fee * $request->qty;
            $pm_fee = $vendor_denom->pm_fee * $request->qty;

            $collection_amt = $face_value * $request->qty;
            $net_revenue = 0;
            if ($collection_amt > 0) {
                $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true, 'PIN');
                if (!empty($ret['error_msg'])) {
                    return back()->withInput()->withErrors([
                        'exception' => $ret['error_msg']
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = $product->id;
            $trans->action = 'PIN';
            $trans->denom = $face_value;
            $trans->denom_id = $denom->id;
            $trans->phone = '';
            $trans->status = 'I';
            $trans->cdate = Carbon::now();
            $trans->created_by = $user->user_id;
            $trans->api = 'Y';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $request->qty;
            $trans->net_revenue = $net_revenue;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->vendor_code = $product->vendor_code;
            $trans->save();

            ### process vendor API - first month ###
            $vendor_tx_id = '';
            $pin = '';
            $carrier_trans_id = '';
            switch ($product->vendor_code) {
                case 'EMD':
                    $ret = emida2::pin($trans->id, $vendor_denom->pin_pid, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '';
                    $pin = isset($ret['pin']) ? $ret['pin'] : '';
                    break;
                case 'DLP':
                    $ret = DollarPhone::pin($trans->id, $vendor_denom->pin_pid, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    $pin = isset($ret['pin']) ? $ret['pin'] : '';
                    $carrier_trans_id = isset($ret['carrier_trans_id']) ? $ret['carrier_trans_id'] : '';
                    break;
                case 'GSS':
                    $ret = gss::pin($trans->id, $vendor_denom->pin_pid);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    $pin = isset($ret['pin']) ? $ret['pin'] : '';
                    break;
                case 'SPP':
                    \DB::beginTransaction();
                    try {
                        ### get pin from DB ###
                        $stock_pin = StockPin::where('product', $product->id)
                            ->where('status', 'A')
                            ->where('amount',$face_value)
                            ->orderBy('id', 'asc')
                            ->first();

                        if (empty($stock_pin)) {
                            $ret['error_code'] = -1;
                            $ret['error_msg'] = "PIN Not Exist";
                        } else {
                            $pin = $stock_pin->pin;
                            $vendor_tx_id = $trans->id;

                            ### update stock_pin ###
                            $stock_pin->status = 'S';
                            $stock_pin->used_trans_id = $vendor_tx_id;
                            $stock_pin->amount = $face_value;
                            $stock_pin->used_date = Carbon::now();
                            $stock_pin->save();
                            $ret['error_code'] = '';
                        }
                        \DB::commit();
                    } catch (\Exception $ex) {
                        \DB::rollBack();
                        return back()->withInput()->withErrors([
                            'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
                        ]);
                    }
                    break;
                default:
                    $ret = [
                        'error_code' => -1,
                        'error_msg' => 'Vendor [' . $product->vendor_code .'] is not supported yet!'
                    ];
                    break;
            }

            if ($ret['error_code'] != '') {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                return back()->withInput()->withErrors([
                    'exception' => $trans->note
                ]);
            }

            if (empty($vendor_tx_id)) {
                $trans->status = 'F';
                $trans->note = 'Unable to retrieve vendor Tx.ID';
                $trans->save();

                return back()->withInput()->withErrors([
                    'exception' => $trans->note
                ]);
            }

            if (empty($pin)) {
                $trans->status = 'F';
                $trans->note = 'Unable to retrieve PIN from vendor';
                $trans->save();

                return back()->withInput()->withErrors([
                    'exception' => $trans->note
                ]);
            }

            ### processor remaining month ###

            Helper::log('#### RTR Month ###', $trans->rtr_month);

            ### commission ###
            if ($collection_amt > 0) {
                $ret = CommissionProcessor::create($trans->id);
                if (!empty($ret['error_msg'])) {
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                    $msg .= ' - product : ' . $product->id . '<br/>';
                    $msg .= ' - denom : ' . $face_value . '<br/>';
                    $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - create commission failed', $msg);
                }
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
            if($product->vendor_code == 'DLP') {
                $trans->note = '[CarrierTransId] : ' . $carrier_trans_id;
            }
            $trans->pin = $pin;
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### give spiff ###

            ### refresh balance ###
            Helper::update_balance();


            Helper::log('### RIGHT BEFORE RETURN PIN ###', [
                'trans' => $trans
            ]);

            return back()->with([
                'success' => 'Y',
                'invoice_no' => $trans->id,
                'carrier' => $request->carrier,
                'product' => $product->name,
                'amount' => $trans->denom,
                'qty' => $trans->rtr_month,
                'pin' => trim($pin),
                'sub_total' => $trans->collection_amt,
                'fee' => $fee + $pm_fee,
                'total' => $collection_amt + $fee + $pm_fee
            ]);


        } catch (\Exception $ex) {
            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'Domestic RTR Failed', $msg);

            return back()->withInput()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}