<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/15/17
 * Time: 2:59 PM
 */

namespace App\Http\Controllers\SubAgent\RTR;


use App\Http\Controllers\Controller;
use App\Lib\boom;
use App\Lib\CommissionProcessor;
use App\Lib\emida;
use App\Lib\emida2;
use App\Lib\epay;
use App\Lib\h2o_rtr;
use App\Lib\DollarPhone;
use App\Lib\Helper;
use App\Lib\liberty;
use App\Lib\PaymentProcessor;
use App\Lib\reup;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\telestar;
use App\Lib\gss;
use App\Model\Account;
use App\Model\AccountFee;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\Product;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use App\Model\Vendor;
use App\Model\VendorDenom;
use App\Model\VendorFeeSetup;
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

        if ($carrier == 'GEN Mobile') {
            return redirect('/sub-agent/rtr/gen');
        }

        if ($carrier == 'Boom Mobile') {
            return redirect('/sub-agent/rtr/boom');
        }

        $carriers = DB::select("
            select distinct  
                c.carrier as name
            from rate_detail a 
                inner join denomination b on a.denom_id = b.id
                inner join product c on b.product_id = c.id
                inner join vendor_denom d on b.product_id = d.product_id 
                    and b.denom = d.denom
                    and c.vendor_code = d.vendor_code
                    and ifnull(d.rtr_pid, '') != '' 
            where a.rate_plan_id = :rate_plan_id
            and c.type in ('Wireless', 'INTL')
            and a.action = 'RTR'
            and c.status = 'A' 
            and b.status = 'A'
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
                    and b.denom = d.denom
                    and c.vendor_code = d.vendor_code
                    and ifnull(d.rtr_pid, '') != ''
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'RTR'
            and c.status = 'A'
            and b.status = 'A'
            and c.carrier = :carrier
            and c.id not in ('WATTA', 'WATTPVA', 'WFRUPA')
            order by 2 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id,
            'carrier' => $carrier
        ]);

        $product_id = $request->old('product_id', $request->get('product_id'));

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
                    and ifnull(d.rtr_pid, '') != ''
            where a.rate_plan_id = :rate_plan_id
            and a.action = 'RTR'
            and c.id = :product_id
            and c.status = 'A'
            and d.status = 'A'
            and b.status = 'A'
            order by 2 asc
        ", [
            'rate_plan_id' => $account->rate_plan_id,
            'product_id' => $product_id
        ]);

        ### open denom ###
        $open_denom = 'N';
        if (count($denominations) == 1 && $denominations[0]->denom == 0) {
            $open_denom = 'Y';
        }

        $total = 0;
        $amt = 0;
        $sub_total = 0;
        $fee = 0;
        $country_code = '';
        $vendor_country_code = '';

        if($request->carrier == 'Claro'){
            if(!empty($request->product_id)){
                $p = Product::where('id', $request->product_id)->first();
                $country_code = $p->country_code;

                $v = Vendor::where('code', $p->vendor_code)->where('status', 'A')->first();
                $vendor_country_code = $v->country_code;
            }
        }

        if (!empty($request->denom_id)) {

            $denom = Denom::find($request->denom_id);
            if (!empty($denom)) {
                $amt = $denom->denom;

                $product = Product::find($product_id);
                if (!empty($product)) {

                    if($product->acct_fee != 'Y') {

                        $vendor_denom = VendorDenom::where('product_id', $product_id)
                            ->where('denom_id', $denom->id)
                            ->where('vendor_code', $product->vendor_code)
                            ->where('status', 'A')
                            ->first();
                        if (!empty($vendor_denom)) {
                            $fee = $request->get('rtr_month', 1) * ($vendor_denom->fee + $vendor_denom->pm_fee);
                        }
                    }else{
                        if($account->master_id == $account->parent_id){
                            // No Distributor
                            $acc_fee = AccountFee::get_total_fee($product_id, $account->id, 'N');
                        }else{
                            // Yes Distributor
                            $acc_fee = AccountFee::get_total_fee($product_id, $account->id, 'Y');
                        }
                        $pm_fee = 0;
                        $fee = $request->get('rtr_month', 1) * ($acc_fee + $pm_fee);
                    }
                }

                $sub_total = $request->get('rtr_month', 1) * $denom->denom;
                $total = $sub_total + $fee;
            }
        } else if (!empty($request->denom)) {

            $amt = $request->denom;

            $product = Product::find($product_id);
            if (!empty($product)) {

                if($product->acct_fee != 'Y') {
                    $vendor_denom = VendorDenom::where('product_id', $product_id)
                        ->where('denom_id', $denominations[0]->denom_id)
                        ->where('vendor_code', $product->vendor_code)
                        ->where('status', 'A')
                        ->first();
                    if (!empty($vendor_denom)) {
                        $fee = $request->get('rtr_month', 1) * ($vendor_denom->fee + $vendor_denom->pm_fee);
                    }
                }else{
                    if($account->master_id == $account->parent_id){
                        // No Distributor
                        $acc_fee = AccountFee::get_total_fee($product_id, $account->id, 'N');
                    }else{
                        // Yes Distributor
                        $acc_fee = AccountFee::get_total_fee($product_id, $account->id, 'Y');
                    }
                    $pm_fee = 0;
                    $fee = $request->get('rtr_month', 1) * ($acc_fee + $pm_fee);
                }
            }

            $sub_total = $request->get('rtr_month', 1) * $request->denom;
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

        return view('sub-agent.rtr.domestic', [
            'products' => $products,
            'denominations' => $denominations,
            'product_id' => $product_id,
            'denom_id' => $request->denom_id,
            'rtr_month' => $request->rtr_month,
            'country_code' => !empty($country_code) ? $country_code : '',
            'vendor_country_code' => !empty($vendor_country_code) ? $vendor_country_code : '',
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
                    'msg'   => 'Login as is not allowed to do recharge.'
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
                'rtr_month' => 'required|numeric|min:1,max:12',
                'phone' => 'required|regex:/^\d{10}$/'
            ], [
                'product_id.required' => 'Please select product first',
                'denom_id.required' => 'Please select amount first',
                'phone.regex' => 'Please enter valid phone number. 10 digits only.'
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

            $face_value = 0;

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

            if (empty($vendor_denom) || $vendor_denom->status != 'A') {
                return back()->withInput()->withErrors([
                    'exception' => '$' . number_format($face_value) . ' is not supported by the vendor [' . $product->vendor_code . ']'
                ]);
            }

            if ($product->id == 'WBST') {
                if (empty($request->mdn_pin)) {
                    return back()->withInput()->withErrors([
                        'exception' => 'Please enter pin number.'
                    ]);
                }
            }

            if ($product->id == 'WXITN') {
                if (empty($request->zip)) {
                    return back()->withInput()->withErrors([
                        'exception' => 'Please enter ZIP.'
                    ]);
                }
            }

            ### check sales limit ###
            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return back()->withInput()->withErrors([
                    'exception' => 'Logged in user account is invalid. Please contact our customer care.'
                ]);
            }

            ### Processing Fee (Boost, Metro, Cricket) ###
            $ret = Product::where('id', $request->product_id)->where('acct_fee', 'Y')->first();
            if($ret){
                if($account->master_id == $account->parent_id){
                    // No Distributor
                    $processing_fee = AccountFee::get_total_fee($request->product_id, $account->id, 'N');
                }else{
                    // Yes Distributor
                    $processing_fee = AccountFee::get_total_fee($request->product_id, $account->id, 'Y');
                }

                $processing_fee = $processing_fee * $request->rtr_month;

                $fee = $processing_fee;
                $pm_fee = 0;
            }else{
                $fee = $vendor_denom->fee * $request->rtr_month;
                $pm_fee = $vendor_denom->pm_fee * $request->rtr_month;
            }

            $collection_amt = $face_value * $request->rtr_month;
            $net_revenue = 0;
            if ($collection_amt > 0) {
                $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true);
                if (!empty($ret['error_msg'])) {
                    return back()->withInput()->withErrors([
                        'exception' => $ret['error_msg']
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            ### International code ##
            $phone = $request->phone;
            if(!empty($product->country_code)){
                $v = Vendor::where('code', $product->vendor_code)->first();
                if(!empty($v->country_code)){
                    $phone = $product->country_code . $phone;
                }
            }

            ### Duplicate transaction check ###
            $now = Carbon::now();
            $gap = Carbon::now()->subSeconds(10);

            $ret = Transaction::where('account_id', '=', $account->id)
                ->where('product_id', '=', $product->id)
                ->where('denom_id', '=', $denom->id)
                ->where('phone', '=', $phone)
                ->where('status', '!=', 'F')
                ->where('cdate', '<=', $now)
                ->where('cdate', '>', $gap)
                ->count();

            if($ret > 0){
                return back()->withInput()->withErrors([
                    'exception' => 'You already have another transaction with same phone number'
                ]);
            }

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = $product->id;
            $trans->action = 'RTR';
            $trans->denom = $face_value;
            $trans->denom_id = $denom->id;
            $trans->phone = $phone;
            $trans->status = 'I';
            $trans->cdate = Carbon::now();
            $trans->created_by = $user->user_id;
            $trans->api = 'Y';
            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $request->rtr_month;
            $trans->net_revenue = $net_revenue;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->vendor_code = $product->vendor_code;
            $trans->pref_pin = $request->mdn_pin;

            // Keep ZIP when Xfinity RTR (10/12/2020)
            if(!empty($request->zip)){
                $trans->zip = $request->zip;
            }
            
            $trans->save();

            ### process vendor API - first month ###
            $vendor_tx_id = '';
            $carrier_trans_id = '';

            switch ($product->vendor_code) {
                case 'LOC': ### H2O ###
                    $ret = h2o_rtr::recharge($vendor_denom->rtr_pid, $trans->phone, $face_value, $trans->id);
                    $vendor_tx_id = isset($ret['sales_confirmation_id']) ? $ret['sales_confirmation_id'] : '';
                    break;
                case 'EPY':
                    $ret = epay::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '';
                    break;
                case 'EMD':
                    $ret = emida2::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $vendor_denom->fee);
                    $vendor_tx_id = isset($ret['vendor_tx_id']) ? $ret['vendor_tx_id'] : '' ;
                    break;
                case 'DLP':
                    ### Check for vendor fee setup (08/12/2020) ###
                    $vfs = VendorFeeSetup::where('vendor_code', $trans->vendor_code)
                        ->where('product_id', $trans->product_id)
                        ->where('amt_and_fee', 'Y')
                        ->first();
                    $amt_and_fee = !empty($vfs) ? 'Y' : 'N';

                    $mdn_pin = $product->id == 'WBST' ? $request->mdn_pin : '';

                    // For Xfinity Internet - phone|zip (10/02/2020)
                    $mdn = !empty($request->zip) ? $trans->phone ."|".$request->zip : $trans->phone;

                    $ret = DollarPhone::rtr($trans->id, $vendor_denom->rtr_pid, $mdn, $face_value,  $vendor_denom->fee, $mdn_pin, $amt_and_fee);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    $carrier_trans_id = isset($ret['carrier_trans_id']) ? $ret['carrier_trans_id'] : '';
                    break;
                case 'RUP':
                    $ret = reup::rtr($vendor_denom->rtr_pid, $trans->phone);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    break;
                case 'TST':
                    $ret = telestar::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value);
                    $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                    break;
                case 'LBT':
                    $ret = liberty::refillByLot($trans->phone, $vendor_denom->rtr_pid);
                    $result = $ret['result'];
                    $vendor_tx_id = ($result->StatusCode == '1') ? $result->mdn : '';
                    break;
                case 'GSS':
                    if ($product->id == 'WATTILD' || $product->id == 'WATTDATA') {
                        $tid = Helper::get_att_tid($account);
                        if (empty($tid)) {
                            return back()->withInput()->withErrors([
                                'exception' => 'Your account is not authorized to do AT&T. Please contact your distributor'
                            ]);
                        }
                        $ret = gss::UpgradeFeature($trans->id, $vendor_denom->rtr_pid, $trans->phone, $tid);
                        $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : 'NA';
                    } else {
                        if($product->id == 'WBST'){
                            $ret = gss::rtr_boost($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $request->mdn_pin);
                            $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                        }else {
                            // ATT_TID2 added for WATTR
                            $att_tid2 = !empty($account->att_tid2) ? $account->att_tid2 : '';
                            $ret = gss::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $face_value, $att_tid2);
                            $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';
                        }
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

            ### add rtr-q for first month just for show up ###
            $error_msg = RTRProcessor::applyRTR(
                1,
                'Refill',
                $trans->id,
                'Refill',
                $trans->phone,
                $trans->product_id,
                $vendor_denom->vendor_code,
                $vendor_denom->rtr_pid,
                $face_value,
                $user->user_id,
                false,
                null,
                1,
                $vendor_denom->fee,
                $trans->rtr_month
            );
            if (!empty($error_msg)) {
                $msg = ' - trans ID : ' . $trans->id . '<br/>';
                $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                $msg .= ' - product : ' . $product->id . '<br/>';
                $msg .= ' - denom : ' . $face_value . '<br/>';
                $msg .= ' - error : ' . $error_msg;
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - applyRTR 1st month failed', $msg);
            }

            ### processor remaining month ###

            Helper::log('#### RTR Month ###', $trans->rtr_month);

            if ($trans->rtr_month > 1) {
                $error_msg = RTRProcessor::applyRTR(
                    $trans->rtr_month,
                    'Refill' ,
                    $trans->id,
                    'Refill',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $face_value,
                    $user->user_id,
                    true,
                    null,
                    2,
                    $vendor_denom->fee
                );

                if (!empty($error_msg)) {
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                    $msg .= ' - product : ' . $product->id . '<br/>';
                    $msg .= ' - denom : ' . $face_value . '<br/>';
                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                    $msg .= ' - error : ' . $error_msg;
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - applyRTR remaining month failed', $msg);
                }
            }

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

            ### PAY Processing FEE (Boost, Metro, Cricket) ###
            $ret = Product::where('id', $request->product_id)->where('acct_fee', 'Y')->first();
            if($ret){
                AccountFee::pay_acct_fee($product->id, $trans->account_id, $trans->id, $trans->rtr_month);
            }

            ### mark as success ###
            $trans->status = 'C';
            $trans->vendor_tx_id = $vendor_tx_id;
            if($product->vendor_code == 'DLP') {
                $trans->note = '[CarrierTransId] : ' . $carrier_trans_id;
            }
            $trans->mdate = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            ### give spiff ###

            ### refresh balance ###
            Helper::update_balance();

            return back()->with([
                'success' => 'Y',
                'invoice_no' => $trans->id,
                'carrier' => $request->carrier,
                'product' => $product->name,
                'amount' => $trans->denom,
                'rtr_month' => $trans->rtr_month,
                'phone' => $trans->phone,
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