<?php
/**
 * Created by Royce
 * Date: 6/22/18
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Events\TransactionStatusUpdated;
use App\Lib\boom;
use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\liberty;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\gen;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\GenActivation;
use App\Model\GenFee;
use App\Model\LbtActivation;
use App\Model\PmModelSimLookup;
use App\Model\Product;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use App\Model\Promotion;
use App\Model\SpiffSetupSpecial;

use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;

use App\Model\Zip;
use Carbon\Carbon;
use function Couchbase\defaultDecoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BoomController
{
    public function show_blue(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
              'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_boom != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Boom Mobile activation. Please contact your distributor'
            ]);
        }

        $query = Transaction::join('product', 'transaction.product_id', 'product.id')
          ->join("accounts", 'transaction.account_id', 'accounts.id')
          ->join("accounts as master", "accounts.master_id", "master.id")
          ->leftjoin("stock_sim", function($join) {
              $join->on('transaction.sim', 'stock_sim.sim_serial')
                ->where('transaction.product_id', 'stock_sim.product');
          })
          ->Leftjoin("accounts as dist", function($join) {
              $join->on('accounts.parent_id', 'dist.id')
                ->where('dist.type', 'D');
          });

        $transactions = $query->where('transaction.account_id', Auth::user()->account_id)
          ->whereIn('product_id', ['WBMBA'])
          ->whereIn('action', ['Activation', 'Port-In'])
          ->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
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
            'product.id as product_id',
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
            'accounts.loc_id',
            'accounts.outlet_id',
            'accounts.state as loc_state',
            \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
            'transaction.dc',
            'transaction.dp',
            'transaction.status',
            \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
            'transaction.created_by',
            'transaction.cdate',
            'transaction.mdate',
            \DB::raw("case stock_sim.type when 'R' then 'Regular' when 'P' then 'Wallet' when 'B' then 'Bundle' when 'C' then 'Consignment' else '' end as sim_type")
          )->limit(10)->get();

        $states = State::all();

        return view('sub-agent.activate.boom-blue')->with([
            'transactions'  => $transactions,
            'states'        => $states,
            'account'       => $account
        ]);
    }

    public function show_red(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_boom != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Boom Mobile activation. Please contact your distributor'
            ]);
        }

        $query = Transaction::join('product', 'transaction.product_id', 'product.id')
            ->join("accounts", 'transaction.account_id', 'accounts.id')
            ->join("accounts as master", "accounts.master_id", "master.id")
            ->leftjoin("stock_sim", function($join) {
                $join->on('transaction.sim', 'stock_sim.sim_serial')
                    ->where('transaction.product_id', 'stock_sim.product');
            })
            ->Leftjoin("accounts as dist", function($join) {
                $join->on('accounts.parent_id', 'dist.id')
                    ->where('dist.type', 'D');
            });

        $transactions = $query->where('transaction.account_id', Auth::user()->account_id)
            ->whereIn('product_id', ['WBMRA'])
            ->whereIn('action', ['Activation', 'Port-In'])
            ->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
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
                'product.id as product_id',
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
                'accounts.loc_id',
                'accounts.outlet_id',
                'accounts.state as loc_state',
                \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                'transaction.dc',
                'transaction.dp',
                'transaction.status',
                \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
                'transaction.created_by',
                'transaction.cdate',
                'transaction.mdate',
                \DB::raw("case stock_sim.type when 'R' then 'Regular' when 'P' then 'Wallet' when 'B' then 'Bundle' when 'C' then 'Consignment' else '' end as sim_type")
            )->limit(10)->get();

        return view('sub-agent.activate.boom-red')->with([
            'transactions'  => $transactions,
            'account'       =>$account
        ]);
    }

    public function show_purple(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        if ($account->act_boom != 'Y') {
            return redirect('/sub-agent/error')->with([
                'error_msg' => 'Your account is not authorized to do Boom Mobile activation. Please contact your distributor'
            ]);
        }

        $query = Transaction::join('product', 'transaction.product_id', 'product.id')
            ->join("accounts", 'transaction.account_id', 'accounts.id')
            ->join("accounts as master", "accounts.master_id", "master.id")
            ->leftjoin("stock_sim", function($join) {
                $join->on('transaction.sim', 'stock_sim.sim_serial')
                    ->where('transaction.product_id', 'stock_sim.product');
            })
            ->Leftjoin("accounts as dist", function($join) {
                $join->on('accounts.parent_id', 'dist.id')
                    ->where('dist.type', 'D');
            });

        $transactions = $query->where('transaction.account_id', Auth::user()->account_id)
            ->whereIn('product_id', ['WBMPA', 'WBMPOA'])
            ->whereIn('action', ['Activation', 'Port-In'])
            ->orderByRaw('ifnull(transaction.mdate, transaction.cdate) desc')
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
                'product.id as product_id',
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
                'accounts.loc_id',
                'accounts.outlet_id',
                'accounts.state as loc_state',
                \DB::raw('concat(accounts.address1, " ", ifnull(accounts.address2, ""), ", ", accounts.city, " ", accounts.state, " ", accounts.zip) as loc_address'),
                'transaction.dc',
                'transaction.dp',
                'transaction.status',
                \DB::raw('case when transaction.note like \'%[EX-%\' then \'Connection Refused\' else transaction.note end as note'),
                'transaction.created_by',
                'transaction.cdate',
                'transaction.mdate',
                \DB::raw("case stock_sim.type when 'R' then 'Regular' when 'P' then 'Wallet' when 'B' then 'Bundle' when 'C' then 'Consignment' else '' end as sim_type")
            )->limit(10)->get();

        $states = State::all();

        return view('sub-agent.activate.boom-purple')->with([
            'transactions'  => $transactions,
            'states'        => $states,
            'account'       => $account
        ]);
    }

    public function get_portin_form_blue() {

        return view('sub-agent.activate.portin-form-boom-blue')->with([
            'product_id' => 'WBMBA'
        ]);
    }

    public function get_portin_form_red() {

        $states = State::all();
        $carriers = Carrier::all();

        return view('sub-agent.activate.portin-form-boom-red')->with([
            'states'        => $states,
            'carriers'      => $carriers,
            'product_id'    => 'WBMRA'
        ]);
    }

    public function get_portin_form_purple() {

        $states = State::all();

        return view('sub-agent.activate.portin-form-boom-purple')->with([
            'states'     => $states,
            'product_id' => 'WBMPA'
        ]);
    }

    public function sim_blue(Request $request) {
        try {

            $product_id = 'WBMBA';
            $p = Product::where('id', $product_id)->first();
            $account = Account::find(Auth::user()->account_id);

            $sim_obj = StockSIM::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            if (empty($sim_obj)) {
                $p = Product::where('id', 'WBMBA')->first();
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WBMBA', $p->carrier, $p->sim_group, $account->id, $account->name);
            }else{
                if ($sim_obj->status !== 'A') {
                    return response()->json([
                        'code' => '-2',
                        'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                    ]);
                }
                ### check owner path ###
                if (!empty($sim_obj->owner_id)) {
                    $owner = Account::where('id', $sim_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return response()->json([
                            'code' => '-2',
                            'msg' => 'SIM is not available. Not valid owner.'
                        ]);
                    }
                }
            }

            $mapping = StockMapping::where('product', $product_id)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();

            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Boom Blue activation is not ready.'
                ]);
            }
            $product_obj = Product::where('id', $product_id)->first();
            if ($product_obj->status !='A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Product is not Active.'
                ]);
            }


            $plans = Array();
            if (empty($sim_obj->amount)) {
                foreach ($denoms as $d) {
                    $v_denom = VendorDenom::where('product_id', $product_id)
                        ->where('vendor_code',$product_obj->vendor_code)
                        ->where('denom_id', $d->id)
                        ->where('status', 'A')
                        ->first();
                    if(!empty($v_denom->act_pid)) {
                        $plans[] = [
                            'denom_id' => $d->id,
                            'denom' => $d->denom,
                            'name' => $d->name
                        ];
                    }
                }
            } else {
                $ds = explode('|', $sim_obj->amount);
                foreach ($ds as $s) {
                    $denom_tmp = Denom::where('product_id', $product_id)->where('denom', $s)->where('status', 'A')->get();
                    foreach ($denom_tmp as $d) {
                        $plans[] = [
                            'denom_id' => $d->id,
                            'denom' => $d->denom,
                            'name' => $d->name
                        ];
                    }
                }
            }

            foreach ($plans as $p) {
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, $request->sim, null);

                $p['spiff'] = $ret_spiff['spiff_amt'];
            }

            $allowed_months = Helper::get_min_month($sim_obj, $account, 'boom_min_month');

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim'       => $sim_obj->sim_serial,
                    'meid'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim_obj->sub_carrier,
                    'product_id' => $product_id,
                    'plans'     => $plans,
                    'allowed_months' => $allowed_months,
                    'sim_charge' => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate' => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'sim_consignment_charge' => $sim_obj->type == 'C' ? (empty($sim_obj->charge_amount_r) ? 0 : $sim_obj->charge_amount_r) : 0
                ]
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function esn_valid_blue(Request $request) {
        try{

            $esn_obj = StockESN::where('esn', $request->meid)->where('product', 'WBMBA')->first();
            if (empty($esn_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WBMBA')->first();
                StockESN::upload_byod($request->meid, 'WBMBA', $p->carrier, $account->id, $account->name);
            }

            return response()->json([
                'code'  => '0',
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function commission_blue(Request $request) {
        try {
            $spiff = 0;
            $rebate = 0;
            $product_id = 'WBMBA';
            $p = Product::where('id', $product_id)->first();
            $special_spiffs = null;
            $denom   = Denom::find($request->denom_id);
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            // BYOD allow or not..?
            if($request->meid) {
                $esn_obj = StockESN::where('esn', $request->meid)->where('product', $product_id)->first();

                if (empty($esn_obj)) {
                    $account = Account::find(Auth::user()->account_id);
                    StockESN::upload_byod($request->meid, 'WBMBA', $p->carrier, $account->id, $account->name);
                }
            }else{
                $esn_obj ='';
            }

            if (!empty($denom)) {
                $account = Account::find(Auth::user()->account_id);
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'BoomBlue');
                $spiff = $ret_spiff['spiff_amt'];

                if ($account->rebates_eligibility == 'Y') {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                    $rebate = $ret_rebate['rebate_amt'];
                }
                if (empty($request->sim)) {
                    $rebate += $spiff;
                }
                ### Special Spiff
                $terms = array();
                if ($request->is_port_in == 'Y') {
                    $terms[] = 'Port';
                }
                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                    $product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj, $terms
                );
            }


            $spiff_labels = Array();
            $sim_label = '';
            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
            if($sim_obj) {
                if ($sim_obj->type == 'P') {
                    if ($spiff == 0) {
                        $sim_label = 'Credit Already Paid' . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = 'Already Paid';
                    } else {
                        $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                    }
                } else {
                    $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                    $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                }
            }else {
                $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
            }
            if ($extra_spiff > 0) {
                $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';
            }
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $sim_label .= ', ' . $ss['name'] . ' $' . number_format($ss['spiff'], 2);
                    $spiff_labels[] = '$ ' . number_format($ss['spiff'], 2) . ', ' . $ss['name'];
                }
            }

            // Regulatory Fee, instead of Vendor Fee //
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            $fee = empty($vendor_denom->fee) ? 0 : $vendor_denom->fee;
            $pm_fee = empty($vendor_denom->pm_fee) ? 0 : $vendor_denom->pm_fee;

            $activation_fee = $fee + $pm_fee;

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'spiff_labels'    => $spiff_labels,
                    'sim_charge'      => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'      => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'      => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'      => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate,
                    'activation_fee'  => $activation_fee
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function validate_mdn(Request $request) {

        $mdn        = $request->mdn;
        $network    = $request->network;
        if($network == 'BLUE'){
            $zip = $request->zip;
        }else{
            $zip = ' ';
        }
        $ret = Boom::validateMDN($mdn, $network, $zip);

        $error_code     = $ret['error_code'];
        $port_status    = $ret['port_status'];
        $port_desc      = $ret['port_desc'];

        if ($error_code != '') {
            return response()->json([
                'code'  => '-1',
                'msg'   => $ret['error_msg'] . ' [' . $error_code . ']'
            ]);
        }

        if ($port_status != 'ELIGIBLE') {
            return response()->json([
                'code' => '-2',
                'msg' => $port_desc
            ]);
        }
        return response()->json([
            'code'  => '0',
            'msg' => ''
        ]);
    }

    public function esn_valid_red(Request $request) {
        try {
            $product_id = ['WBMRA'];
            $esn = $request->esn;
            $ret = boom::deviceInquery($esn);
            $error_code = $ret['error_code'];
            if ($error_code != '') {
                return response()->json([
                    'code'  => '-1',
                    'msg'   => $ret['error_msg'] . ' [' . $error_code . ']'
                ]);
            }
            $esn_obj = StockESN::where('esn', $esn)->whereIn('product', $product_id)->first();
            if (empty($esn_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WBMRA')->first();
                StockESN::upload_byod($esn, 'WBMRA', $p->carrier, $account->id, $account->name);
            }
            $denoms = Denom::where('product_id', $product_id)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Boom Mobile activation is not ready.'
                ]);
            }
            $plans = Array();
            foreach ($denoms as $d) {
                $plans[] = [
                    'denom_id' => $d->id,
                    'denom' => $d->denom,
                    'name'  => $d->name
                ];
            }
            $account = Account::find(Auth::user()->account_id);
            foreach ($plans as $p) {
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, null, null);

                $p['spiff'] = $ret_spiff['spiff_amt'];
            }

            $allowed_months = Helper::get_min_month($esn_obj, $account, 'boom_min_month');

            return response()->json([
                'code'  => '0',
                'plans' => $plans,
                'allowed_months' => $allowed_months,
                'msg'   => ''
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function sim_red(Request $request) {
        try {

            $sim_obj = StockSIM::where('sim_serial', $request->sim)->where('sim_group', 'BoomRed')->first();

            if (empty($sim_obj)) {
                $account = Account::find(Auth::user()->account_id);
                $p = Product::where('id', 'WBMRA')->first();
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WBMRA', $p->carrier, $p->sim_group, $account->id, $account->name);
            }
            if ($sim_obj->status !== 'A') {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                ]);
            }
            return response()->json([
                'code'  => '0',
                'msg' => ''
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function sim_purple(Request $request) {
        try {

            $product_ids = ['WBMPA', 'WBMPOA'];
            $p = Product::where('id', 'WBMPA')->first();
            $account = Account::find(Auth::user()->account_id);

            $sim_obj = StockSIM::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            if (empty($sim_obj)) {
                $sim_obj = StockSim::upload_byos($request->sim, null, 'WBMPA', $p->carrier, $p->sim_group, $account->id, $account->name);
            }else{
                if ($sim_obj->status !== 'A') {
                    return response()->json([
                        'code' => '-2',
                        'msg' => 'SIM is not available, Verify the SIM number again or (could be already used SIM)'
                    ]);
                }
                ### check owner path ###
                if (!empty($sim_obj->owner_id)) {
                    $owner = Account::where('id', $sim_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return response()->json([
                            'code' => '-2',
                            'msg' => 'SIM is not available. Not valid owner.'
                        ]);
                    }
                }
            }

            $mapping = StockMapping::whereIn('product', $product_ids)->where('sim', $sim_obj->sim_serial)->where('status', 'A')->first();

            $denoms = Denom::whereIn('product_id', $product_ids)->where('status', 'A')->get();
            if (empty($denoms)) {
                return response()->json([
                    'code' => '-2',
                    'msg' => 'Boom Purple activation is not ready.'
                ]);
            }

            $plans = Array();
            if (empty($sim_obj->amount)) {
                foreach ($denoms as $d) {
                    $plans[] = [
                        'denom_id' => $d->id,
                        'denom' => $d->denom,
                        'name'  => $d->name
                    ];
                }
            } else {
                $ds = explode('|', $sim_obj->amount);
                foreach ($ds as $s) {
                    $denom_tmp = Denom::whereIn('product_id', $product_ids)->where('denom', $s)->where('status', 'A')->get();
                    foreach ($denom_tmp as $d) {
                        $plans[] = [
                            'denom_id' => $d->id,
                            'denom' => $d->denom,
                            'name' => $d->name
                        ];
                    }
                }
            }

//            foreach ($plans as $p) {
//                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $product_id, $p['denom'], 1, 1, null, $request->sim, null);
//
//                $p['spiff'] = $ret_spiff['spiff_amt'];
//            }

            $allowed_months = Helper::get_min_month($sim_obj, $account, 'boom_min_month');

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'sim'       => $sim_obj->sim_serial,
                    'meid'      => empty($mapping) ? '' : $mapping->esn,
                    'sub_carrier' => $sim_obj->sub_carrier,
//                    'product_id' => $product_id,
                    'plans'     => $plans,
                    'allowed_months' => $allowed_months,
                    'sim_charge' => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate' => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'sim_consignment_charge' => $sim_obj->type == 'C' ? (empty($sim_obj->charge_amount_r) ? 0 : $sim_obj->charge_amount_r) : 0
                ]
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function commission_red(Request $request) {
        try {
            $spiff = 0;
            $rebate = 0;
            $product_id = 'WBMRA';
            $p = Product::where('id', $product_id)->first();
            $special_spiffs = null;
            $denom   = Denom::find($request->denom_id);
            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();
            $esn_obj = StockESN::where('esn', $request->esn)->where('product', $product_id)->first();
            if (!empty($denom)) {
                $account = Account::find(Auth::user()->account_id);
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn);
                $spiff = $ret_spiff['spiff_amt'];
                if ($account->rebates_eligibility == 'Y') {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                    $rebate = $ret_rebate['rebate_amt'];
                }
                if (empty($request->sim)) {
                    $rebate += $spiff;
                }
                ### Special Spiff
                $terms = array();
                if ($request->is_port_in == 'Y') {
                    $terms[] = 'Port';
                }
                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                    $product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj, $terms
                );
            }
            $spiff_labels = Array();
            $sim_label = '';
            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
            if($sim_obj) {
                if ($sim_obj->type == 'P') {
                    if ($spiff == 0) {
                        $sim_label = 'Credit Already Paid' . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = 'Already Paid';
                    } else {
                        $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                    }
                } else {
                    $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                    $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                }
            }else {
                $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
            }
            if ($extra_spiff > 0) {
                $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';
            }
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $sim_label .= ', ' . $ss['name'] . ' $' . number_format($ss['spiff'], 2);
                    $spiff_labels[] = '$ ' . number_format($ss['spiff'], 2) . ', ' . $ss['name'];
                }
            }

            // Regulatory Fee, instead of Vendor Fee //
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            $fee = empty($vendor_denom->fee) ? 0 : $vendor_denom->fee;
            $pm_fee = empty($vendor_denom->pm_fee) ? 0 : $vendor_denom->pm_fee;

            $activation_fee = $fee + $pm_fee;

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'spiff_labels'    => $spiff_labels,
                    'sim_charge'      => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'      => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'      => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'      => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate,
                    'activation_fee'  => $activation_fee
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function commission_purple(Request $request) {
        try {
            $spiff = 0;
            $rebate = 0;
            $special_spiffs = null;
            $denom   = Denom::find($request->denom_id);
            $product_id = $denom->product_id;
            $p = Product::where('id', $product_id)->first();

            $sim_obj = StockSim::where('sim_serial', $request->sim)->where('sim_group', $p->sim_group)->first();

            // BYOD allow or not..?
            if($request->meid) {
                $esn_obj = StockESN::where('esn', $request->meid)->where('product', $product_id)->first();

                if (empty($esn_obj)) {
                    $account = Account::find(Auth::user()->account_id);
                    $p = Product::where('id', $product_id)->first();
                    StockESN::upload_byod($request->meid, $product_id, $p->carrier, $account->id, $account->name);
                }
            }else{
                $esn_obj ='';
            }

            if (!empty($denom)) {
                $account = Account::find(Auth::user()->account_id);
                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'BoomPurple');
                $spiff = $ret_spiff['spiff_amt'];

                if ($account->rebates_eligibility == 'Y') {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                    $rebate = $ret_rebate['rebate_amt'];
                }
                if (empty($request->sim)) {
                    $rebate += $spiff;
                }
                ### Special Spiff
                $terms = array();
                if ($request->is_port_in == 'Y') {
                    $terms[] = 'Port';
                }
                $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                    $product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj, $terms
                );
            }

            $spiff_labels = Array();
            $sim_label = '';
            $extra_spiff = StockSim::get_spiff_2_amt('S', $sim_obj);
            if($sim_obj) {
                if ($sim_obj->type == 'P') {
                    if ($spiff == 0) {
                        $sim_label = 'Credit Already Paid' . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = 'Already Paid';
                    } else {
                        $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                        $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                    }
                } else {
                    $sim_label = 'Credit $' . number_format($spiff - $extra_spiff, 2) . ($extra_spiff > 0 ? ', Extra Credit $' . number_format($extra_spiff, 2) : '');
                    $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
                }
            }else {
                $spiff_labels[] = '$ ' . number_format($spiff - $extra_spiff, 2);
            }
            if ($extra_spiff > 0) {
                $spiff_labels[] = '$ ' . number_format($extra_spiff, 2) . ', Extra Credit';
            }
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $sim_label .= ', ' . $ss['name'] . ' $' . number_format($ss['spiff'], 2);
                    $spiff_labels[] = '$ ' . number_format($ss['spiff'], 2) . ', ' . $ss['name'];
                }
            }

            // Regulatory Fee, instead of Vendor Fee //
            $product = Product::find($product_id);
            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();

            $fee = empty($vendor_denom->fee) ? 0 : $vendor_denom->fee;
            $pm_fee = empty($vendor_denom->pm_fee) ? 0 : $vendor_denom->pm_fee;

            $activation_fee = $fee + $pm_fee;

            return response()->json([
                'code'  => '0',
                'data'  => [
                    'spiff_labels'    => $spiff_labels,
                    'sim_charge'      => empty($sim_obj->sim_charge) ? 0 : $sim_obj->sim_charge,
                    'sim_rebate'      => empty($sim_obj->sim_rebate) ? 0 : $sim_obj->sim_rebate,
                    'esn_charge'      => empty($esn_obj->esn_charge) ? 0 : $esn_obj->esn_charge,
                    'esn_rebate'      => empty($esn_obj->esn_rebate) ? 0 : $esn_obj->esn_rebate,
                    'activation_fee'  => $activation_fee
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code'  => '-9',
                'msg'   => $ex->getMessage()
            ]);
        }
    }

    public function post_blue(Request $request) {
        try {

            Helper::log('### Boom Blue Post Start ###', '');

            $user = Auth::user();
            if (empty($user)) {
                Helper::log('### Check - Auth ###', [
                    'msg' => 'No user logged-In'
                ]);
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Your session has been expired. Please login again.'
                    ]
                ]);
            }

            if (Helper::is_login_as()) {
                Helper::log('### Check - is_login_as ###', '');
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => 'Login as is not allowed to do activation.'
                  ]
                ]);
            }

            $v = Validator::make($request->all(), [
                'sim'       => 'required',
                'esn'       => 'required',
                'zip'       => 'required|regex:/^\d{5}$/',
                'is_port_in'=> 'required',
                'denom_id'  => 'required',
                'rtr_month' => 'required',
                'first_name'=> 'required',
                'last_name' => 'required',
                'address'   => 'required',
                'city'      => 'required',
                'state'     => 'required',
//                'email'     => 'required',

                'port_in_mdn'   => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'carrier'       => 'required_if:is_port_in,Y',
                'account_no'    => 'required_if:is_port_in,Y',
                'password'      => 'required_if:is_port_in,Y',
                'call_back_number' => 'required_if:is_port_in,Y',
            ], [
                'zip.required_if'           => 'Valid zip code is required',
                'mdn.required_if'           => 'MDN is required',
                'first_name.required_if'    => 'First name is required',
                'last_name.required_if'     => 'Last name is required',
                'port_in_mdn.required_if'   => 'Port-in MDN is required',
                'carrier.required_if'       =>  'Carrier is required',
                'account_no.required_if'    => 'Account # is required',
                'password.required_if'      => 'Password is required',
                'address.required_if'       => 'Address is required',
                'city.required_if'          => 'City is required',
                'state.required_if'         => 'State is required',
                'call_back_number.required_if' => 'Call back number # is required'
//                'email.required_if'         => 'Email is required'
            ]);

            if ($v->fails()) {
                $errors = Array();
                foreach ($v->errors()->messages() as $key => $value) {
                    $errors[] = [
                        'fld' => $key,
                        'msg' => $value[0]
                    ];
                };

                return response()->json([
                    'code' => '-1',
                    'data' => $errors
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom) || $denom->status != 'A') {
                Helper::log('### Check - Denom ###', '');
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Invalid denomination provided.]'
                  ]
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                Helper::log('### Check - account ###', '');
                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Your session has been expired.]'
                  ]
                ]);
            }

            $result = Helper::check_threshold_limit_by_account($account->id);
            if ($result['code'] != '0') {
                Helper::log('### Check - check_threshold_limit_by_account ###', '');
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'    => 'threshold',
                        'msg'    => $result['msg']
                    ]
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                Helper::log('### Check - Product ###', '');
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[The product is not available.]'
                  ]
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom) || empty($vendor_denom->act_pid)) {
                Helper::log('### Check - VendorDenom ###', '');
                return response()->json([
                  'code' => '-2',
                  'data' => [
                    'fld'   => 'Your request has been failed.',
                    'msg'   => '[Vendor configuration incomplete.]'
                  ]
                ]);
            }

            // Duplicate check (Status 'I' in 10 min)
            $ret_t = Transaction::where('account_id', $account->id)
                ->where('product_id', $product->id)
                ->where('status', 'I')
                ->where('cdate', '>=', Carbon::now()->subMinutes(10)->toDateTimeString())
                ->first();

            if (!empty($ret_t)){
                Helper::log('### Check - Duplicate ###', '');
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'You already have another transaction (In Progress)',
                        'msg'   => 'Please wait for the respond at Transaction report.'
                    ]
                ]);
            }

            // ESN Device Restrictions Check (Blue)  Back to again (8/5/2020)
            $ret = boom::deviceInquery_blue($request->esn, $vendor_denom->act_pid);
            $error_code = $ret['error_code'];
            if ($error_code != '') {
                Helper::log('### Check - ESN Device Restrictions Check (Blue) ###', '');
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your ESN(IMEID) cannot be validated or compatible with selected plan. Please check your device and plan again.',
                        'msg'   => $ret['error_msg'] . ' [' . $error_code . ']'
                    ]
                ]);
            }

            $sim_obj = null;
            $esn_obj = null;

            if($request->esn) {
                $esn_obj = StockESN::where('esn', $request->esn)->where('product', 'WBMBA')->first();
                if(empty($esn_obj)){
                    $esn_obj = StockESN::upload_byod($request->esn, 'WBMBA', $product->carrier, $account->id, $account->name);
                }
            }
            if (!empty($esn_obj)) {

                if (!empty($esn_obj->amount) && $esn_obj->product != 'WBMBA') {
                    Helper::log('### Check - ESN 1 ###', '');
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld' => 'Your request has been failed.',
                            'msg' => '[Please enter valid device id.]'
                        ]
                    ]);
                }

                if (!empty($esn_obj->amount) && !in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    Helper::log('### Check - ESN 2 ###', '');
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld' => 'Your request has been failed.',
                            'msg' => '[Plan $' . $denom->denom . ' is not allowed to the device]'
                        ]
                    ]);
                }

                ### check owner path ###
                if (!empty($esn_obj->owner_id)) {
                    $owner = Account::where('id', $esn_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        Helper::log('### Check - Owner Path ###', '');
                        return response()->json([
                            'code' => '-2',
                            'data' => [
                                'fld' => 'Your request has been failed.',
                                'msg' => '[ESN is not available. Not valid owner.]'
                            ]
                        ]);
                    }
                }
            }

            if (!empty($request->sim)) {

                $sim_obj = StockSim::where('sim_serial', $request->sim)
                    ->where('sim_group', $product->sim_group)
                    ->where('status', 'A')
                    ->first();

                if (empty($sim_obj)) {
                    Helper::log('### Check - SIM ###', '');
                    return response()->json([
                      'code' => '-2',
                      'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Please enter valid SIM.]'
                      ]
                    ]);
                } else {

                    ### check owner path ###
                    if (!empty($sim_obj->owner_id)) {
                        $owner = Account::where('id', $sim_obj->owner_id)
                          ->whereRaw("? like concat(path, '%')", [$account->path])
                          ->first();
                        if (empty($owner)) {
                            Helper::log('### Check - Sim Owner ###', '');
                            return response()->json([
                              'code' => '-2',
                              'data' => [
                                'fld'   => 'Your request has been failed.',
                                'msg' => '[SIM is not available. Not valid owner.]'
                              ]
                            ]);
                        }
                    }
                }
            }

            ### fee ###
            $rtr_month  = $request->rtr_month;

            $rtr_discount   = 0;

            ### Act/Recharge Fee by products, not by accounts (7/24/19)  ###
            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt =  $denom->denom * $rtr_month - $rtr_discount;

            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            switch (substr($sim_type, 0, 1)) {
                case 'P':
                    $collection_amt = 0;
                    break;
                case 'C':
                    ### collection amount = charge.amount.r of SIM / ESN ###
                    $collection_amt = $collection_amt + StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'S');
                    break;
                case 'X':
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => 'Unable to determine SIM type.'
                        ]
                    ]);
            }

            ### check sales limit ###
            $net_revenue = 0;
            $rebate_amt = 0;
            if ($account->rebates_eligibility == 'Y') {
                if($request->esn) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                    $rebate_amt = $ret_rebate['rebate_amt'];
                }
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'BoomBlue');
            $spiff_amt = $ret_spiff['spiff_amt'];


            ### Special Spiff
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($denom->product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            ### sim/esn of recharge/rebate ###
            $sim_recharge   = 0;
            $sim_rebate     = 0;
            $esn_recharge   = 0;
            $esn_rebate     = 0;

            if(!empty($sim_obj)){
                $sim_recharge   = $sim_obj->sim_charge;
                $sim_rebate     = $sim_obj->sim_rebate;
            }

            if(!empty($esn_obj)){
                $esn_recharge   = $esn_obj->esn_charge;
                $esn_rebate     = $esn_obj->esn_rebate;
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt - $sim_recharge + $sim_rebate - $esn_recharge + $esn_rebate;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                      'code' => '-3',
                      'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => $ret['error_msg']
                      ]
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            $trans = new Transaction;
            $trans->account_id = $user->account_id;
            $trans->product_id = $denom->product_id;
            $trans->action = $request->is_port_in == 'Y' ? 'Port-In' : 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->zip = $request->zip;
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->address1 = $request->address;
            $trans->city = $request->city;
            $trans->state = $request->state;
            $trans->email = 'ops@softpayplus.com';

            if ($request->is_port_in == 'Y'){
                $trans->phone = $request->port_in_mdn;
                $trans->current_carrier = $request->carrier;
                $trans->account_no = $request->account_no;
                $trans->account_pin = $request->password;
                $trans->account_zip = $request->zip;
                $trans->call_back_phone = $request->call_back_number;
            }

            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = '';

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            $params = new \stdClass();
            $params->trans_id   = $trans->id;
            $params->zip        = $request->zip;
            $params->esn        = $request->esn;
            $params->sim        = $request->sim;
            $params->service_type = $request->service_type;
            $params->act_pid    = $vendor_denom->act_pid;
            $params->first_name = $request->first_name;
            $params->last_name  = $request->last_name;
            $params->address    = $request->address;
            $params->city       = $request->city;
            $params->state      = $request->state;
            $params->email      = 'ops@softpayplus.com';

            if ($request->is_port_in != 'Y') {

                $ret = boom::activationBlue($params);

                $trans->vendor_tx_id = $ret['vendor_tx_id'];

                /* Only for without Port In
                 *  If(mdn), Call getServiceStatus($mdn)
                 */
                if ($ret['mdn'] != ''){

                    $boom_mdn = $ret['mdn'];
                    $cnt = 0;
                    /*
                     * 30 * 5 = 100 sec. almost 2 min.
                     */
                    while($cnt < 30){
                        $ret2 = boom::getServiceStatus($boom_mdn, 'BLUE');
                        if($ret2['error_code'] == '') {
                            // Activation is complete!
                            $cnt = $cnt+30;
                        }else{
                            $cnt++;
                            sleep(5);
                        }
                    }

                    if($ret2['error_code'] != ''){
                        // Status check few times but didn't get status complete
                        // But we should deal with complete.
                        // Just sent email
                        $msg_boom = '';
                        $msg_boom .= ' - MDN : ' . $boom_mdn . '<br/>';
                        $msg_boom .= ' - error code : ' . $ret2['error_code'] . '<br/>';
                        $msg_boom .= ' - error msg : ' . $ret2['error_msg'] . '<br/>';
                        $msg_boom .= ' - cnt : ' . $cnt;
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to obtain Service Status.', $msg_boom);
                    }
                }

            } else {

                $params->portFromMDN        = $request->port_in_mdn;
                $params->portFromNetwork    = $request->carrier;
                $params->ospAcctNumber      = $request->account_no;
                $params->portFromPwdPin     = $request->password;
                $params->portin_zip         = $request->zip;
                $params->call_back_number   = $request->call_back_number;
                $params->password           = $request->password;

                // Portin($params)
                $ret = boom::activationBluePortIn($params);

                if($ret['port_reference_num'] != ''){
                    $trans->port_reference_num = $ret['port_reference_num'];
                }
                if($ret['custNbr'] != ''){
                    $trans->cust_nbr = $ret['custNbr'];
                }
            }

            Helper::log('### Boom API RESULT ###', [
                'ret' => $ret
            ]);

            if ($ret['error_code'] != '') {

                $trans->status = 'F';
                $trans->note = $ret['error_msg']. '[' . $ret['error_msg'] . ']' ;
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                return response()->json([
                    'code' => '-7',
                    'data' => [
                        'fld'   => 'Your request has been failed. [F]',
                        'msg'   => $ret['error_msg']. '[' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $ret['error_msg']. '[' . $ret['error_msg'] . ']' ;
            $trans->phone   = $request->is_port_in == 'Y' ? $request->port_in_mdn : $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            ### Consignment Charge ###
            if ($sim_type == 'C') {
                $ret = ConsignmentProcessor::charge($trans);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@jjonbp.com', '[PM][BOM][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            ### Update ESN status
            if (!empty($esn_obj)) {
                StockESN::where('esn', $esn_obj->esn)
                  ->update([
                    'used_trans_id' => $trans->id,
                    'used_date'     => Carbon::now(),
                    'esn_charge'    => null,
                    'esn_rebate'    => null,
                    'status'        => 'U'
                  ]);
            }

            ### Update Sim status
            if (!empty($sim_obj)) {
                StockSim::where('sim_serial', $sim_obj->sim_serial)
                  ->update([
                    'used_trans_id' => $trans->id,
                    'used_date'     => Carbon::now(),
                    'product'       => $denom->product_id,
                    'status'        => 'U'
                  ]);

                if(!empty($esn_obj)) {
                    $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $denom->product_id)->where('status', 'A')->first();
                    if (!empty($mapping)) {
                        $mapping->status = 'U';
                        $mapping->update();
                    }
                }
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            if ($request->is_port_in != 'Y') {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

//                    Helper::send_mail('it@perfectmobileinc.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                    Helper::send_mail('it@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                    if (!empty($ret['error_msg'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                    }
                }

                $ret = RTRProcessor::applyRTR(
                    1,
                    isset($sim_type) ? $sim_type : '',
                    $trans->id,
                    'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $vendor_denom->denom,
                    $user->user_id,
                    false,
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WBMBA'){
                        $rtr_product_id = 'WBMBAR';
                    }elseif($trans->product_id == 'WBMPA'){
                        $rtr_product_id = 'WBMPAR';
                    }elseif($trans->product_id == 'WBMRA'){
                        $rtr_product_id = 'WBMRAR';
                    }else{
                        $rtr_product_id = $trans->product_id;
                    }
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        $sim_type,
                        $trans->id,
                        'House',
                        $trans->phone,
                        $rtr_product_id,
                        $vendor_denom->vendor_code,
                        $vendor_denom->rtr_pid,
                        $vendor_denom->denom,
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
                        $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                        $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                        $msg .= ' - error : ' . $error_msg;
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
                    }
                }
            }

            ### update balance ###
            Helper::update_balance();

            Helper::log('### API call completed ###');

            return response()->json([
              'code' => '0',
              'data' => [
                'id'    => $trans->id,
                'mdn'   => $trans->phone,
                'msg'   => $trans->note
              ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
              'code' => '-9',
              'data' => [
                'fld'   => 'Your request has been failed. [EXP]',
                'msg'   => $ex->getMessage() . ' [' . $ex->getCode() . ']'
              ]
            ]);
        }
    }

    public function post_red(Request $request) {
        try {

            if (Helper::is_login_as()) {
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Login as is not allowed to do activation.'
                    ]
                ]);
            }

            $v = Validator::make($request->all(), [
                'esn' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'is_port_in' => 'required',
                'denom_id' => 'required',
                'rtr_month' => 'required',

//                'mdn'           => 'required_if:is_port_in,Y',
                'first_name'    => 'required_if:is_port_in,Y',
                'last_name'     => 'required_if:is_port_in,Y',
                'port_in_mdn'   => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'carrier'       => 'required_if:is_port_in,Y',
                'account_no'    => 'required_if:is_port_in,Y',
                'password'      => 'required_if:is_port_in,Y',
                'street_number' => 'required_if:is_port_in,Y',
                'street_name'   => 'required_if:is_port_in,Y',
                'city'          => 'required_if:is_port_in,Y',
                'state'         => 'required_if:is_port_in,Y',
                'portin_zip'    => 'required_if:is_port_in,Y',
                'call_back_number' => 'required_if:is_port_in,Y'
//                'email'         => 'required_if:is_port_in,Y'
            ], [
                'zip.required' => 'Valid zip code is required',

//                'mdn.required_if'           =>  'MDN is required',
                'first_name.required_if'    => 'First name is required',
                'last_name.required_if'     => 'Last name is required',
                'port_in_mdn.required_if'   => 'Port-in MDN is required',
                'carrier.required_if'       =>  'Carrier is required',
                'account_no.required_if'    => 'Account # is required',
                'password.required_if'      => 'Password is required',
                'street_number.required_if' => 'Street number is required',
                'street_name.required_if'   => 'Street name is required',
                'city.required_if'          => 'City is required',
                'state.required_if'         => 'State is required',
                'portin_zip.required_if'    => 'ZIP is required',
                'call_back_number.required_if' => 'Call back number # is required'
//                'email.required_if'         => 'Email is required'
            ]);

            if ($v->fails()) {
                $errors = Array();
                foreach ($v->errors()->messages() as $key => $value) {
                    $errors[] = [
                        'fld'   => $key,
                        'msg'   => $value[0]
                    ];
                };

                return response()->json([
                    'code' => '-1',
                    'data' => $errors
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom) || $denom->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Invalid denomination provided.]'
                    ]
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Your session has been expired.]'
                    ]
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[The product is not available.]'
                    ]
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom) || empty($vendor_denom->act_pid)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Vendor configuration incomplete.]'
                    ]
                ]);
            }

            // Duplicate check (Status 'I' in 10 min)
            $ret_t = Transaction::where('account_id', $account->id)
                ->where('product_id', $product->id)
                ->where('status', 'I')
                ->where('cdate', '>=', Carbon::now()->subMinutes(10)->toDateTimeString())
                ->first();

            if (!empty($ret_t)){
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'You already have another transaction (In Progress)',
                        'msg'   => 'Please wait for the respond at Transaction report.'
                    ]
                ]);
            }

            $sim_obj = null;
            $esn_obj = null;

            $esn_obj = StockESN::where('esn', $request->esn)->where('product', 'WBMRA')->first();

            if (!empty($esn_obj)) {

                if (!empty($esn_obj->amount) && $esn_obj->product != 'WBMRA') {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Please enter valid device id.]'
                        ]
                    ]);
                }

                if (!empty($esn_obj->amount) && !in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Plan $' . $denom->denom . ' is not allowed to the device]'
                        ]
                    ]);
                }

                ### check owner path ###
                if (!empty($esn_obj->owner_id)) {
                    $owner = Account::where('id', $esn_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return response()->json([
                            'code' => '-2',
                            'data' => [
                                'fld'   => 'Your request has been failed.',
                                'msg' => '[ESN is not available. Not valid owner.]'
                            ]
                        ]);
                    }
                }
            }

            if (!empty($request->sim)) {
                $sim_obj = StockSim::where('sim_serial', $request->sim)
                    ->where('sim_group', $product->sim_group)
                    ->where('status', 'A')
                    ->first();
                if (empty($sim_obj)) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Please enter valid SIM.]'
                        ]
                    ]);
                } else {

                    ### check owner path ###
                    if (!empty($sim_obj->owner_id)) {
                        $owner = Account::where('id', $sim_obj->owner_id)
                            ->whereRaw("? like concat(path, '%')", [$account->path])
                            ->first();
                        if (empty($owner)) {
                            return response()->json([
                                'code' => '-2',
                                'data' => [
                                    'fld'   => 'Your request has been failed.',
                                    'msg' => '[SIM is not available. Not valid owner.]'
                                ]
                            ]);
                        }
                    }
                }
            }

            ### fee ###
            $rtr_month = $request->rtr_month;

            $rtr_discount = 0;

            ### Act/Recharge Fee by products, not by accounts (7/24/19)  ###
            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt =  $denom->denom * $rtr_month - $rtr_discount;

            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            switch (substr($sim_type, 0, 1)) {
                case 'P':
                    $collection_amt = 0;
                    break;
                case 'C':
                    ### collection amount = charge.amount.r of SIM / ESN ###
                    $collection_amt = $collection_amt + StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'S');
                    break;
                case 'X':
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => 'Unable to determine SIM type.'
                        ]
                    ]);
            }

            ### check sales limit ###
            $net_revenue = 0;
            $rebate_amt = 0;
            if ($account->rebates_eligibility == 'Y') {
                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                $rebate_amt = $ret_rebate['rebate_amt'];
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn);
            $spiff_amt = $ret_spiff['spiff_amt'];

            ### Special Spiff
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($denom->product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            ### sim/esn of recharge/rebate ###
            $sim_recharge   = 0;
            $sim_rebate     = 0;
            $esn_recharge   = 0;
            $esn_rebate     = 0;

            if(!empty($sim_obj)){
                $sim_recharge   = $sim_obj->sim_charge;
                $sim_rebate     = $sim_obj->sim_rebate;
            }

            if(!empty($esn_obj)){
                $esn_recharge   = $esn_obj->esn_charge;
                $esn_rebate     = $esn_obj->esn_rebate;
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt - $sim_recharge + $sim_rebate - $esn_recharge + $esn_rebate;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'code' => '-3',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => $ret['error_msg']
                        ]
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            $trans = new Transaction;
            $trans->account_id = $user->account_id;
            $trans->product_id = $denom->product_id;
            $trans->action = $request->is_port_in == 'Y' ? 'Port-In' : 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->zip = $request->zip;

            if ($request->is_port_in == 'Y'){

                $trans->phone = $request->port_in_mdn;
                $trans->first_name = $request->first_name;
                $trans->last_name = $request->last_name;
                $trans->current_carrier = $request->carrier;
                $trans->account_no = $request->account_no;
                $trans->account_pin = $request->password;
                $trans->address1 = $request->street_number;
                $trans->address2 = $request->street_name;
                $trans->account_city = $request->city;
                $trans->account_state = $request->state;
                $trans->account_zip = $request->portin_zip;
                $trans->call_back_phone = $request->call_back_number;
                $trans->email = 'ops@softpayplus.com';
            }
            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = empty($request->sim) ? '3G' : '4G';

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            $params = new \stdClass();
            $params->trans_id = $trans->id;
            $params->zip = $request->zip;
            $params->esn = $request->esn;
            $params->sim = empty($request->sim) ? 'EMPTY' : $request->sim;
            $params->serviceType = empty($request->sim) ? '3G' : '4G';
            $params->act_pid = $vendor_denom->act_pid;

            if ($request->is_port_in != 'Y') {

                $ret = boom::activationRed($params);
                $trans->vendor_tx_id = $ret['vendor_tx_id'];

                /*  This is only for Active <without Port In>
                 *  If(mdn), Call getServiceStatus($mdn)
                 */
                if ($ret['mdn'] != ''){

                    $boom_mdn = $ret['mdn'];
                    $cnt = 0;
                    /*
                     * 20 * 5 = 100 sec. almost 2 min.
                     */
                    while($cnt < 20){

                        $ret2 = boom::getServiceStatus($boom_mdn, 'Red');

                        if($ret2['error_code'] == '') {
                            // Activation is complete!
                            $cnt = $cnt+20;
                        }else{
                            $cnt++;
                            sleep(5);
                        }
                    }

                    if($ret2['error_code'] != ''){
                        // Status check few times but didn't get status complete
                        // But we should deal with complete.
                        // Just sent email
                        $msg_boom = '';
                        $msg_boom .= ' - MDN : ' . $boom_mdn . '<br/>';
                        $msg_boom .= ' - error code : ' . $ret2['error_code'] . '<br/>';
                        $msg_boom .= ' - error msg : ' . $ret2['error_msg'] . '<br/>';
                        $msg_boom .= ' - cnt : ' . $cnt;
                        Helper::send_mail('it@jjonbp.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to obtain Service Status.', $msg_boom);
                    }
                }

            } else {

                $params->portFromMDN        = $request->port_in_mdn;
                $params->first_name         = $request->first_name;
                $params->last_name          = $request->last_name;
                $params->portFromNetwork    = $request->carrier;
                $params->ospAcctNumber      = $request->account_no;
                $params->portFromPwdPin     = $request->password;

                $params->street_number      = $request->street_number;
                $params->street_name        = $request->street_name;
                $params->city               = $request->city;
                $params->state              = $request->state;
                $params->portin_zip         = $request->portin_zip;
                $params->call_back_number   = $request->call_back_number;
                $params->password           = $request->password;
                $params->email              = 'ops@softpayplus.com';

                $ret = boom::activationRedPortIn($params);

                if($ret['port_reference_num'] != ''){
                    $trans->port_reference_num = $ret['port_reference_num'];
                }
            }

            Helper::log('### Boom API RESULT ###', [
                'ret' => $ret
            ]);

            if ($ret['error_code'] != '') {

                $trans->status = 'F';
                $trans->note = $ret['error_msg']. '[' . $ret['error_msg'] . ']' ;
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                return response()->json([
                    'code' => '-7',
                    'data' => [
                        'fld'   => 'Your request has been failed. [F]',
                        'msg'   => $ret['error_msg']. '[' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $ret['error_msg']. '[' . $ret['error_msg'] . ']' ;
            $trans->phone   = $request->is_port_in == 'Y' ? $request->port_in_mdn : $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            ### Consignment Charge ###
            if ($sim_type == 'C') {
                $ret = ConsignmentProcessor::charge($trans);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

//                    Helper::send_mail('it@perfectmobileinc.com', '[PM][LBT][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                    Helper::send_mail('it@jjonbp.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            ### Update ESN status
            if (!empty($esn_obj)) {
                StockESN::where('esn', $esn_obj->esn)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
                        'esn_charge'    => null,
                        'esn_rebate'    => null,
                        'status'        => 'U'
                    ]);
            }

            ### Update Sim status
            if (!empty($sim_obj)) {
                StockSim::where('sim_serial', $sim_obj->sim_serial)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
                        'product'       => $denom->product_id,
                        'status'        => 'U'
                    ]);

                $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $denom->product_id)->where('status', 'A')->first();
                if (!empty($mapping)) {
                    $mapping->status = 'U';
                    $mapping->update();
                }
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            if ($request->is_port_in != 'Y') {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

//                    Helper::send_mail('it@perfectmobileinc.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                    Helper::send_mail('it@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                    if (!empty($ret['error_msg'])) {
                        ### send message only ###
                        $msg = ' - trans ID : ' . $trans->id . '<br/>';
                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                    }
                }

                $ret = RTRProcessor::applyRTR(
                    1,
                    isset($sim_type) ? $sim_type : '',
                    $trans->id,
                    'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $vendor_denom->denom,
                    $user->user_id,
                    false,
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WBMBA'){
                        $rtr_product_id = 'WBMBAR';
                    }elseif($trans->product_id == 'WBMPA'){
                        $rtr_product_id = 'WBMPAR';
                    }elseif($trans->product_id == 'WBMRA'){
                        $rtr_product_id = 'WBMRAR';
                    }else{
                        $rtr_product_id = $trans->product_id;
                    }
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        $sim_type,
                        $trans->id,
                        'House',
                        $trans->phone,
                        $rtr_product_id,
                        $vendor_denom->vendor_code,
                        $vendor_denom->rtr_pid,
                        $vendor_denom->denom,
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
                        $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                        $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                        $msg .= ' - error : ' . $error_msg;
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
                    }
                }
            }

            ### update balance ###
            Helper::update_balance();

            Helper::log('### API call completed ###');

            return response()->json([
                'code' => '0',
                'data' => [
                    'id'    => $trans->id,
                    'mdn'   => $trans->phone,
                    'msg'   => $trans->note
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'data' => [
                    'fld'   => 'Your request has been failed. [EXP]',
                    'msg'   => $ex->getMessage() . ' [' . $ex->getCode() . ']'
                ]
            ]);
        }
    }

    public function post_purple(Request $request) {
        try {

            if (Helper::is_login_as()) {
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'exception',
                        'msg'   => 'Login as is not allowed to do activation.'
                    ]
                ]);
            }

            $v = Validator::make($request->all(), [
                'sim'       => 'required',
                'esn'       => 'required',
                'zip'       => 'required|regex:/^\d{5}$/',
                'is_port_in'=> 'required',
                'denom_id'  => 'required',
                'rtr_month' => 'required',
                'first_name'=> 'required',
                'last_name' => 'required',
                'address'   => 'required',
                'city'      => 'required',
                'state'     => 'required',
//                'email'     => 'required',

                'port_in_mdn'   => 'required_if:is_port_in,Y|regex:/^\d{10}$/',
                'carrier'       => 'required_if:is_port_in,Y',
                'account_no'    => 'required_if:is_port_in,Y',
                'password'      => 'required_if:is_port_in,Y',
                'call_back_number' => 'required_if:is_port_in,Y',
            ], [
                'zip.required_if'           => 'Valid zip code is required',
                'mdn.required_if'           => 'MDN is required',
                'first_name.required_if'    => 'First name is required',
                'last_name.required_if'     => 'Last name is required',
                'port_in_mdn.required_if'   => 'Port-in MDN is required',
                'carrier.required_if'       =>  'Carrier is required',
                'account_no.required_if'    => 'Account # is required',
                'password.required_if'      => 'Password is required',
                'address.required_if'       => 'Address is required',
                'city.required_if'          => 'City is required',
                'state.required_if'         => 'State is required',
                'call_back_number.required_if' => 'Call back number # is required'
//                'email.required_if'         => 'Email is required'
            ]);

            if ($v->fails()) {
                $errors = Array();
                foreach ($v->errors()->messages() as $key => $value) {
                    $errors[] = [
                        'fld'   => $key,
                        'msg'   => $value[0]
                    ];
                };

                return response()->json([
                    'code' => '-1',
                    'data' => $errors
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom) || $denom->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Invalid denomination provided.]'
                    ]
                ]);
            }

            $user = Auth::user();
            $account = Account::find($user->account_id);
            if (empty($account)) {
                return response()->json([
                    'code' => '-5',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Your session has been expired.]'
                    ]
                ]);
            }

            $product = Product::find($denom->product_id);
            if (empty($product) || $product->status != 'A') {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[The product is not available.]'
                    ]
                ]);
            }

            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $product->id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
            if (empty($vendor_denom) || empty($vendor_denom->act_pid)) {
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'Your request has been failed.',
                        'msg'   => '[Vendor configuration incomplete.]'
                    ]
                ]);
            }

            // Duplicate check (Status 'I' in 10 min)
            $ret_t = Transaction::where('account_id', $account->id)
                ->where('product_id', $product->id)
                ->where('status', 'I')
                ->where('cdate', '>=', Carbon::now()->subMinutes(10)->toDateTimeString())
                ->first();

            if (!empty($ret_t)){
                return response()->json([
                    'code' => '-2',
                    'data' => [
                        'fld'   => 'You already have another transaction (In Progress)',
                        'msg'   => 'Please wait for the respond at Transaction report.'
                    ]
                ]);
            }

            $product_ids = ['WBMPA', 'WBMPOA'];
            $product_id = $denom->product_id;

            $sim_obj = null;
            $esn_obj = null;

            if ($request->esn) {
                $esn_obj = StockESN::where('esn', $request->esn)->whereIn('product', $product_ids)->first();
            }
            if (!empty($esn_obj)) {

                if (!empty($esn_obj->amount) && $esn_obj->product != $product_id) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Please enter valid device id.]'
                        ]
                    ]);
                }

                if (!empty($esn_obj->amount) && !in_array($denom->denom, explode('|', $esn_obj->amount))) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Plan $' . $denom->denom . ' is not allowed to the device]'
                        ]
                    ]);
                }

                ### check owner path ###
                if (!empty($esn_obj->owner_id)) {
                    $owner = Account::where('id', $esn_obj->owner_id)
                        ->whereRaw("? like concat(path, '%')", [$account->path])
                        ->first();
                    if (empty($owner)) {
                        return response()->json([
                            'code' => '-2',
                            'data' => [
                                'fld'   => 'Your request has been failed.',
                                'msg' => '[ESN is not available. Not valid owner.]'
                            ]
                        ]);
                    }
                }
            }

            if (!empty($request->sim)) {

                $sim_obj = StockSim::where('sim_serial', $request->sim)
                    ->where('sim_group', 'BoomPurple')
                    ->where('status', 'A')
                    ->first();
                
                if (empty($sim_obj)) {
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => '[Please enter valid SIM.]'
                        ]
                    ]);
                } else {

                    ### check owner path ###
                    if (!empty($sim_obj->owner_id)) {
                        $owner = Account::where('id', $sim_obj->owner_id)
                            ->whereRaw("? like concat(path, '%')", [$account->path])
                            ->first();
                        if (empty($owner)) {
                            return response()->json([
                                'code' => '-2',
                                'data' => [
                                    'fld'   => 'Your request has been failed.',
                                    'msg' => '[SIM is not available. Not valid owner.]'
                                ]
                            ]);
                        }
                    }
                }
            }

            ### fee ###
            $rtr_month  = $request->rtr_month;

            $rtr_discount   = 0;

            ### Act/Recharge Fee by products, not by accounts (7/24/19)  ###
            $fee = $vendor_denom->fee * $rtr_month;
            $pm_fee = $vendor_denom->pm_fee * $rtr_month;

            ### get collection amount ###
            $collection_amt =  $denom->denom * $rtr_month - $rtr_discount;

            $sim_type = StockSim::get_sim_type($request->esn, $request->sim, $denom->product_id);
            switch (substr($sim_type, 0, 1)) {
                case 'P':
                    $collection_amt = 0;
                    break;
                case 'C':
                    ### collection amount = charge.amount.r of SIM / ESN ###
                    $collection_amt = $collection_amt + StockSim::get_sim_charge_amt($esn_obj, $sim_obj, 'S');
                    break;
                case 'X':
                    return response()->json([
                        'code' => '-2',
                        'data' => [
                            'fld'   => 'exception',
                            'msg'   => 'Unable to determine SIM type.'
                        ]
                    ]);
            }

            ### check sales limit ###
            $net_revenue = 0;
            $rebate_amt = 0;
            if ($account->rebates_eligibility == 'Y') {
                if($request->esn) {
                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
                    $ret_rebate = RebateProcessor::get_account_rebate_amt($rebate_type, $account, $denom->product_id, $denom->denom, 1, 1, $request->esn);
                    $rebate_amt = $ret_rebate['rebate_amt'];
                }
            }

            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $denom->product_id, $denom->denom, 1, 1, null, $request->sim, $request->esn, 'BoomPurple');
            $spiff_amt = $ret_spiff['spiff_amt'];

            ### Special Spiff
            $special_spiffs = SpiffSetupSpecial::get_special_spiffs($product_id, $denom->denom, 'S', Auth::user()->account_id, $sim_obj, $esn_obj);
            if (!empty($special_spiffs)) {
                foreach ($special_spiffs as $ss) {
                    $spiff_amt += $ss['spiff'];
                }
            }

            ### sim/esn of recharge/rebate ###
            $sim_recharge   = 0;
            $sim_rebate     = 0;
            $esn_recharge   = 0;
            $esn_rebate     = 0;

            if(!empty($sim_obj)){
                $sim_recharge   = $sim_obj->sim_charge;
                $sim_rebate     = $sim_obj->sim_rebate;
            }

            if(!empty($esn_obj)){
                $esn_recharge   = $esn_obj->esn_charge;
                $esn_rebate     = $esn_obj->esn_rebate;
            }

            $limit_amount_to_check = $collection_amt - $rebate_amt - $spiff_amt - $sim_recharge + $sim_rebate - $esn_recharge + $esn_rebate;

            if ($limit_amount_to_check + $fee + $pm_fee > 0) {
                $ret = PaymentProcessor::check_limit($user->account_id, $denom->id, $limit_amount_to_check, $fee + $pm_fee, false);
                if (!empty($ret['error_msg'])) {
                    return response()->json([
                        'code' => '-3',
                        'data' => [
                            'fld'   => 'Your request has been failed.',
                            'msg'   => $ret['error_msg']
                        ]
                    ]);
                }

                $net_revenue = $ret['net_revenue'];
            }

            $trans = new Transaction;
            $trans->account_id = $user->account_id;
            $trans->product_id = $denom->product_id;
            $trans->action = $request->is_port_in == 'Y' ? 'Port-In' : 'Activation';
            $trans->denom = $denom->denom;
            $trans->denom_id = $denom->id;
            $trans->sim = $request->sim;
            $trans->esn = $request->esn;
            $trans->zip = $request->zip;
            $trans->first_name = $request->first_name;
            $trans->last_name = $request->last_name;
            $trans->address1 = $request->address;
            $trans->city = $request->city;
            $trans->state = $request->state;
            $trans->email = 'ops@softpayplus.com';

            if ($request->is_port_in == 'Y'){
                $trans->phone = $request->port_in_mdn;
                $trans->current_carrier = $request->carrier;
                $trans->account_no = $request->account_no;
                $trans->account_pin = $request->password;
                $trans->account_zip = $request->zip;
                $trans->call_back_phone = $request->call_back_number;
            }

            $trans->created_by = $user->user_id;
            $trans->cdate = Carbon::now();
            $trans->status = 'I';
            $trans->dc = '';
            $trans->dp = '';
            $trans->phone_type = '';

            $trans->collection_amt = $collection_amt;
            $trans->rtr_month = $rtr_month;
            $trans->fee = $fee;
            $trans->pm_fee = $pm_fee;
            $trans->net_revenue = $net_revenue;
            $trans->vendor_code = $product->vendor_code;

            $trans->save();

            $params = new \stdClass();
            $params->trans_id   = $trans->id;
            $params->zip        = $request->zip;
            $params->esn        = $request->esn;
            $params->sim        = $request->sim;
            $params->service_type = $request->service_type;
            $params->act_pid    = $vendor_denom->act_pid;
            $params->first_name = $request->first_name;
            $params->last_name  = $request->last_name;
            $params->address    = $request->address;
            $params->city       = $request->city;
            $params->state      = $request->state;
            $params->email      = 'ops@softpayplus.com';

            if ($request->is_port_in != 'Y') {

                $ret = boom::activationPurple($params);

                $trans->vendor_tx_id = $ret['vendor_tx_id'];

                /* Only for without Port In
                 *  If(mdn), Call getServiceStatus($mdn)
                 */
                if ($ret['mdn'] != ''){

                    $boom_mdn = $ret['mdn'];
                    $cnt = 0;
                    /*
                     * 20 * 5 = 100 sec. almost 2 min.
                     */
                    while($cnt < 20){

                        $ret2 = boom::getServiceStatus($boom_mdn, 'Pink');

                        if($ret2['error_code'] == '') {
                            // Activation is complete!
                            $cnt = $cnt+20;
                        }else{
                            $cnt++;
                            sleep(5);
                        }
                    }

                    if($ret2['error_code'] != ''){
                        // Status check few times but didn't get status complete
                        // But we should deal with complete.
                        // Just sent email
                        $msg_boom = '';
                        $msg_boom .= ' - MDN : ' . $boom_mdn . '<br/>';
                        $msg_boom .= ' - error code : ' . $ret2['error_code'] . '<br/>';
                        $msg_boom .= ' - error msg : ' . $ret2['error_msg'] . '<br/>';
                        $msg_boom .= ' - cnt : ' . $cnt;
                        Helper::send_mail('it@jjonbp.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to obtain Service Status.', $msg_boom);
                    }
                }

            } else {

                $params->portFromMDN        = $request->port_in_mdn;
                $params->portFromNetwork    = $request->carrier;
                $params->ospAcctNumber      = $request->account_no;
                $params->portFromPwdPin     = $request->password;
                $params->portin_zip         = $request->zip;
                $params->call_back_number   = $request->call_back_number;
                $params->password           = $request->password;

                $ret = boom::activationPurplePortIn($params);

                if($ret['port_reference_num'] != ''){
                    $trans->port_reference_num = $ret['port_reference_num'];
                }
            }

            Helper::log('### Boom API RESULT ###', [
                'ret' => $ret
            ]);

            if ($ret['error_code'] != '') {

                $trans->status = 'F';
                $trans->note = $ret['error_msg']. '[' . $ret['error_msg'] . ']' ;
                $trans->mdate = Carbon::now();
                $trans->modified_by = $user->user_id;
                $trans->api = 'Y';
                $trans->save();

                return response()->json([
                    'code' => '-7',
                    'data' => [
                        'fld'   => 'Your request has been failed. [F]',
                        'msg'   => $ret['error_msg']. '[' . $ret['error_code'] . ']'
                    ]
                ]);
            }

            $trans->status  = $request->is_port_in == 'Y' ? 'Q' : 'C';
            $trans->note    .= ' ' . $ret['error_msg']. '[' . $ret['error_msg'] . ']' ;
            $trans->phone   = $request->is_port_in == 'Y' ? $request->port_in_mdn : $ret['mdn'];
            $trans->mdate   = Carbon::now();
            $trans->modified_by = $user->user_id;
            $trans->api = 'Y';
            $trans->save();

            ### Consignment Charge ###
            if ($sim_type == 'C') {
                $ret = ConsignmentProcessor::charge($trans);
                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@jjonbp.com', '[PM][BOM][' . getenv('APP_ENV') . '] Failed to charge for consignment SIM / ESN', $msg);
                }
            }

            ### Update ESN status
            if (!empty($esn_obj)) {
                StockESN::where('esn', $esn_obj->esn)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
                        'esn_charge'    => null,
                        'esn_rebate'    => null,
                        'comments'      => '',
                        'status'        => 'U'
                    ]);
            }

            ### Update Sim status
            if (!empty($sim_obj)) {
                StockSim::where('sim_serial', $sim_obj->sim_serial)
                    ->update([
                        'used_trans_id' => $trans->id,
                        'used_date'     => Carbon::now(),
                        'product'       => $denom->product_id,
                        'status'        => 'U'
                    ]);
                if(!empty($esn_obj)) {
                    $mapping = StockMapping::where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->where('product', $denom->product_id)->where('status', 'A')->first();
                    if (!empty($mapping)) {
                        $mapping->status = 'U';
                        $mapping->update();
                    }
                }
            }

            ### spiff ###
            # R: Regular SIM only has 1 rtr month, so no point of considering 3 rtr month spiff
            if ($request->is_port_in != 'Y') {
                $ret = SpiffProcessor::give_spiff($trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, $trans->id, $trans->created_by, 1, null, $trans->sim, $trans->esn, $trans->denom_id);

                if (!empty($ret['error_msg'])) {
                    ### send message only ###
                    $msg = ' - trans ID : ' . $trans->id . '<br/>';
                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                    Helper::send_mail('it@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                }

                ### Pay extra spiff and sim charge, sim rebate
                Promotion::create_by_order($sim_obj, $account, $trans->id);

                ### Pay extra spiff and esn charge, esn rebate
                Promotion::create_by_order_esn($esn_obj, $account, $trans->id);

                ### rebate ###
                if (!empty($trans->esn)) {

                    if($esn_obj) {
                        $rebate_type = empty($esn_obj) ? 'B' : 'R';
                        $ret = RebateProcessor::give_rebate($rebate_type, $trans->account_id, $trans->product_id, $trans->denom, 1, $trans->phone, null, $trans->id, $trans->created_by, 1, $trans->esn, $trans->denom_id);
                        if (!empty($ret['error_msg'])) {
                            ### send message only ###
                            $msg = ' - trans ID : ' . $trans->id . '<br/>';
                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                            Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                        }
                    }
                }

                $ret = RTRProcessor::applyRTR(
                    1,
                    isset($sim_type) ? $sim_type : '',
                    $trans->id,
                    'Carrier',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $vendor_denom->denom,
                    $user->user_id,
                    false,
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                );

                if (!empty($ret)) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                }

                if ($trans->rtr_month > 1) {
                    if($trans->product_id == 'WBMBA'){
                        $rtr_product_id = 'WBMBAR';
                    }elseif($trans->product_id == 'WBMPA'){
                        $rtr_product_id = 'WBMPAR';
                    }elseif($trans->product_id == 'WBMRA'){
                        $rtr_product_id = 'WBMRAR';
                    }else{
                        $rtr_product_id = $trans->product_id;
                    }
                    $error_msg = RTRProcessor::applyRTR(
                        $trans->rtr_month,
                        $sim_type,
                        $trans->id,
                        'House',
                        $trans->phone,
                        $rtr_product_id,
                        $vendor_denom->vendor_code,
                        $vendor_denom->rtr_pid,
                        $vendor_denom->denom,
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
                        $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                        $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                        $msg .= ' - error : ' . $error_msg;
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
                    }
                }
            }

            ### update balance ###
            Helper::update_balance();

            Helper::log('### API call completed ###');

            return response()->json([
                'code' => '0',
                'data' => [
                    'id'    => $trans->id,
                    'mdn'   => $trans->phone,
                    'msg'   => $trans->note
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'code' => '-9',
                'data' => [
                    'fld'   => 'Your request has been failed. [EXP]',
                    'msg'   => $ex->getMessage() . ' [' . $ex->getCode() . ']'
                ]
            ]);
        }
    }

    public function success(Request $request, $id) {

        $trans = Transaction::find($id);
        $product_id = $trans->product_id;
        $trans->product = Product::where('id', $trans->product_id)->first();

        $account = Account::find(Auth::user()->account_id);

        if($product_id == 'WBMRA'){
            return view('sub-agent.activate.boom-red')->with([
                'trans' => $trans,
                'account' => $account
            ]);
        }elseif($product_id == 'WBMBA'){
            return view('sub-agent.activate.boom-blue')->with([
                'trans' => $trans,
                'account' => $account
            ]);
        }else{
            return view('sub-agent.activate.boom-purple')->with([
                'trans' => $trans,
                'account' => $account
            ]);
        }
    }

    public function invoice($id) {
        $trans = Transaction::find($id);
        $trans->product = Product::where('id', $trans->product_id)->first();

        return view('sub-agent.activate.invoice')->with([
          'trans' => $trans
        ]);
    }

    public function test(Request $request) {

        $ret = boom::deviceInquery_blue('359170070930639');

//        $ret = boom::activationPink();

//        $ret = boom::updatePlan();
//        $ret = boom::cancelPendingPort();
//        $ret = boom::updatePlan();
//        $ret = boom::updatePendingPort();
//        $ret = boom::validateMdn(); // ok
//        $ret = boom::activationCustomerWithPortIn(); // ok
//        $ret = boom::activationCustomerWithoutPortIn(); //ok
//        $ret = boom::activationBlue();
//        $ret = boom::activationRed();

//        $port_ins = Transaction::join('product', 'transaction.product_id', 'product.id')
//            ->where('transaction.status', 'Q')
//            ->where('transaction.action', 'Port-In')
////            ->where('transaction.cdate', Carbon::today()->subDay(10))
//            ->selectRaw('transaction.*, product.carrier')
//            ->get();
//
//        $cnt = 0;
//
//        if (count($port_ins) > 0) {
//            foreach ($port_ins as $o) {
//
//                if ($o->carrier() == 'Boom Mobile') {
//
//                    if($o->product_id == 'WBMBA'){
//                        $network = 'BLUE';
//                    }elseif ($o->product_id == 'WBMRA'){
//                        $network = 'RED';
//                    }elseif ($o->product_id == 'WBMPA'){
//                        $network =  'PINK';
//                    }
//
//                    $ret = boom::checkPortStatus($network, $o->phone);
//
//                    $o->portable_reason = $ret['error_msg'];
//                    $o->portstatus = $ret['port_status'];
//                    $o->update();
//
//                    if (!empty($ret['error_code'])) { // If not 11_0, keep going
//
//                        continue;
//                    } else {
//
//                        // Get Plan_code
//                        $product = Product::find($o->product_id);
//                        if (empty($product)) {
//                            throw new \Exception('product not found');
//                        }
//
//                        $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
//                            ->where('product_id', $product->id)
//                            ->where('denom', $o->denom)
//                            ->first();
//                        if (empty($vendor_denom)) {
//                            throw new \Exception('Vendor Denomination not found');
//                        }
//
//                        $port_status = $ret['port_status'];
//
//                        if($port_status == 'RESOLUTION_REQUIRED'){
//                            /*
//                             * Update transaction status to 'R' for escaping loop.
//                             */
//                            $o->status = 'R';
//                            $o->save();
//
//                            $msg = "Transaction " . $o->id . " has some issue! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";
//                            event(new TransactionStatusUpdated($o, $msg));
//
//                            $cnt++;
//                            continue;
//                        } elseif ($port_status == 'CONFIRMED') {
//
//                            if($network == 'BLUE') {
//
//                                $product = Product::find($o->product_id);
//                                if (empty($product)) {
//                                    throw new \Exception('product not found');
//                                }
//
//                                $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
//                                    ->where('product_id', $product->id)
//                                    ->where('denom', $o->denom)
//                                    ->first();
//                                if (empty($vendor_denom)) {
//                                    throw new \Exception('Vendor Denomination not found');
//                                }
//
//                                $esn = $o->esn;
//                                $sim = $o->sim;
//                                $cust_nbr = $o->cust_nbr;
//                                $plan_code = $vendor_denom->act_pid;
//                                $first_name = $o->first_name;
//                                $last_name = $o->last_name;
//                                $address1 = $o->address1;
//                                $city = $o->city;
//                                $state = $o->state;
//                                $zip = $o->zip;
//                                $email = $o->email;
//                                $phone = $o->phone;
//                                $account_zip = $o->account_zip;
//                                $acct = $o->account_no;
//                                $pw = $o->account_pin;
//                                $cur_car = $o->current_carrier;
//
//                                $ret = boom::finalizeActivation($esn, $sim, $plan_code, $cust_nbr, $first_name, $last_name, $address1, $city, $state, $zip, $email, $phone, $account_zip, $acct, $pw, $cur_car);
//
//                                if($ret['error_code'] == ''){ // If Completed!
//                                    $o->status = 'C';
//                                    $o->save();
//
//                                    ### 1st spiff for port-in ###
//                                    $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn);
//                                    if (!empty($ret['error_msg'])) {
//                                        ### send message only ###
//                                        $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//                                        Helper::send_mail('jin@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
//                                    }
//
//                                    ### Pay extra spiff and sim charge, sim rebate
//                                    $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
//                                    $account = Account::find($o->account_id);
//                                    Promotion::create_by_order($sim_obj, $account, $o->id);
//
//                                    ### Pay extra spiff and esn charge, esn rebate
//                                    $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
//                                    Promotion::create_by_order_esn($esn_obj, $account, $o->id);
//
//
//                                    ### rebate ###
//                                    if (!empty($o->esn)) {
//                                        $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
//                                        $spiff_amt = $ret_spiff['spiff_amt'];
//
//                                        $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();
//                                        $rebate_type = empty($esn_obj) ? 'B' : 'R';
//                                        $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
//                                        if (!empty($ret['error_msg'])) {
//                                            ### send message only ###
//                                            $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//
//                                            Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
//                                        }
//                                    }
//
//                                    ### rebate ###
//                                    if (!empty($o->esn)) {
//                                        $rebate_type = empty($esn_obj) ? 'B' : 'R';
//                                        $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, null, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
//                                        if (!empty($ret['error_msg'])) {
//                                            ### send message only ###
//                                            $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//                                            Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
//                                        }
//                                    }
//
//                                    $ret = RTRProcessor::applyRTR(
//                                        1,
//                                        isset($sim_type) ? $sim_type : '',
//                                        $o->id,
//                                        'Carrier',
//                                        $o->phone,
//                                        $o->vendor_code,
//                                        '',
//                                        $o->denom,
//                                        'system',
//                                        false,
//                                        null,
//                                        1,
//                                        $o->fee,
//                                        $o->rtr_month
//                                    );
//
//                                    if (!empty($ret)) {
//                                        Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
//                                    }
//
//                                    if ($o->rtr_month > 1) {
//                                        $error_msg = RTRProcessor::applyRTR(
//                                            $o->rtr_month,
//                                            $sim_type,
//                                            $o->id,
//                                            'House',
//                                            $o->phone,
//                                            $o->vendor_code,
//                                            '',
//                                            $o->denom,
//                                            'system',
//                                            true,
//                                            null,
//                                            2,
//                                            $o->fee
//                                        );
//
//                                        if (!empty($error_msg)) {
//                                            $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                            $msg .= ' - vendor : ' . $o->vendor_code . '<br/>';
//                                            $msg .= ' - product : ' . $o->product_id . '<br/>';
//                                            $msg .= ' - denom : ' . $o->denom . '<br/>';
//                                            $msg .= ' - fee : ' . $o->fee . '<br/>';
//                                            $msg .= ' - error : ' . $error_msg;
//                                            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
//                                        }
//                                    }
//                                }
//                            }
//
//                        } elseif ($port_status == 'COMPLETED') {
//                            /*
//                             * Port In Confirmed. Give spiff..
//                             *
//                             * Boom advised us to call getServiceStatus() when Red, Pink.
//                             * Boom advised us to call finalizeActivation() when Blue. (Not implement)
//                             */
//
//                            if($network == 'RED' || $network == 'PINK') {
//                                $o->status = 'C';
//                                $o->save();
//
//                                ### 1st spiff for port-in ###
//                                $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn);
//                                if (!empty($ret['error_msg'])) {
//                                    ### send message only ###
//                                    $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//                                    Helper::send_mail('jin@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
//                                }
//
//                                ### Pay extra spiff and sim charge, sim rebate
//                                $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
//                                $account = Account::find($o->account_id);
//                                Promotion::create_by_order($sim_obj, $account, $o->id);
//
//                                ### Pay extra spiff and esn charge, esn rebate
//                                $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
//                                Promotion::create_by_order_esn($esn_obj, $account, $o->id);
//
//
//                                ### rebate ###
//                                if (!empty($o->esn)) {
//                                    $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
//                                    $spiff_amt = $ret_spiff['spiff_amt'];
//
//                                    $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();
//                                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
//                                    $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
//                                    if (!empty($ret['error_msg'])) {
//                                        ### send message only ###
//                                        $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//
//                                        Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
//                                    }
//                                }
//
//                                ### rebate ###
//                                if (!empty($o->esn)) {
//                                    $rebate_type = empty($esn_obj) ? 'B' : 'R';
//                                    $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, null, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
//                                    if (!empty($ret['error_msg'])) {
//                                        ### send message only ###
//                                        $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
//                                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
//                                        Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
//                                    }
//                                }
//
//                                $ret = RTRProcessor::applyRTR(
//                                    1,
//                                    isset($sim_type) ? $sim_type : '',
//                                    $o->id,
//                                    'Carrier',
//                                    $o->phone,
//                                    $o->vendor_code,
//                                    '',
//                                    $o->denom,
//                                    'system',
//                                    false,
//                                    null,
//                                    1,
//                                    $o->fee,
//                                    $o->rtr_month
//                                );
//
//                                if (!empty($ret)) {
//                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
//                                }
//
//                                if ($o->rtr_month > 1) {
//                                    $error_msg = RTRProcessor::applyRTR(
//                                        $o->rtr_month,
//                                        $sim_type,
//                                        $o->id,
//                                        'House',
//                                        $o->phone,
//                                        $o->vendor_code,
//                                        '',
//                                        $o->denom,
//                                        'system',
//                                        true,
//                                        null,
//                                        2,
//                                        $o->fee
//                                    );
//
//                                    if (!empty($error_msg)) {
//                                        $msg = ' - trans ID : ' . $o->id . '<br/>';
//                                        $msg .= ' - vendor : ' . $o->vendor_code . '<br/>';
//                                        $msg .= ' - product : ' . $o->product_id . '<br/>';
//                                        $msg .= ' - denom : ' . $o->denom . '<br/>';
//                                        $msg .= ' - fee : ' . $o->fee . '<br/>';
//                                        $msg .= ' - error : ' . $error_msg;
//                                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
//                                    }
//                                }
//                            }
//
//                        } else {
//                            /*
//                             * Unknown, Pending, Delayed, Error, Request
//                             */
//                            continue;
//                        }
//                    }
//
//
//
//                }
//            }
//        }
//        dd('end');

//        $ret = boom::checkPortStatus('4083910226', 'EMPTY');
//        $ret = boom::deviceInquery();
//        $ret = boom::getServiceStatus('2012840512');
//        $ret = boom::activationRedPortIn();
//        $ret = boom::getCustomerInfo();

    }

}