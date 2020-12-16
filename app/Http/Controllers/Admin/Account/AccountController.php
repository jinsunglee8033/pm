<?php

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\Permission;
use App\Mail\ApplySubagent;
use App\Mail\CheckList;
use App\Mail\HowToUsePortal;
use App\Mail\UserCreated;
use App\Mail\UserUpdated;
use App\Model\AccountFileAtt;
use App\Model\AccountShipFee;
use App\Model\AccountVRAuth;
use App\Model\ActivationController;
use App\Model\Carrier;
use App\Model\DefaultSubSpiff;
use App\Model\GenFee;
use App\Model\LoginHistory;
use App\Model\Product;
use App\Model\Role;
use App\Model\SpiffSetup;
use App\Model\SpiffTemplate;
use App\Model\SpiffTemplateOwner;
use App\Model\VRProduct;
use http\Env\Response;
use Illuminate\Http\Request;
use App\Model\Account;
use App\Model\AccountAuthority;
use App\Model\AccountFile;
use App\Model\AccountStoreType;
use App\Model\ATTBatchFee;
use App\Model\ATTBatchFeeBase;
use App\Model\State;
use App\Model\Transaction;
use App\Model\StoreType;
use App\Model\RatePlan;
use App\Model\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Session;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Facades\Excel;
use Log;
use App\User;
use Mail;
use DB;

/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/15/17
 * Time: 5:54 PM
 */
class AccountController extends Controller
{
    public function show(Request $request) {

        $accounts = Account::whereRaw('1=1');

        if (!empty($request->type)) {
            if ($request->include_sub_account == 'Y') {

                $types = [];
                switch ($request->type) {
                    case 'L':
                        $types = ['L', 'M', 'D', 'S'];
                        break;
                    case 'M':
                        $types = ['M', 'D', 'S'];
                        break;
                    case 'D':
                        $types = ['D', 'S'];
                        break;
                    case 'S':
                        $types = ['S'];
                        break;
                }

                $accounts = $accounts->whereIn('type', $types);

            } else {
                $accounts = $accounts->where('type', $request->type);
            }

        }

        if (!empty($request->name)) {

            $accounts = $accounts->whereRaw("lower(name) like ?", '%' . strtolower($request->name) . '%');

            if ($request->include_sub_account_name == 'Y') {

                $searched_accounts = Account::whereRaw("lower(name) like ?", '%'. strtolower($request->name) . '%')->get();

                foreach($searched_accounts as $sa) {

                    $accounts = $accounts->orWhere('path', 'like', $sa->path . '%');
                }
            }
        }

        if (!empty($request->office_number)) {
            $accounts = $accounts->where('office_number', $request->office_number);
        }

        if (!empty($request->status)) {
            $accounts = $accounts->where('status', $request->status);
        }

//        if (!empty($request->email)) {
//            $accounts = $accounts->whereRaw('lower(email) like \'%' . strtolower($request->email) . '%\'')
//                            ->orWhereRaw('lower(email2) like \'%' . strtolower($request->email) . '%\'');
//        }

        if (!empty($request->emails)) {
            if($request->emails_except == 'Y'){
                $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                $con = sprintf(" '%s' ", implode("', '", $emails));
                $con = strtolower($con);
                $accounts = $accounts->whereRaw(" ( lower(IfNull(email,'')) not in ($con) and lower(IfNull(email2,'')) not in ($con) ) ");
            } else {
                $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                $con = sprintf(" '%s' ", implode("', '", $emails));
                $con = strtolower($con);
                $accounts = $accounts->whereRaw(" ( lower(IfNull(email,'')) in ($con) or lower(IfNull(email2,'')) in ($con) ) ");
            }
        }

        if (!empty($request->user_id)) {
            $accounts = $accounts->whereRaw("id in (select account_id from users where user_id = '$request->user_id')");
        }

        if (!empty($request->tax_id)) {
            $accounts = $accounts->where('tax_id', 'like', '%' . $request->tax_id . '%');
        }

        if (!empty($request->id)) {
            $target_account = Account::find($request->id);
            if ($request->include_sub_account_id == 'Y') {
                $accounts = $accounts->where('path', 'like', $target_account->path . '%');
            } else {
                $accounts = $accounts->where('id', $request->id);
            }
        }

        if (!empty($request->ids)) {
            if($request->ids_except == 'Y'){
                $ids = preg_split('/[\ \r\n\,]+/', $request->ids);
                $accounts = $accounts->whereNotIn('id', $ids);
            }else {
                $ids = preg_split('/[\ \r\n\,]+/', $request->ids);
                $accounts = $accounts->whereIn('id', $ids);
            }
        }

        if (!empty($request->codes)) {
            $codes = preg_split('/[\ \r\n\,]+/', $request->codes);
            $accounts = $accounts->whereIn('att_dealer_code', $codes);
        }

        if (!empty($request->code_like)) {
            $accounts = $accounts->whereRaw('lower(att_dc_notes ) like "%' . strtolower($request->code_like) . '%"');
        }

        if (!empty($request->is_code)) {
            if($request->is_code == 'Y')
            $accounts = $accounts->whereNotNull('att_dealer_code');
        }

        if (!empty($request->no_att_code)) {
            $accounts = $accounts->whereNull('att_dealer_code');
        }

        if (!empty($request->user_name)) {
            $accounts = $accounts->whereRaw("id in (select account_id from users where lower(name) like '%" . strtolower($request->user_name) . "%')");
        }

        $store_type_ids = "";
        $store_type_ids_arr = [];
        if (is_array($request->store_types)) {
            foreach ($request->store_types as $o) {
                $store_type_ids .= (empty($store_type_ids) ? "" : ",") . "\"" . $o . "\"";
                $store_type_ids_arr[] = $o;
            }
        }

        Helper::log('XXX STORE TYPE IDS XXX', [
            'XXX' => $store_type_ids
        ]);

        if (!empty($store_type_ids)) {
            $accounts = $accounts->whereRaw('id in (select account_id from account_store_type where store_type_id in (' . $store_type_ids . '))');
        }

        if (!empty($request->state)) {
            $accounts = $accounts->where('state', $request->state);
        }

        if (!empty($request->city)) {
            $accounts = $accounts->whereRaw("lower(city) like ?", '%'. strtolower($request->city) . '%');
        }

        if (!empty($request->zip)) {
            $accounts = $accounts->where('zip', $request->zip);
        }

        if (!empty($request->address1)) {
            $accounts = $accounts->whereRaw('lower(address1) like ?', '%' . strtolower($request->address1) . '%');
        }

        if (!empty($request->address2)) {
            $accounts = $accounts->whereRaw('lower(address2) like ?', '%' . strtolower($request->address2) . '%');
        }

        if (!empty($request->dealer_code)) {
            $accounts = $accounts->where('dealer_code', 'like', '%' . $request->dealer_code . '%');
        }

        if ($request->user_not_created == 'Y') {
            $accounts = $accounts->whereRaw('(select count(*) from users where account_id = accounts.id) = 0');
        }

        if (!empty($request->rate_plan_id)) {
            //$accounts = $accounts->where('rate_plan_id', $request->rate_plan_id);
            $accounts = $accounts->whereRaw("
            (
                rate_plan_id = ? 
                or id in (
                    select owner_id
                    from rate_plan
                    where id = ?
                )   
            )", [
                $request->rate_plan_id, $request->rate_plan_id
            ]);
        }

        if (!empty($request->is_c_store)) {
            $accounts = $accounts->where('c_store', 'Y');
        }

        if (!empty($request->is_d_s_report)) {
            $accounts = $accounts->where('show_discount_setup_report', 'Y');
        }

        if (!empty($request->is_s_s_report)) {
            $accounts = $accounts->where('show_spiff_setup_report', 'Y');
        }

        if (!empty($request->is_rebates_eligibility)) {
            $accounts = $accounts->where('rebates_eligibility', 'N');
        }

        if ($request->rate_plan_not_assigned == 'Y') {

            $accounts = $accounts->whereNull('rate_plan_id');

//// Ref. Show Accounts which made rate plan but not assigned to any place
//            $assigned_rate_plans = Account::distinct()->select('rate_plan_id')->groupBy('rate_plan_id')->get()->toArray();
//
//            $assigned_rate_plan_ids = "";
//            if (!empty($assigned_rate_plans)) {
//                foreach ($assigned_rate_plans as $o) {
//                    $assigned_rate_plan_ids .= (empty($assigned_rate_plan_ids) ? "" : ",") . "'" . $o["rate_plan_id"] . "'";
//                }
//            }
//
//            if (!empty($assigned_rate_plan_ids)) {
//                $accounts = $accounts->whereRaw('id in (select owner_id from rate_plan where id not in (' . $assigned_rate_plan_ids . '))');
//            }

        }

        if (!empty($request->pay_method)) {
            $accounts = $accounts->where('pay_method', $request->pay_method);
        }

        if ($request->no_bank_info == 'Y') {
            $accounts = $accounts->whereRaw("(
                pay_method = 'C' and (
                ifnull(ach_routeno, '') = '' or 
                ifnull(ach_acctno, '') = '' or 
                ifnull(ach_holder, '') = '' or 
                ifnull(ach_bank, '') = '')
            )");
        }

        if ($request->yes_bank_info == 'Y') {
            $accounts = $accounts->whereRaw("(
                pay_method = 'C' and (
                    ifnull(ach_bank, '') != '' or 
                    ifnull(ach_holder, '') != '' or
                    ifnull(ach_routeno, '') != '' or 
                    ifnull(ach_acctno, '') != ''
                )   
            )");
        }

        if (!empty($request->ach_routeno)) {
            $accounts = $accounts->where('ach_routeno', 'like', '%' . trim($request->ach_routeno) . '%');
        }

        if (!empty($request->ach_acctno)) {
            $accounts = $accounts->where('ach_acctno', 'like', '%' . trim($request->ach_acctno) . '%');
        }

        if (!empty($request->no_ach)) {
            $accounts = $accounts->where('no_ach', $request->no_ach);
        }

        if (!empty($request->no_postpay)) {
            $accounts = $accounts->where('no_postpay', $request->no_postpay);
        }

        if (!empty($request->att_tid)) {
            $accounts = $accounts->where('type', 'S');
            
            if ($request->att_tid == 'Y') {
                $accounts = $accounts->whereRaw('((att_tid is not null) or (att_tid2 is not null))');
            } else if ($request->att_tid == 'N') {
                $accounts = $accounts->whereRaw('((att_tid is null) and (att_tid2 is null) )');
            } else {
                $accounts = $accounts->whereRaw('(att_tid = "' . $request->att_tid . '" or att_tid2 = "' . $request->att_tid . '" )');
            }
        }

        if (!empty($request->att_ids)) {
            $att_ids = preg_split('/[\ \r\n\,]+/', $request->att_ids);
            $accounts = $accounts->whereIn('att_tid', $att_ids)
                                ->orWhereIn('att_tid2', $att_ids);
        }

        if ($request->no_att_byos == 'Y') {
            $accounts = $accounts->whereRaw("(att_allow_byos is null or att_allow_byos <> 'Y')")
                ->where('type', 'S');
        }

        if (!empty($request->notes)) {
            if ($request->notes == 'Y') {
                $accounts = $accounts->whereRaw('notes is not null');
            } else if ($request->notes == 'N') {
                $accounts = $accounts->whereRaw('notes is null');
            } else {
                $accounts = $accounts->whereRaw('lower(notes) like ?', '%' . strtolower($request->notes) . '%');
            }
        }

        if (!empty($request->credit_limit)) {
            if ($request->credit_limit == 'Y') {
                $accounts = $accounts->whereRaw('credit_limit > 0');
            } else if ($request->credit_limit == 'N') {
                $accounts = $accounts->whereRaw('credit_limit = 0');
            } else {
                $accounts = $accounts->whereRaw('credit_limit >= ' . $request->credit_limit);
            }
        }

        if (!empty($request->created_sdate)) {
//            $accounts = $accounts->whereRaw('cast(cdate as date) >= \'' . $request->created_sdate . '\'');
            $accounts = $accounts->where('cdate', '>=', Carbon::parse($request->created_sdate . ' 00:00:00'));
        }

        if (!empty($request->created_edate)) {
//            $accounts = $accounts->whereRaw('cast(cdate as date) <= \'' . $request->created_edate . '\'');
            $accounts = $accounts->where('cdate', '<=', Carbon::parse($request->created_edate . ' 23:59:59'));
        }

        $user = Auth::user();
        $user_account = Account::find($user->account_id);
        $path = $user_account->path;

        $accounts = $accounts->whereRaw('path like \'' . $path . '%\'');

        if (!empty($request->wait_for_approve)) {
            $accounts = $accounts->where('wait_for_approve', $request->wait_for_approve);
        }

        $order_by = empty($request->order_by) ? 'path asc' : $request->order_by;

        if ($request->wait_for_approve == 'Y') {
            $accounts = $accounts->orderBy('cdate', 'desc');
        }else {
            switch ($order_by) {
                case 'path asc':
                    $accounts = $accounts->orderBy('path', 'asc');
                    break;
                case 'cdate asc':
                    $accounts = $accounts->orderBy('cdate', 'asc');
                    break;
                case 'cdate desc':
                    $accounts = $accounts->orderBy('cdate', 'desc');
                    break;

            }
        }

        if ($request->excel == 'Y' && Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system'])) {
            $data = $accounts->get();
            Excel::create('accounts', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {
                        $parent = Account::find($a->parent_id);

                        $reports[] = [
                          'Type' => $a->type,
                          'Account.Type' => ($a->pay_method == 'P' ? 'Prepay' : 'Credit'),
                          'Status' => $a->status,
                          'Parent #' => $parent->id,
                          'Parent Name' => $parent->name,
                          'SPP Acct#' => $a->id,
                          'Tax ID' => $a->tax_id,
                          'Created.At' => $a->cdate,
                          'Note' => $a->notes,
                          'TID' => $a->att_tid,
                          'TID2' => $a->att_tid2,
                          'ATT.Dealer.Code' => $a->att_dealer_code,
                          'ATT.Dealer.Notes' => $a->att_dc_notes,
                          'Business.Name' => $a->name,
                          'Address' => $a->address1,
                          'Address2' => $a->address2,
                          'City' => $a->city,
                          'State' => $a->state,
                          'Zip' => $a->zip,
                          'Tel' => $a->office_number,
                          'Contact Name' => $a->contact,
                          'Email' => $a->email,
                          'Tel2' => $a->phone2,
                          'Email2' => $a->email2,
                          'Download.Date' => date("m/d/Y h:i:s A")
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $accounts = $accounts->paginate(15);

        $states = State::orderBy('name', 'asc')->get();
        $store_types = StoreType::orderBy('name', 'asc')->get();
        $vendors = Vendor::all();

        $attbatchfeebase = ATTBatchFeeBase::where('type', 'B')->first();
        $attbatchfeetiers = ATTBatchFeeBase::where('type', 'T')->get();

        $d_spiff_templates = SpiffTemplate::where('account_type', 'D')->orderBy('template')->get();
        $s_spiff_templates = SpiffTemplate::where('account_type', 'S')->orderBy('template')->get();

        $vr_carriers = VRProduct::whereNotNull('carrier')->where('carrier', '<>', '')->groupBy('carrier')->get([
            'carrier'
        ]);

        foreach ($vr_carriers as $v) {
            $v->carrier_key = $v->carrier;
            $v->carrier_key = str_replace('&', '', $v->carrier);
            $v->carrier_key = str_replace(' ', '', $v->carrier_key);
        }

        $vr_products = VRProduct::whereNotNull('carrier')->where('carrier', '<>', '')->where('status', '=', 'A')->get();

//        foreach ($vr_products as $v) {
//            $v->product_key = $v->model;
//            $v->product_key = str_replace('&', '', $v->model);
//            $v->product_key = str_replace(' ', '', $v->product_key);
//        }

        return view('admin.account', [
            'accounts' => $accounts,
            'states' => $states,
            'types' => $this->get_types_filter(),
            'type' => $request->type,
            'name' => $request->name,
            'office_number' => $request->office_number,
            'status' => $request->status,
            'emails' => $request->emails,
            'emails_except' => $request->emails_except,
            'user_id' => $request->user_id,
            'tax_id' => $request->tax_id,
            'id' => $request->id,
            'ids' => $request->ids,
            'ids_except' => $request->ids_except,
            'user_name' => $request->user_name,
            'include_sub_account' => $request->include_sub_account,
            'include_sub_account_name' => $request->include_sub_account_name,
            'include_sub_account_id' => $request->include_sub_account_id,
            'store_types' => $store_types,
            'store_type_ids' => $store_type_ids_arr,
            'state' => $request->state,
            'city' => $request->city,
            'zip' => $request->zip,
            'address1' => $request->address1,
            'address2' => $request->address2,
            'dealer_code' => $request->dealer_code,
            'user_not_created' => $request->user_not_created,
            'rate_plan_id' => $request->rate_plan_id,
            'rate_plan_not_assigned' => $request->rate_plan_not_assigned,
            'product_id' => '',
            'product_name' => '',
            'vendor' => '',
            'vendors' => $vendors,
            'is_c_store' => $request->is_c_store,
            'is_rebates_eligibility' => $request->is_rebates_eligibility,
            'is_d_s_report' => $request->is_d_s_report,
            'is_s_s_report' => $request->is_s_s_report,
            'pay_method' => $request->pay_method,
            'no_bank_info' => $request->no_bank_info,
            'yes_bank_info' => $request->yes_bank_info,
            'ach_routeno' => $request->ach_routeno,
            'ach_acctno' => $request->ach_acctno,
            'no_ach' => $request->no_ach,
            'no_att_code' => $request->no_att_code,
            'no_postpay' => $request->no_postpay,
            'att_tid' => $request->att_tid,
            'att_tid2' => $request->att_tid2,
            'att_ids'   => $request->att_ids,
            'no_att_byos' => $request->no_att_byos,
            'notes' => $request->notes,
            'credit_limit' => $request->credit_limit,
            'allow_cash_limit' => $user_account->allow_cash_limit,
            'wait_for_approve' => $request->wait_for_approve,
            'created_sdate' => $request->created_sdate,
            'created_edate' => $request->created_edate,
            'quick' => $request->quick,
            'order_by'  => $request->order_by,
            'attbatchfeebase' => $attbatchfeebase,
            'attbatchfeetiers' => $attbatchfeetiers,
            'd_spiff_templates' => $d_spiff_templates,
            's_spiff_templates' => $s_spiff_templates,
            'vr_carriers' => $vr_carriers,
            'vr_products' => $vr_products,
            'codes' => $request->codes,
            'is_code' => $request->is_code,
            'code_like' => $request->code_like
        ]);
    }

    public function add_new(Request $request, $p_account_id) {

        $auth_user = Auth::user();
        $auth_account = Account::find($auth_user->account_id);

        $p_account = Account::where('id', $p_account_id)
          ->whereRaw('path like \'' . $auth_account->path . '%\'')
          ->first();

        if (empty($p_account)) {
            return view('errors.404');
        }

        $states = State::orderBy('name', 'asc')->get();
        $store_types = StoreType::orderBy('name', 'asc')->get();
        $vendors = Vendor::all();

        return view("admin.account.create")->with([
            'p_account_id'  => $p_account_id,
            'p_account'     => $p_account,
            'states'        => $states,
            'store_types'   => $store_types,
            'vendors'       => $vendors
        ]);
    }

    public function edit(Request $request, $p_account_id, $account_id = null) {

        $auth_user = Auth::user();
        $auth_account = Account::find($auth_user->account_id);

        if ($account_id !=  $auth_account->id) {
            $p_account = Account::where('id', $p_account_id)
              ->whereRaw('path like \'' . $auth_account->path . '%\'')
              ->first();
        } else {
            $p_account = Account::find($p_account_id);
        }

        if (empty($p_account)) {
            return view('errors.404');
        }

        if (!empty($account_id)) {
            $account = Account::find($account_id);

            if (empty($account) || ($account->id != 100000 && $account->parent_id != $p_account->id)) {
                return view('errors.404');
            }
        } else {
            if ($p_account_id == 100000) {
                $account_id = 100000;
            }

            $account = $p_account;
        }

        $states = State::orderBy('name', 'asc')->get();
        $store_types = StoreType::orderBy('name', 'asc')->get();
        $vendors = Vendor::all();

        $m_account = Account::find($account->master_id);

        $account_shipping_fees = AccountShipFee::where('account_id', $account_id)->get();

        return view("admin.account.edit")->with([
            'p_account_id'  => $p_account_id,
            'p_account'     => $p_account,
            'account_id'    => $account_id,
            'account'       => $account,
            'm_account'     => $m_account,
            'auth_account'  => $auth_account,
            'states'        => $states,
            'store_types'   => $store_types,
            'vendors'       => $vendors,
            'account_shipping_fees' => empty($account_shipping_fees) ? null : $account_shipping_fees
        ]);
    }

    public function send_welcome_email(Request $request) {

        try{

            $account = Account::find($request->account_id);
            $user = User::where('account_id', $account->id)
                    ->whereRaw('lower(email) like ?', ['%'. strtolower($account->email). '%'])
                    ->first();
            if(empty($user)){
                return response()->json([
                    'msg' => 'Can not Find any User with Account : '.$account->id. ' and Email : ' . $account->email
                ]);
            }

            $params = new \stdClass();

            $params->business_name = $account->name;
            $params->biz_license = $account->tax_id;
            $params->first_name = $account->contact;
            $params->last_name = '';
            $params->phone = $account->office_number;
            $params->email = $account->email;
            $params->address1 = $account->address1;
            $params->address2 = $account->address2;
            $params->city = $account->city;
            $params->state = $account->state;
            $params->zip = $account->zip;
            $params->store_type = '';
            $params->sales_name = '';
            $params->sales_phone = '';
            $params->sales_email = '';
            $params->promo_code = '';

            $params->user_name = $user->user_id;
            $params->password = '';
            $params->account_type = $account->pay_method;
            $params->added_msg = $request->welcome_email;

            // Send Email here!
            if (getenv('APP_ENV') == 'production') {
                $email = ['register@softpayplus.com', $account->email];
            } else {
                $email = ['it@jjonbp.com', $account->email];
            }

            Mail::to($email)
                ->bcc('it@perfectmobileinc.com')
                ->send(new ApplySubagent($params, $account));

            // Send to CC email as well
            if(!empty($request->cc_email)) {
                Mail::to($request->cc_email)
                    ->send(new ApplySubagent($params, $account));

                if($account->pay_method == 'C'){
                    Mail::to($request->cc_email)->send(new CheckList($request));
                }else{
                    Mail::to($request->cc_email)->send(new HowToUsePortal($request));
                }
            }

            if($account->pay_method == 'C'){
                Mail::to($email)->send(new CheckList($request));
            }else{
                Mail::to($email)->send(new HowToUsePortal($request));
            }

            return response()->json([
                'msg' => '',
                'product_id' => '',
                'product_name' => '',
                'vendor' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

    public function lookup(Request $request) {

        $sdate = Carbon::today()->addDays(-90);
        $edate = Carbon::today();

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $data = [];
        $matched_count = 0;
        $dupmatched_count = 0;
        $notmatched_count = 0;
        if (!empty($request->acct_ids)) {
            $acct_ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);

            foreach ($acct_ids as $acct_id) {

                $acct_id = trim($acct_id);

                if(strlen($acct_id) != 6){
                    continue;
                }

                $child_account_ids = Account::where('path', 'like', "%$acct_id%")->get();

                if(empty($child_account_ids)){
                    $notmatched_count++;
                    if (empty($request->matched) || $request->matched == 'N') {
                        $data[] = [
                            'key' => $acct_id,
                            'value' => ''
                        ];
                    }
                }else {
                    foreach ($child_account_ids as $child_account_id) {

                        $account = Account::find($child_account_id->id);

                        $matched_count++;
                        if (empty($request->matched) || $request->matched == 'Y') {
                            $dupmatched_count++;

                            if (empty($request->product)) {
                                $trx_count = Transaction::where('account_id', $account->id)->where('status', 'C')
                                    ->where('cdate', '>=', $sdate)
                                    ->where('cdate', '<=', $edate)
                                    ->count();
                            } else {
                                $trx_count = Transaction::where('account_id', $account->id)->where('status', 'C')
                                    ->whereRaw("product_id in (select id from product where lower(name) like '%" . strtolower($request->product) . "%')")
                                    ->where('cdate', '>=', $sdate)
                                    ->where('cdate', '<=', $edate)
                                    ->count();
                            }

                            if (empty($request->has_transaction)
                                || ($request->has_transaction == 'Y' && $trx_count > 0)
                                || ($request->has_transaction == 'N' && $trx_count < 1)) {

                                $account->trx_count = $trx_count;
                                $account->parent = Account::find($account->parent_id);
                                $data[] = [
                                    'key' => $acct_id,
                                    'value' => $account
                                ];
                            }
                        } else{
                            $notmatched_count++;
                            if (empty($request->matched) || $request->matched == 'N') {
                                $data[] = [
                                    'key' => $acct_id,
                                    'value' => ''
                                ];
                            }
                        }

                    }
                }
            }
        }

        if (!empty($request->emails)) {
            $emails = preg_split('/[\ \n\,]+/', $request->emails);
            foreach ($emails as $email) {
                $email = trim($email);

                $accounts = Account::whereRaw("(lower(IfNull(email,''))='" . strtolower($email) . "' or lower(IfNull(email2,''))='" . strtolower($email) .
                    "' or id in (select account_id from users where lower(IfNull(email,''))='" . strtolower($email) . "'))")->get();
                if (empty($accounts) || count($accounts) < 1) {
                    $notmatched_count++;
                    if (empty($request->matched) || $request->matched == 'N'){
                        $data[] = [
                          'key' => $email,
                          'value' => ''
                        ];
                    }
                } else {
                    $matched_count ++;
                    if (empty($request->matched) || $request->matched == 'Y') {
                        foreach ($accounts as $account) {
                            $dupmatched_count ++;
                            if (empty($request->product)) {
                                $trx_count = Transaction::where('account_id', $account->id)->where('status', 'C')
                                  ->where('cdate', '>=', $sdate)
                                  ->where('cdate', '<=', $edate)
                                  ->count();
                            } else {
                                $trx_count = Transaction::where('account_id', $account->id)->where('status', 'C')
                                  ->whereRaw("product_id in (select id from product where lower(name) like '%" . strtolower($request->product) . "%')")
                                  ->where('cdate', '>=', $sdate)
                                  ->where('cdate', '<=', $edate)
                                  ->count();
                            }

                            if (empty($request->has_transaction)
                                || ($request->has_transaction == 'Y' && $trx_count > 0)
                                || ($request->has_transaction == 'N' && $trx_count < 1)) {

                                $account->trx_count = $trx_count;
                                $account->parent = Account::find($account->parent_id);
                                $data[] = ['key' => $email, 'value' => $account];
                            }
                        }
                    }
                }
            }
        }


        if ($request->excel == 'Y' && Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id, ['admin', 'thomas', 'system'])) {
            Excel::create('accounts', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $aa) {
                        if (empty($aa['value'])) {
                            $reports[] = [
                              'Key' => $aa['key'],
                              'Acct#' => 'Not Found',
                              'Acct.Type' => '',
                              'Business.Name' => '',
                              'Parent#' => '',
                              'Parent.Type' => '',
                              'Parent.Name' => '',
                              'Address' => '',
                              'Contact.Name' => '',
                              'Office Number' => '',
                              'Phone2' => '',
                              'Email' => '',
                              'Email2' => '',
                              'Tax ID' => '',
                              'Tran.Qty' => '',
                              'ATT TID' => '',
                              'Status' => '',
                              'Created.At' => '',
                              'Download.Date' => date("m/d/Y h:i:s A")
                            ];
                        } else {
                            $a = $aa['value'];
                            $reports[] = [
                                'Key' => $aa['key'],
                                'Acct#' => $a->id,
                                'Acct.Type' => $a->type_name(),
                                'Business.Name' => $a->name,
                                'Parent#' => $a->parent_id,
                                'Parent.Type' => empty($a->parent) ? '' : $a->parent->type_name(),
                                'Parent.Name' => empty($a->parent) ? '' : $a->parent->name,
                                'Address' => $a->address1 . ' ' . $a->address2 . ', ' . $a->city . ', ' . $a->state . ' ' . $a->zip,
                                'Contact.Name' => $a->contact,
                                'Office Number' => $a->office_number,
                                'Phone2' => $a->phone2,
                                'Email' => $a->email,
                                'Email2' => $a->email2,
                                'Tax ID' => $a->tax_id,
                                'Tran.Qty' => $a->trx_count,
                                'ATT TID' => $a->att_tid,
                                'ATT TID2' => $a->att_tid2,
                                'Status' => $a->status,
                                'Created.At' => $a->cdate,
                                'Download.Date' => date("m/d/Y h:i:s A")
                            ];
                        }
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $count = count($data);

        return view('admin.account.lookup', [
            'data'     => $data,
            'matched_count' => $matched_count,
            'notmatched_count' => $notmatched_count,
            'dupmatched_count' => $dupmatched_count,
            'acct_ids' => $request->acct_ids,
            'emails'   => $request->emails,
            'matched'  => $request->matched,
            'sdate'    => $sdate,
            'edate'    => $edate,
            'quick'    => $request->quick,
            'has_transaction' => $request->has_transaction,
            'product'   => $request->product,
            'count'     => $count
        ]);
    }

    public function lookup_new(Request $request) {

        $sdate = Carbon::today();
        $edate = Carbon::today();

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        if (empty($request->type)) {
            $request->type = 'M';
        }

        $join_type_tx = " left ";
        if(!empty($request->has_transaction)){
            if($request->has_transaction == 'Y'){
                $join_type_tx = " inner ";
            }
        }

        if ($request->type == 'S') {

            if ($request->has_transaction == 'N') {
                $query = "
                select a.id, a.name, a.type, a.address1, a.address2, a.city, a.state, a.zip, 
                    a.contact, a.office_number, a.phone2, a.email, a.email2, a.tax_id, a.status, a.cdate, 
                    a.parent_id, a.master_id, 
                    d.name as p_name, d.id as p_id, d.type as p_type,
                    count(distinct a.id) no_of_sub
                from accounts a inner join accounts b on a.master_id = b.id and b.type='M' 
                                inner join accounts d on a.master_id = d.id
                    where not exists ( 
                    select c.account_id from transaction c 
                    where c.account_id = a.id 
                    and c.cdate >= '$sdate'
                    and c.cdate <= '$edate'
                    and c.type ='S' and c.status='C' and c.void_date is null 
                    ";

                if (!empty($request->carrier)) {
                    $result = Product::where('carrier', $request->carrier)->select('id')->get();
                    $prods = [];
                    foreach ($result as $p) {
                        array_push($prods, $p->id);
                    }
                    $condition = '"' . implode('", "', $prods) . '"';
                    $query .= " and c.product_id in ($condition) ";
                }

                if (!empty($request->vendor)) {
                    $query .= " and c.vendor_code = '$request->vendor' ";
                }

                $query = $query . " ) 
                and a.type = 'S'
                ";

                if (!empty($request->emails)) {
                    if($request->emails_except == 'Y'){
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(a.email,'')) not in ($con) and lower(IfNull(a.email2,'')) not in ($con) ) ";
                    } else {
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(a.email,'')) in ($con) or lower(IfNull(a.email2,'')) in ($con) ) ";
                    }
                }

                if (!empty($request->account_name)) {
                    $query .= " and lower(a.name) like '%".strtolower($request->account_name)."%' ";
                }

            }else {
                $query = "
                select a.id, a.name, a.type, a.address1, a.address2, a.city, a.state, a.zip, 
                    a.contact, a.office_number, a.phone2, a.email, a.email2, a.tax_id, a.status, a.cdate, 
                    a.parent_id, a.master_id, 
                    d.name as p_name, d.id as p_id, d.type as p_type,
                    count(distinct a.id) no_of_sub,
                    sum(case c.action when 'Activation' then 1 else 0 end) as act_cnt,
                    sum(case c.action when 'Port-In' then 1 else 0 end) as port_cnt,
                    sum(case c.action when 'RTR' then 1 else 0 end) as rtr_cnt,
                    sum(case c.action when 'PIN' then 1 else 0 end) as pin_cnt,
                    sum(case c.status when 'C' then 1 else 0 end) as total_cnt,
                    count(distinct c.account_id) no_of_tx_sub
                from accounts a inner join accounts b on a.master_id = b.id and b.type='M' 
                                inner join accounts d on a.master_id = d.id
                    $join_type_tx join  transaction c on a.id = c.account_id 
                    and c.cdate >= '$sdate'
                    and c.cdate <= '$edate'
                    and c.type ='S' and c.status='C' and c.void_date is null
                where a.type = 'S'    
                ";

                if (!empty($request->product)) {
                    $query .= " and c.product_id = '$request->product' ";
                }

                if (!empty($request->carrier)) {
                    $result = Product::where('carrier', $request->carrier)->select('id')->get();
                    $prods = [];
                    foreach ($result as $p) {
                        array_push($prods, $p->id);
                    }
                    $condition = '"' . implode('", "', $prods) . '"';
                    $query .= " and c.product_id in ($condition) ";
                }

                if (!empty($request->vendor)) {
                    $query .= " and c.vendor_code = '$request->vendor' ";
                }

                if (!empty($request->emails)) {
                    if($request->emails_except == 'Y'){ // checked
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(a.email,'')) not in ($con) and lower(IfNull(a.email2,'')) not in ($con) ) ";
                    } else {
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(a.email,'')) in ($con) or lower(IfNull(a.email2,'')) in ($con) ) ";
                    }
                }

                if (!empty($request->account_name)) {
                    $query .= " and lower(a.name) like '%".strtolower($request->account_name)."%' ";
                }

            }
            if (!empty($request->acct_ids)) {
                $ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
                $condition = implode(", ", $ids);

                if($request->ids_except == 'Y'){
                    $query = $query . " and a.id not in ( select id from accounts where a.id in ( $condition )
                                            UNION select id from accounts where a.parent_id in ( $condition )
			                                UNION select id from accounts where a.master_id in ( $condition )
                                    ) ";
                }else {
                    $query = $query . " and a.id in ( select id from accounts where a.id in ( $condition )
                                            UNION select id from accounts where a.parent_id in ( $condition )
			                                UNION select id from accounts where a.master_id in ( $condition )
                                    ) ";
                }
            }

        } elseif($request->type == 'M') {

            if ($request->has_transaction == 'N') {

                $query = " 
                select b.id, b.name, b.type, b.address1, b.address2, b.city, b.state, b.zip, 
                    b.contact, b.office_number, b.phone2, b.email, b.email2, b.tax_id, b.status, b.cdate, 
                    b.parent_id, b.master_id, 
                    b.name as p_name, b.id as p_id, b.type as p_type,
                    count(distinct a.id) no_of_sub
                from accounts a inner join accounts b on a.master_id = b.id and b.type ='M'
                where not exists ( 
                    select c.account_id from transaction c 
                    where c.account_id = a.id 
                    and c.cdate >= '$sdate'
                    and c.cdate <= '$edate'
                    and c.type ='S' and c.status='C' and c.void_date is null 
                    ";

                if (!empty($request->product)) {
                    $query .= " and c.product_id = '$request->product' ";
                }

                if (!empty($request->carrier)) {
                    $result = Product::where('carrier', $request->carrier)->select('id')->get();
                    $prods = [];
                    foreach ($result as $p) {
                        array_push($prods, $p->id);
                    }
                    $condition = '"' . implode('", "', $prods) . '"';
                    $query .= " and c.product_id in ($condition) ";
                }

                if (!empty($request->vendor)) {
                    $query .= " and c.vendor_code = '$request->vendor' ";
                }

                $query = $query . " ) 
                and a.type = 'S'
                ";

                if (!empty($request->emails)) {
                    if($request->emails_except == 'Y'){
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) not in ($con) and lower(IfNull(b.email2,'')) not in ($con) ) ";
                    } else {
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) in ($con) or lower(IfNull(email2,'')) in ($con) ) ";
                    }
                }

                if (!empty($request->account_name)) {
                    $query .= " and lower(b.name) like '%".strtolower($request->account_name)."%' ";
                }

            } else {

                $query = "
                    select b.id, b.name, b.type, b.address1, b.address2, b.city, b.state, b.zip, 
                        b.contact, b.office_number, b.phone2, b.email, b.email2, b.tax_id, b.status, b.cdate, 
                        b.parent_id, b.master_id, 
                        b.name as p_name, b.id as p_id, b.type as p_type,
                        count(distinct a.id) no_of_sub,
                        sum(case c.action when 'Activation' then 1 else 0 end) as act_cnt,
                        sum(case c.action when 'Port-In' then 1 else 0 end) as port_cnt,
                        sum(case c.action when 'RTR' then 1 else 0 end) as rtr_cnt,
                        sum(case c.action when 'PIN' then 1 else 0 end) as pin_cnt,
                        sum(case c.status when 'C' then 1 else 0 end) as total_cnt,
                        sum(case a.type when 'S' then 1 else 0 end) as sub_cnt,
                        count(distinct c.account_id) no_of_tx_sub
                    from accounts a inner join accounts b on a.master_id = b.id and b.type ='M'
                    $join_type_tx  join  transaction c on a.id = c.account_id 
                        and c.cdate >= '$sdate'
                        and c.cdate <= '$edate'
                        and c.type ='S' and c.status='C' and c.void_date is null
                    where a.type = 'S'   
                    ";

                if (!empty($request->product)) {
                    $query .= " and c.product_id = '$request->product' ";
                }

                if (!empty($request->carrier)) {
                    $result = Product::where('carrier', $request->carrier)->select('id')->get();
                    $prods = [];
                    foreach ($result as $p) {
                        array_push($prods, $p->id);
                    }
                    $condition = '"' . implode('", "', $prods) . '"';
                    $query .= " and c.product_id in ($condition) ";
                }

                if (!empty($request->vendor)) {
                    $query .= " and c.vendor_code = '$request->vendor' ";
                }

                if (!empty($request->emails)) {
                    if ($request->emails_except == 'Y') { //checked
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) not in ($con) and lower(IfNull(b.email2,'')) not in ($con) ) ";
                    } else { // Non checked
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) in ($con) or lower(IfNull(b.email2,'')) in ($con) ) ";
                    }
                }

                if (!empty($request->account_name)) {
                    $query .= " and lower(b.name) like '%".strtolower($request->account_name)."%' ";
                }

            }

            if (!empty($request->acct_ids)) {
                $ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
                $condition = implode(", ", $ids);

                if ($request->ids_except == 'Y') {
                    $query = $query . " and b.id not in ( $condition ) ";
                } else {
                    $query = $query . " and b.id in ( $condition ) ";
                }
            }

        } elseif ($request->type == 'D') {

            if ($request->has_transaction == 'N') {
                $query = " 
                select b.id, b.name, b.type, b.address1, b.address2, b.city, b.state, b.zip, 
                    b.contact, b.office_number, b.phone2, b.email, b.email2, b.tax_id, b.status, b.cdate, b.parent_id, b.master_id, 
                    d.name as p_name, d.id as p_id,  d.type as p_type,
                    count(distinct a.id) no_of_sub
                from accounts a inner join accounts b on a.parent_id = b.id and b.type ='D'   and a.id != b.id
                                inner join accounts d on b.parent_id = d.id
                    where not exists ( 
                        select  c.account_id
                        from transaction c 
                      where c.account_id = a.id
                        and c.cdate >= '$sdate'
                        and c.cdate <= '$edate'
                        and c.type ='S' and c.status='C' and c.void_date is null 
                    )";

                if (!empty($request->product)) {
                    $query .= " and c.product_id = '$request->product' ";
                }

                if (!empty($request->carrier)) {
                    $result = Product::where('carrier', $request->carrier)->select('id')->get();
                    $prods = [];
                    foreach ($result as $p) {
                        array_push($prods, $p->id);
                    }
                    $condition = '"' . implode('", "', $prods) . '"';
                    $query .= " and c.product_id in ($condition) ";
                }

                if (!empty($request->vendor)) {
                    $query .= " and c.vendor_code = '$request->vendor' ";
                }

                $query = $query . " and a.type = 'S' ";

                if (!empty($request->emails)) {
                    if($request->emails_except == 'Y'){
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) not in ($con) and lower(IfNull(b.email2,'')) not in ($con) ) ";
                    } else {
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) in ($con) or lower(IfNull(b.email2,'')) in ($con) ) ";
                    }
                }

                if (!empty($request->account_name)) {
                    $query .= " and lower(b.name) like '%".strtolower($request->account_name)."%' ";
                }

            } else {

                $query = "
                select b.id, b.name, b.type, b.address1, b.address2, b.city, b.state, b.zip, 
                    b.contact, b.office_number, b.phone2, b.email, b.email2, b.tax_id, b.status, b.cdate, b.parent_id, b.master_id, 
                    d.name as p_name, d.id as p_id,  d.type as p_type,
                    count(distinct a.id) no_of_sub,
                sum(case c.action when 'Activation' then 1 else 0 end) as act_cnt,
                sum(case c.action when 'Port-In' then 1 else 0 end) as port_cnt,
                sum(case c.action when 'RTR' then 1 else 0 end) as rtr_cnt,
                sum(case c.action when 'PIN' then 1 else 0 end) as pin_cnt,
                sum(case c.status when 'C' then 1 else 0 end) as total_cnt,
                count(distinct c.account_id) no_of_tx_sub
                from accounts a inner join accounts b on a.parent_id = b.id and b.type ='D'   and a.id != b.id
                                inner join accounts d on b.parent_id = d.id
                    $join_type_tx join  transaction c on a.id = c.account_id 
                    and c.cdate >= '$sdate'
                    and c.cdate <= '$edate'
                    and c.type ='S' and c.status='C' and c.void_date is null
                where a.type ='S'    
                ";

                if (!empty($request->product)) {
                    $query .= " and c.product_id = '$request->product' ";
                }

                if (!empty($request->carrier)) {
                    $result = Product::where('carrier', $request->carrier)->select('id')->get();
                    $prods = [];
                    foreach ($result as $p) {
                        array_push($prods, $p->id);
                    }
                    $condition = '"' . implode('", "', $prods) . '"';
                    $query .= " and c.product_id in ($condition) ";
                }

                if (!empty($request->vendor)) {
                    $query .= " and c.vendor_code = '$request->vendor' ";
                }

                if (!empty($request->emails)) {
                    if($request->emails_except == 'Y'){
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'')) not in ($con) and lower(IfNull(b.email2,'') not in ($con) ) ";
                    } else {
                        $emails = preg_split('/[\ \r\n\,]+/', $request->emails);
                        $con = sprintf(" '%s' ", implode("', '", $emails));
                        $con = strtolower($con);
                        $query = $query . " and ( lower(IfNull(b.email,'') in ($con) or lower(IfNull(b.email2,'') in ($con) ) ";
                    }
                }

                if (!empty($request->account_name)) {
                    $query .= " and lower(b.name) like '%".strtolower($request->account_name)."%' ";
                }

            }

            if (!empty($request->acct_ids)) {
                $ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
                $condition = implode(", ", $ids);
                if($request->ids_except == 'Y'){
                    $query = $query . " and b.id not in ( select id from accounts where b.id in ( $condition ) " .
                        "         UNION select id from accounts where parent_id in ( $condition ) )";
                }else {
                    $query = $query . " and b.id in ( select id from accounts where b.id in ( $condition ) " .
                        "         UNION select id from accounts where parent_id in ( $condition ) )";
                }
            }
        }

        $query = $query . "
            group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21 ";

        $query = $query ." order by 1,2,3 ";

        if ($request->excel == 'Y') {
            if($request->matched == 'N'){
                $data = DB::select($query);
                $result_ids = [];
                $nomatch =[];
                foreach ($data as $d){
                    array_push($result_ids, $d->id);
                }
                if(!empty($request->acct_ids)) {
                    $nomatch = array_diff($ids, $result_ids);
                }
                Excel::create('lookup', function($excel) use ($nomatch) {
                    $data = $nomatch;
                    $excel->sheet('Sheet 1', function ($sheet) use ($data) {
                        $reports = [];
                        foreach ($data as $a) {
                            $reports[] = [
                                'ID' => $a
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }else {
                $data = DB::select($query);
                Excel::create('lookup', function ($excel) use ($data) {
                    $excel->sheet('reports', function ($sheet) use ($data) {
                        $reports = [];
                        foreach ($data as $a) {
                            $reports[] = [
                                'Parent.ID' => $a->p_id,
                                'Parent.Name' => $a->p_name,
                                'ID' => $a->id,
                                'Name' => $a->name,
                                'Type' => $a->type,
                                'Address' => $a->address1 . ','  .$a->address2 . ','. $a->city . ',' .  $a->state . ',' . $a->zip,
                                'Contact.Name' => $a->contact,
                                'Office.Name' => $a->office_number,
                                'Phone2' => $a->phone2,
                                'Email' => $a->email,
                                'Email2' => $a->email2,
                                'Tax.ID' => $a->tax_id,
                                'Act.Qty' => $a->act_cnt,
                                'PortIn.Qty' => $a->port_cnt,
                                'RTR.Qty' => $a->rtr_cnt,
                                'PIN.Qty' => $a->pin_cnt,
                                'Total.Qty' => $a->act_cnt + $a->port_cnt + $a->rtr_cnt + $a->pin_cnt,
                                'Status' => $a->status,
                                'List' => $a->no_of_sub,
                                'Created.By' => $a->cdate
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }
        }

        $data = DB::select($query);

        $result_ids = [];
        $nomatch =[];
        $num_nomatch = 0;
        foreach ($data as $d){
            array_push($result_ids, $d->id);
        }
        if(!empty($request->acct_ids)) {
            $nomatch = array_diff($ids, $result_ids);
            $num_nomatch = sizeof($nomatch);
        }

        $products   = Product::get();
        $carriers   = Carrier::get();
        $vendors    = Vendor::where('status', 'A')->get();

        return view('admin.account.lookup_new', [
            'data'     => $data,
            'types' => $this->get_types_filter(),
            'type' => $request->type,
            'matched_count' => 0,
            'notmatched_count' => 0,
            'dupmatched_count' => 0,
            'acct_ids' => $request->acct_ids,
            'ids_except' => $request->ids_except,
            'include_sub_account' => $request->include_sub_account,
            'matched'  => $request->matched,
            'sdate'    => $sdate->format('Y-m-d'),
            'edate'    => $edate->format('Y-m-d'),
            'quick'    => $request->quick,
            'has_transaction' => $request->has_transaction,
            'products'  => $products,
            'product'   => $request->product,
            'carriers'  => $carriers,
            'carrier'   => $request->carrier,
            'vendors'   => $vendors,
            'account_name' => $request->account_name,
            'emails'    => $request->emails,
            'emails_except' => $request->emails_except,
            'vendor'    => $request->vendor,
            'nomatch'  => $nomatch,
            'num_nomatch' => $num_nomatch
        ]);
    }

    public function get_types_filter() {
        $type = Auth::user()->account_type;
        switch ($type) {
            case 'L':
                return [
                    ['code' => 'L', 'name' => 'Root'],
                    ['code' => 'M', 'name' => 'Master'],
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent']
                ];
            case 'M':
                return [
                    ['code' => 'M', 'name' => 'Master'],
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent']
                ];
            case 'D':
                return [
                    ['code' => 'D', 'name' => 'Distributor'],
                    ['code' => 'S', 'name' => 'Sub-Agent']
                ];
        }
    }

    public function getRatePlans(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'parent_id' => 'required',
                'type' => ''
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

            $rates_plans = RatePlan::where('owner_id', $request->parent_id)
                ->where('type', $request->type)
                ->get();

//            $auth_account = Account::find(Auth::user()->account_id);

            if ($request->parent_id == 100000) {
                $spiff_templates = SpiffTemplate::where('account_type', $request->type)
                  ->orderBy('template', 'asc')
                  ->get();
            } else {
                $account = Account::find($request->parent_id);

                $spiff_templates = SpiffTemplate::whereRaw('id in (select template_id from spiff_template_owner where account_id = ' . $account->id . ')')
                  ->where('account_type', $request->type)
                  ->orderBy('template', 'asc')
                  ->get();
            }

            return response()->json([
                'msg' => '',
                'rate_plans' => $rates_plans,
                'spiff_templates' => $spiff_templates,
                'is_my_account' => 'N'
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function getParentInfo(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $parent = Account::find($request->id);
            if (empty($parent)) {
                return response()->json([
                    'msg' => 'Invalid parent ID provided'
                ]);
            }

            switch ($parent->type) {
                case 'L':
                    $types = [
                        ['code' => 'M', 'name' => 'Master']
                    ];
                    break;
                case 'M':
                    $types = [
                        ['code' => 'D', 'name' => 'Distributor'],
                        //['code' => 'A', 'name' => 'Agent'],
                        ['code' => 'S', 'name' => 'Sub-Agent']
                    ];
                    break;
                case 'D':
                    $types = [
                        //['code' => 'A', 'name' => 'Agent'],
                        ['code' => 'S', 'name' => 'Sub-Agent']
                    ];
                    break;
                case 'A':
                    $types = [
                        ['code' => 'S', 'name' => 'Sub-Agent']
                    ];
                    break;
                default:
                    return response()->json([
                        'msg' => 'Sub-Agent cannot have child account!'
                    ]);
            }

            return response()->json([
                'msg' => '',
                'parent' => $parent,
                'is_my_account' => 'N',
                'types' => $types
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function getAccountInfo(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $account = Account::find($request->id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid account ID provided'
                ]);
            }

            $parent = Account::find($account->parent_id);
            if ($account->type != 'L' && empty($parent)) {
                return response()->json([
                    'msg' => 'Invalid parent ID provided'
                ]);
            }

            $files = AccountFile::where('account_id', $account->id)
                ->select('id', 'type', 'account_id', 'file_name', 'signed', 'locked', 'created_by', 'cdate')
                ->get();

            $att_files = AccountFileAtt::where('account_id', $account->id)
                ->select('id', 'type', 'account_id', 'file_name', 'signed', 'locked', 'created_by', 'cdate')
                ->get();

            switch ($account->type) {
                case 'L':
                    $types = [
                        ['code' => 'L', 'name' => 'Perfect Mobile']
                    ];

                    $rate_plan_types = [
                        ['code' => 'M', 'name' => 'Master']
                    ];

                    break;
                case 'M':
                    $types = [
                        ['code' => 'M', 'name' => 'Master']
                    ];

                    $rate_plan_types = [
                        ['code' => 'D', 'name' => 'Distributor'],
                        ['code' => 'S', 'name' => 'Sub-Agent']
                    ];
                    break;
                case 'D':
                    $types = [
                        ['code' => 'D', 'name' => 'Distributor']
                    ];

                    $rate_plan_types = [
                        ['code' => 'S', 'name' => 'Sub-Agent']
                    ];
                    break;
                /*case 'A':
                    $types = [
                        ['code' => 'A', 'name' => 'Agent']
                    ];
                    break;*/
                case 'S':
                    $types = [
                        ['code' => 'S', 'name' => 'Sub-Agent']
                    ];

                    $rate_plan_types = [];
                    break;
                default:
                    return response()->json([
                        'msg' => 'Invalid account type: ' . $account->type
                    ]);
            }

            $rates_plans = RatePlan::where('owner_id', $account->parent_id)
                ->where('type', $account->type);
                //->get();

            if (Auth::user()->account_id == $account->id) {
                $rates_plans->where('id', $account->rate_plan_id);
            }

            $rates_plans = $rates_plans->get();

            $owned_plans = RatePlan::where('owner_id', $account->id)
                ->get();

            if (count($owned_plans) > 0) {
                foreach ($owned_plans as $o) {
                    $o->last_updated = $o->last_updated;
                    $o->type_img = Helper::get_hierarchy_img($o->type);
                }
            }

            $auth_account = Account::find(Auth::user()->account_id);

            if ($account->id == 100000 || $account->parent_id == 100000) {
                $spiff_templates = SpiffTemplate::where('account_type', $account->type)
                  ->orderBy('template', 'asc')
                  ->get();
            } else {
                $spiff_templates = SpiffTemplate::whereRaw('id in (select template_id from spiff_template_owner where account_id = ' . $account->parent_id . ')')
                  ->where('account_type', $account->type)
                  ->orderBy('template', 'asc')
                  ->get();
            }

            $is_my_account = 'N';
            if ($auth_account->id == $account->id) {
                $is_my_account = 'Y';
            }

            $account->store_types = AccountStoreType::where('account_id', $account->id)->get();
            switch ($account->type) {
                case 'M':
                    $account->balance = PaymentProcessor::get_master_limit($account->id);
                    break;
                case 'D':
                    $account->balance = PaymentProcessor::get_dist_limit($account->id);
                    break;
                case 'S':
                    $account->balance = PaymentProcessor::get_limit($account->id);
                    break;
                default:
                    $account->balance = 0;
                    break;
            }

            $can_edit_credit_info = Permission::can($request->path(), 'edit-credit-info');
            Helper::log('### edit-credit-info ###', $can_edit_credit_info);
            if ($account->id == Auth::user()->account_id) {
                Helper::log('### edit-my-info ###', [
                    'edit-my-info' => Permission::can($request->path(), 'edit-my-info'),
                    'me' => Auth::user()->account_id,
                    'account_id' => $account->id
                ]);
                $can_edit_credit_info &= Permission::can($request->path(), 'edit-my-info');
            }

            $can_edit_status = true;
            if ($account->type == 'S' && $account->pay_method == 'C' && $account->status == 'P') {
                $can_edit_status = Permission::can($request->path(), 'change-pre-auth-status');
            }

            if ($account->status == 'F') {
                $can_edit_status &= Permission::can($request->path(), 'change-failed-payment-status');
            }

            if ($account->id == Auth::user()->account_id) {
                $can_edit_status &= Permission::can($request->path(), 'edit-my-info');
            }

            if (Auth::user()->account_type != 'L') {
                if (!empty($account->att_tid)) {
                    $account->att_tid = "****" . substr($account->att_tid, 4);
                }
            }


            $account->gen_a_fee_l = \App\Model\GenFee::where('account_id', $account->id)->where('fee_type', 'A')->where('account_type', 'L')->sum('fee_amount');
            if (empty($account->gen_a_fee_l)) {
                $account->gen_a_fee_l = '';
            }
            $account->gen_a_fee_m = \App\Model\GenFee::where('account_id', $account->id)->where('fee_type', 'A')->where('account_type', 'M')->sum('fee_amount');
            if (empty($account->gen_a_fee_m)) {
                $account->gen_a_fee_m = '';
            }
            $account->gen_a_fee_d = \App\Model\GenFee::where('account_id', $account->id)->where('fee_type', 'A')->where('account_type', 'D')->sum('fee_amount');
            if (empty($account->gen_a_fee_d)) {
                $account->gen_a_fee_d = '';
            }

            $account->gen_p_fee_l = \App\Model\GenFee::where('account_id', $account->id)->where('fee_type', 'R')->where('account_type', 'L')->sum('fee_amount');
            if (empty($account->gen_p_fee_l)) {
                $account->gen_p_fee_l = '';
            }
            $account->gen_p_fee_m = \App\Model\GenFee::where('account_id', $account->id)->where('fee_type', 'R')->where('account_type', 'M')->sum('fee_amount');
            if (empty($account->gen_p_fee_m)) {
                $account->gen_p_fee_m = '';
            }
            $account->gen_p_fee_d = \App\Model\GenFee::where('account_id', $account->id)->where('fee_type', 'R')->where('account_type', 'D')->sum('fee_amount');
            if (empty($account->gen_p_fee_d)) {
                $account->gen_p_fee_d = '';
            }

            $default_spiff = SpiffTemplate::whereRaw('id in (select template_id from spiff_template_owner where account_id = ' . $account->id . ')')
                ->where('account_type', 'S')
                ->orderBy('template', 'asc')
                ->get();

            $default_sub_spiff = DefaultSubSpiff::where('acct_id', $account->id)->first();

            $account_shipping_fees = AccountShipFee::where('account_id', $account->id)->get();

            return response()->json([
                'msg' => '',
                'parent' => $parent,
                'account' => $account,
                'types' => $types,
                'files' => $files,
                'att_files' => $att_files,
                'rate_plans' => $rates_plans,
                'owned_plans' => $owned_plans,
                'rate_plan_types' => $rate_plan_types,
                'login_account_id' => Auth::user()->account_id,
                'can_edit_credit_info' => $can_edit_credit_info ? 'Y' : 'N',
                'can_edit_status' => $can_edit_status ? 'Y' : 'N',
                'spiff_templates' => $spiff_templates,
                'is_my_account' => $is_my_account,
                'default_spiff' => $default_spiff,
                'default_sub_spiff' => empty($default_sub_spiff) ? null : $default_sub_spiff,
                'account_shipping_fees' => empty($account_shipping_fees) ?  null : $account_shipping_fees
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ]);
        }
    }

    public function createAddressCheck(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'parent_id' => 'required',
                'type' => 'required',
                'address1' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $ret = DB::select("
                select concat(id, ' - ' , name) as name
                from accounts
                where trim(lower(address1)) = :address1
                and trim(lower(ifnull(address2, ''))) = :address2
                and trim(lower(city)) = :city
                and trim(lower(state)) = :state
                and trim(lower(zip)) = :zip
                and parent_id = :parent_id
                and type = :type
            ", [
                'address1' => trim(strtolower($request->address1)),
                'address2' => trim(strtolower($request->address2)),
                'city' => trim(strtolower($request->city)),
                'state' => trim(strtolower($request->state)),
                'zip' => trim(strtolower($request->zip)),
                'parent_id' => $request->parent_id,
                'type' => $request->type
            ]);

            if (count($ret) > 0) {
                return response()->json([
                    'msg' => 'Account ' . $ret[0]->name . ' has similar address. Are you sure to continue?'
                ]);
            }

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function create(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                $this->output('You are not authorized to modify any information', 'edit');
            }

            $v = Validator::make($request->all(), [
                'name' => 'required',
                'type' => 'required',
                'tax_id' => 'required',
                'contact' => 'required',
                'parent_id' => 'required',
                'contact' => 'required',
                'office_number' => 'required|regex:/^\d{10}$/',
                'email' => 'required|email',
                'status' => 'required',
                'address1' => 'required',
                'address2' => '',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'pay_method' => 'required|in:P,C',
                'ach_bank' => 'required_if:pay_method,C',
                'ach_holder' => 'required_if:pay_method,C',
                'ach_routeno' => 'required_if:pay_method,C|nullable|regex:/^\d{9}$/',
                'ach_acctno' => 'required_if:pay_method,C|nullable|regex:/^\d{1,20}$/',
                'credit_limit' => 'required_if:pay_method,C',

                'rate_plan_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                $this->output($msg);
            }

            /*$account = Account::where('tax_id', $request->tax_id)->first();
            if (!empty($account)) {
                $this->output('Duplicated Tax ID already in the system');
            }*/

            $parent = Account::find($request->parent_id);
            if ($request->type != 'L' && empty($parent)) {
                $this->output('Invalid parent ID provided');
            }

            if ($request->type == 'S' && empty($request->store_type_id)) {
                $this->output('Please select store type');
            }

            $account = new Account;
            $account->type = $request->type;
            $account->name = $request->name;
            $account->tax_id = $request->tax_id;
            $account->parent_id = isset($parent->id) ? $parent->id : null;
            if ($account->type != 'L' && $account->parent_id == null) {
                $this->output('Parent ID is null for non-root account');
            }

            $account->contact = $request->contact;
            $account->office_number = $request->office_number;
            $account->email = strtolower($request->email);
            if (!empty($request->phone2)) {
                $account->phone2 = $request->phone2;
            }
            if (!empty($request->email2)) {
                $account->email2 = strtolower($request->email2);
            }
            $account->status = $request->status;

            $account->address1 = $request->address1;
            $account->address2 = $request->address2;
            $account->city = $request->city;
            $account->state = $request->state;
            $account->zip  = $request->zip;

            if (in_array(Auth::user()->account_type, ['L'])) {
                $account->notes = $request->notes;

                $account->act_lyca      = empty($request->act_lyca) ? 'N' : 'Y';
                $account->act_h2o       = empty($request->act_h2o) ? 'N' : 'Y';
                $account->act_att       = empty($request->act_att) ? 'N' : 'Y';
                $account->att_allow_byos = empty($request->att_allow_byos) ? 'N' : 'Y';
                $account->att_byos_act_month = $request->att_byos_act_month;
                $account->lyca_min_month    = $request->lyca_min_month;
                $account->h2o_min_month     = $request->h2o_min_month;
                $account->freeup_min_month  = $request->freeup_min_month;
                $account->gen_min_month     = $request->gen_min_month;
                $account->liberty_min_month = $request->liberty_min_month;
                $account->boom_min_month    = $request->boom_min_month;


                $account->act_freeup    = empty($request->act_freeup) ? 'N' : 'Y';
                $account->act_gen       = empty($request->act_gen) ? 'N' : 'Y';
                $account->act_liberty   = empty($request->act_liberty) ? 'N' : 'Y';
                $account->act_boom   = empty($request->act_boom) ? 'N' : 'Y';
                $account->no_ach = empty($request->no_ach) ? 'N' : 'Y';
                $account->no_postpay = empty($request->no_postpay) ? 'N' : 'Y';
                $account->esn_swap = empty($request->esn_swap) ? 'Y' : $request->esn_swap;
                $account->esn_swap_num = empty($request->esn_swap_num) ? '7' : $request->esn_swap_num;

            } else {
                $account->act_lyca      = empty($request->act_lyca) ? 'N' : 'Y';
                $account->act_h2o       = empty($request->act_h2o) ? 'N' : 'Y';
                $account->act_att       = empty($request->act_att) ? 'N' : 'Y';
                $account->act_freeup    = empty($request->act_freeup) ? 'N' : 'Y';
                $account->act_gen       = empty($request->act_gen) ? 'N' : 'Y';
                $account->act_liberty   = empty($request->act_liberty) ? 'N' : 'Y';
                $account->act_boom      = empty($request->act_boom) ? 'N' : 'Y';
                $account->no_ach        = empty($request->no_ach) ? 'N' : 'Y';
                $account->no_postpay    = empty($request->no_postpay) ? 'N' : 'Y';
            }

            $account->spiff_template = $request->spiff_template;

//            $account->att_spiff_template = $request->att_spiff_template;
//            $account->h2o_spiff_template = $request->h2o_spiff_template;
//            $account->freeup_spiff_template = $request->freeup_spiff_template;
//            $account->gen_spiff_template = $request->gen_spiff_template;
//            $account->lyca_spiff_template = $request->lyca_spiff_template;
//            $account->liberty_spiff_template = $request->liberty_spiff_template;
//            $account->boom_spiff_template = $request->boom_spiff_template;

            if (in_array(Auth::user()->account_type, ['L', 'M', 'D'])) {
                $account->dealer_code = $request->dealer_code;
                $account->dealer_password = $request->dealer_password;
            }

            $account->created_by = Auth::user()->user_id;
            $account->cdate = Carbon::now();
            //$account->store_type_id = $request->store_type_id;

            ### c_store ###
            $account->c_store = empty($request->c_store) ? 'N' : $request->c_store;
            $account->rebates_eligibility = empty($request->rebates_eligibility) ? 'Y' : $request->rebates_eligibility;
            $account->show_discount_setup_report = empty($request->show_discount_setup_report) ? 'N' : $request->show_discount_setup_report;
            $account->show_spiff_setup_report = empty($request->show_spiff_setup_report) ? 'N' : $request->show_spiff_setup_report;

            ### wallet ###
            $account->pay_method = $request->pay_method;
            $account->ach_bank = $request->ach_bank;
            $account->ach_holder = $request->ach_holder;
            $account->ach_routeno = $request->ach_routeno;
            $account->ach_acctno = $request->ach_acctno;
            $account->credit_limit = $request->credit_limit;
            $account->allow_cash_limit = $request->allow_cash_limit;

            ### $100 when Master create a Dist (9/15/20) ###
            if(Auth::user()->account_type == 'M' && $account->type == 'D'){
                $account->min_ach_amt = 100;
            }else {
                $account->min_ach_amt = empty($request->min_ach_amt) ? 0 : $request->min_ach_amt;
            }

            $account->rate_plan_id = $request->rate_plan_id;

            $account->ach_tue = $request->ach_tue;
            $account->ach_wed = $request->ach_wed;
            $account->ach_thu = $request->ach_thu;
            $account->ach_fri = $request->ach_fri;

            if (Auth::user()->account_type == 'D' && $account->type == 'S' && $account->pay_method == 'C') {
                $account->status = 'P';
            }

            $account->save();

            # master ID
            if ($account->type == 'M') {
                $account->master_id = $account->id;
            } else {
                $account->master_id = $parent->master_id;
            }

            $account->path = (isset($parent->path) ? $parent->path : '') . $account->id;
            $account->save();

            if (Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id,['admin', 'thomas' , 'system'])) {
                if (!empty($request->gen_processing_fee_l)) {
                    $genfee = new GenFee();
                    $genfee->account_id = $account->id;
                    $genfee->account_type = 'L';
                    $genfee->fee_type = 'R';
                    $genfee->fee_amount = $request->gen_processing_fee_l;
                    $genfee->cdate = Carbon::now();
                    $genfee->save();
                }
                if (!empty($request->gen_processing_fee_m)) {
                    $genfee = new GenFee();
                    $genfee->account_id = $account->id;
                    $genfee->account_type = 'M';
                    $genfee->fee_type = 'R';
                    $genfee->fee_amount = $request->gen_processing_fee_m;
                    $genfee->cdate = Carbon::now();
                    $genfee->save();
                }
                if (!empty($request->gen_processing_fee_d)) {
                    $genfee = new GenFee();
                    $genfee->account_id = $account->id;
                    $genfee->account_type = 'D';
                    $genfee->fee_type = 'R';
                    $genfee->fee_amount = $request->gen_processing_fee_d;
                    $genfee->cdate = Carbon::now();
                    $genfee->save();
                }

                if (!empty($request->gen_activation_fee_l)) {
                    $genfee = new GenFee();
                    $genfee->account_id = $account->id;
                    $genfee->account_type = 'L';
                    $genfee->fee_type = 'A';
                    $genfee->fee_amount = $request->gen_activation_fee_l;
                    $genfee->cdate = Carbon::now();
                    $genfee->save();
                }
                if (!empty($request->gen_activation_fee_m)) {
                    $genfee = new GenFee();
                    $genfee->account_id = $account->id;
                    $genfee->account_type = 'M';
                    $genfee->fee_type = 'A';
                    $genfee->fee_amount = $request->gen_activation_fee_m;
                    $genfee->cdate = Carbon::now();
                    $genfee->save();
                }
                if (!empty($request->gen_activation_fee_d)) {
                    $genfee = new GenFee();
                    $genfee->account_id = $account->id;
                    $genfee->account_type = 'D';
                    $genfee->fee_type = 'A';
                    $genfee->fee_amount = $request->gen_activation_fee_d;
                    $genfee->cdate = Carbon::now();
                    $genfee->save();
                }
            }

            $file_name_array = Helper::get_file_types();

            foreach ($file_name_array as $key) {
                if (Input::hasFile($key) && Input::file($key)->isValid()) {
                    $path = Input::file($key)->getRealPath();

                    Helper::log('### FILE ###', [
                        'key' => $key,
                        'path' => $path
                    ]);

                    $contents = file_get_contents($path);
                    $name = Input::file($key)->getClientOriginalName();

                    $file = new AccountFile;
                    $file->type = $key;
                    $file->account_id = $account->id;
                    $file->data = base64_encode($contents);
                    $file->file_name = $name;
                    $file->created_by = Auth::user()->user_id;
                    $file->cdate = Carbon::now();
                    $file->save();
                }
            }

            AccountStoreType::where('account_id', $account->id)->delete();

            ### store type
            if (is_array($request->store_type_id)) {
                foreach ($request->store_type_id as $o) {
                    $ast = AccountStoreType::where('account_id', $account->id)
                        ->where('store_type_id', $o)
                        ->first();
                    if (empty($ast)) {
                        $ast = new AccountStoreType;
                    }

                    $ast->account_id = $account->id;
                    $ast->store_type_id = $o;
                    $ast->save();
                }
            }

            # Send credit limit update success email to creditlimit@softpayplus.com
            $subject = "New Account Created. (Acct.ID : " . $account->id . ")";
            $msg = "<b>New Account Created</b> <br/><br/>";
            $msg .= "Acct.Type - " . $account->type . "<br/>";
            $msg .= "Acct.ID - " . $account->id . "<br/>";
            $msg .= "Acct.Name - " . $account->name . "<br/>";
            $msg .= "Date - " . $account->cdate . "<br/>";
            $msg .= "By - " . $account->created_by . "<br/>";

            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('creditlimit@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);


            $this->output('Your request has been processed successfully!', 'new', $close_modal = false, $is_error = false, $account->id);


        } catch (\Exception $ex) {
            /*return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);*/
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function update(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                $this->output('You are not authorized to modify any information', 'edit');
            }

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'type' => 'required',
                'tax_id' => 'required_if:type,M,D,A,S',
                'contact' => 'required',
                'parent_id' => 'required_if:type,M,D,A,S',
                'office_number' => 'required|regex:/^\d{10}$/',
                'email' => 'required|email',
                'status' => 'required',
                'address1' => 'required',
                'address2' => '',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'pay_method' => 'required|in:P,C',
                'ach_bank' => 'required_if:pay_method,C',
                'ach_holder' => 'required_if:pay_method,C',
                'ach_routeno' => 'required_if:pay_method,C|nullable|regex:/^\d{9}$/',
                'ach_acctno' => 'required_if:pay_method,C|nullable|regex:/^\d{1,20}$/',
                'credit_limit' => 'required_if:pay_method,C|nullable|numeric',
                'rate_plan_id' => 'required'
            ]);

            if($request->type != 'L') {
                if ($v->fails()) {
                    $msg = '';
                    foreach ($v->messages()->toArray() as $k => $v) {
                        $msg .= (empty($msg) ? '' : "|") . $v[0];
                    }

                    $this->output($msg, 'edit');
                }
            }

            /*$account = Account::where('tax_id', $request->tax_id)
                ->where('id', '!=', $request->id)
                ->first();
            if (!empty($account)) {
                $this->output('Duplicated Tax ID already in the system', 'edit');
            }*/

            $parent = Account::find($request->parent_id);
            if ($request->type != 'L' && empty($parent)) {
                $this->output('Invalid parent ID provided', 'edit');
            }

            $account = Account::find($request->id);
            if (empty($account)) {
                $this->output('Invalid account ID provided', 'edit');
            }


            if ($request->type == 'S' && empty($request->store_type_id)) {
                $this->output('Please select store type', 'edit');
            }

            $account->type = $request->type;
            $account->name = $request->name;
            $account->tax_id = $request->tax_id;
            $account->parent_id = isset($parent->id) ? $parent->id : null;
            if ($account->type != 'L' && $account->parent_id == null) {
                $this->output('Parent ID is null for non-root account', 'edit');
            }

            $account->contact = $request->contact;
            $account->office_number = $request->office_number;
            $account->email = strtolower($request->email);
            if (!empty($request->phone2)) {
                $account->phone2 = $request->phone2;
            }else{
                $account->phone2 = null;
            }
            if (!empty($request->email2)) {
                $account->email2 = strtolower($request->email2);
            }else{
                $account->email2 = null;
            }
            if (!empty($request->sales_email)) {
                $account->sales_email = strtolower($request->sales_email);
            }else{
                $account->sales_email = null;
            }
            $account->status = $request->status;
            $account->address1 = $request->address1;
            $account->address2 = $request->address2;
            $account->city = $request->city;
            $account->state = $request->state;
            $account->zip  = $request->zip;

            if($request->type != 'L') {
                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'Lyca')) {
                    $account->act_lyca = $request->act_lyca;
                }
                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'H2O')) {
                    $account->act_h2o = $request->act_h2o;
                }
                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'AT&T')) {
                    $account->act_att = $request->act_att;
                }
                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'FreeUP')) {
                    $account->act_freeup = $request->act_freeup;
                }
                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'GEN Mobile')) {
                    $account->act_gen = $request->act_gen;
                }

                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'Liberty Mobile')) {
                    $account->act_liberty = $request->act_liberty;
                }

                if (in_array(Auth::user()->account_type, ['L']) || Helper::has_activation_controller_auth(Auth::user()->account_id,
                        'Boom Mobile')) {
                    $account->act_boom = $request->act_boom;
                }
            }

            if (in_array(Auth::user()->account_type, ['L'])) {
                $account->notes = $request->notes;

                $account->att_allow_byos = $request->att_allow_byos;
                $account->att_byos_act_month = $request->att_byos_act_month;
                $account->att_tid       = $request->att_tid;
                $account->att_tid2       = $request->att_tid2;
                $account->att_dealer_code = $request->att_dealer_code;
                $account->att_dc_notes = $request->att_dc_notes;

                $account->lyca_hold_spiff = $request->lyca_hold_spiff;
                $account->h2o_hold_spiff = $request->h2o_hold_spiff;
                $account->freeup_hold_spiff = $request->freeup_hold_spiff;
                $account->gen_hold_spiff = $request->gen_hold_spiff;
                $account->liberty_hold_spiff = $request->liberty_hold_spiff;
                $account->boom_hold_spiff = $request->boom_hold_spiff;

                $account->lyca_min_month = $request->lyca_min_month;
                $account->h2o_min_month = $request->h2o_min_month;
                $account->freeup_min_month = $request->freeup_min_month;
                $account->gen_min_month = $request->gen_min_month;
                $account->liberty_min_month = $request->liberty_min_month;
                $account->boom_min_month = $request->boom_min_month;

                $account->esn_swap = $request->esn_swap;
                $account->esn_swap_num = $request->esn_swap_num;

                $account->no_ach = $request->no_ach;
                $account->no_postpay = $request->no_postpay;
            }

            if (Auth::user()->account_id != $account->id) {
                $account->spiff_template = $request->spiff_template;
//                $account->att_spiff_template = $request->att_spiff_template;
//                $account->h2o_spiff_template = $request->h2o_spiff_template;
//                $account->freeup_spiff_template = $request->freeup_spiff_template;
//                $account->gen_spiff_template = $request->gen_spiff_template;
//                $account->lyca_spiff_template = $request->lyca_spiff_template;
//                $account->liberty_spiff_template = $request->liberty_spiff_template;
//                $account->boom_spiff_template = $request->boom_spiff_template;
            }

            if (in_array(Auth::user()->account_type, ['L', 'M', 'D'])) {
                $account->dealer_code = $request->dealer_code;
                $account->dealer_password = $request->dealer_password;
            }

            $account->modified_by = Auth::user()->user_id;
            $account->mdate = Carbon::now();
            //$account->store_type_id = $request->store_type_id;

            ### begin - wallet ###

            ### from ACH to Prepay, credit limit should be set to 0
            $new_credit_limit = $request->credit_limit;
            if ($request->pay_method == 'P') {
                $new_credit_limit = 0;
            }

            $old_pay_method = $account->pay_method;

            $account->pay_method = $request->pay_method;
            $account->ach_bank = $request->ach_bank;
            $account->ach_holder = $request->ach_holder;
            $account->ach_routeno = $request->ach_routeno;
            $account->ach_acctno = $request->ach_acctno;
            $account->credit_limit = $new_credit_limit;
            $account->allow_cash_limit = $request->allow_cash_limit;
            $account->min_ach_amt = $request->min_ach_amt;

            $account->ach_tue = $request->ach_tue;
            $account->ach_wed = $request->ach_wed;
            $account->ach_thu = $request->ach_thu;
            $account->ach_fri = $request->ach_fri;

            ### check if new rate plan ID has no issue with children ###
            $child_higher_rates = DB::select("
                select
                    f.name as product_name,
                    e.denom,
                    d.rate_plan_id  
                from rate_detail a 
                    inner join accounts b on b.id = :account_id
                    inner join accounts c on c.path like concat(b.path, '%')
                        and b.path != c.path
                    inner join rate_detail d on c.rate_plan_id = d.rate_plan_id 
                        and a.denom_id = d.denom_id
                        and a.action = d.action
                    inner join denomination e on d.denom_id = e.id
                    inner join product f on e.product_id = f.id         
                where a.rate_plan_id = :rate_plan_id
                and a.rates < d.rates
            ", [
                'rate_plan_id' => $request->rate_plan_id,
                'account_id' => $account->id
            ]);

            if (count($child_higher_rates) > 0) {
                $msg = '';
                foreach ($child_higher_rates as $o) {
                    $msg .= 'Children rate plan [' . $o->rate_plan_id . '] has higher rates for ' . $o->product_name . ' $' . $o->denom . "<br/>";
                }

                $this->output($msg, 'edit');
            }

            $account->rate_plan_id = $request->rate_plan_id;
            $account->default_subagent_plan = $request->default_subagent_plan;
            ### end - wallet ###

            $account->c_store = !empty($request->c_store) ? 'Y' : 'N';
            $account->rebates_eligibility = !empty($request->rebates_eligibility) ? 'Y' : 'N';
            $account->show_discount_setup_report = !empty($request->show_discount_setup_report) ? 'Y' : 'N';
            $account->show_spiff_setup_report = !empty($request->show_spiff_setup_report) ? 'Y' : 'N';

            # master ID
            if ($account->type == 'M') {
                $account->master_id = $account->id;
            } else if ($account->type == 'L') {
//                $account->master_id = null;
            } else {
                $account->master_id = $parent->master_id;
            }

            if($account->type != 'L') {
                $account->path = (isset($parent->path) ? $parent->path : '') . $account->id;
            }
            $account->modified_by = Auth::user()->user_id;
            $account->mdate = Carbon::now();
            $account->save();

            $d_spiff = DefaultSubSpiff::where('acct_id', $account->id)->first();
            if($request->default_spiff){
                if($d_spiff){
                    $d_spiff->spiff_id = $request->default_spiff;
                    $d_spiff->mdate = \Carbon\Carbon::now();
                    $d_spiff->update();
                }else{
                    $default_sub_spiff = new DefaultSubSpiff();
                    $default_sub_spiff->acct_id = $account->id;
                    $default_sub_spiff->spiff_id = $request->default_spiff;
                    $default_sub_spiff->cdate = \Carbon\Carbon::now();
                    $default_sub_spiff->save();
                }
            }else{
                if($d_spiff){
                    $d_spiff->delete();
                }
            }

            if (Auth::user()->account_type == 'L' && in_array(Auth::user()->user_id,['admin', 'thomas', 'system'])) {
                if (!empty($request->gen_processing_fee_l) || $request->gen_processing_fee_l == '0') {
                    $genfee = GenFee::where('account_id', $account->id)->where('account_type', 'L')->where('fee_type', 'R')->first();
                    if (empty($genfee)) {
                        $genfee = new GenFee();
                        $genfee->account_id = $account->id;
                        $genfee->account_type = 'L';
                        $genfee->fee_type = 'R';
                        $genfee->fee_amount = $request->gen_processing_fee_l;
                        $genfee->cdate = Carbon::now();
                        $genfee->save();
                    } else {
                        $genfee->fee_amount = $request->gen_processing_fee_l;
                        $genfee->mdate = Carbon::now();
                        $genfee->update();
                    }
                } else {
                    GenFee::where('account_id', $account->id)->where('account_type', 'L')->where('fee_type', 'R')->delete();
                }
                if (!empty($request->gen_processing_fee_m) || $request->gen_processing_fee_m == '0') {
                    $genfee = GenFee::where('account_id', $account->id)->where('account_type', 'M')->where('fee_type', 'R')->first();
                    if (empty($genfee)) {
                        $genfee = new GenFee();
                        $genfee->account_id = $account->id;
                        $genfee->account_type = 'M';
                        $genfee->fee_type = 'R';
                        $genfee->fee_amount = $request->gen_processing_fee_m;
                        $genfee->cdate = Carbon::now();
                        $genfee->save();
                    } else {
                        $genfee->fee_amount = $request->gen_processing_fee_m;
                        $genfee->mdate = Carbon::now();
                        $genfee->update();
                    }
                } else {
                    GenFee::where('account_id', $account->id)->where('account_type', 'M')->where('fee_type', 'R')->delete();
                }
                if (!empty($request->gen_processing_fee_d) || $request->gen_processing_fee_d == '0') {
                    $genfee = GenFee::where('account_id', $account->id)->where('account_type', 'D')->where('fee_type', 'R')->first();
                    if (empty($genfee)) {
                        $genfee = new GenFee();
                        $genfee->account_id = $account->id;
                        $genfee->account_type = 'D';
                        $genfee->fee_type = 'R';
                        $genfee->fee_amount = $request->gen_processing_fee_d;
                        $genfee->cdate = Carbon::now();
                        $genfee->save();
                    } else {
                        $genfee->fee_amount = $request->gen_processing_fee_d;
                        $genfee->mdate = Carbon::now();
                        $genfee->update();
                    }
                } else {
                    GenFee::where('account_id', $account->id)->where('account_type', 'D')->where('fee_type', 'R')->delete();
                }

                if (!empty($request->gen_activation_fee_l) || $request->gen_activation_fee_l == '0') {
                    $genfee = GenFee::where('account_id', $account->id)->where('account_type', 'L')->where('fee_type', 'A')->first();
                    if (empty($genfee)) {
                        $genfee = new GenFee();
                        $genfee->account_id = $account->id;
                        $genfee->account_type = 'L';
                        $genfee->fee_type = 'A';
                        $genfee->fee_amount = $request->gen_activation_fee_l;
                        $genfee->cdate = Carbon::now();
                        $genfee->save();
                    } else {
                        $genfee->fee_amount = $request->gen_activation_fee_l;
                        $genfee->mdate = Carbon::now();
                        $genfee->update();
                    }
                } else {
                    GenFee::where('account_id', $account->id)->where('account_type', 'L')->where('fee_type', 'A')->delete();
                }
                if (!empty($request->gen_activation_fee_m) || $request->gen_activation_fee_m == '0' ) {
                    $genfee = GenFee::where('account_id', $account->id)->where('account_type', 'M')->where('fee_type', 'A')->first();
                    if (empty($genfee)) {
                        $genfee = new GenFee();
                        $genfee->account_id = $account->id;
                        $genfee->account_type = 'M';
                        $genfee->fee_type = 'A';
                        $genfee->fee_amount = $request->gen_activation_fee_m;
                        $genfee->cdate = Carbon::now();
                        $genfee->save();
                    } else {
                        $genfee->fee_amount = $request->gen_activation_fee_m;
                        $genfee->mdate = Carbon::now();
                        $genfee->update();
                    }
                } else {
                    GenFee::where('account_id', $account->id)->where('account_type', 'M')->where('fee_type', 'A')->delete();
                }
                if (!empty($request->gen_activation_fee_d) || $request->gen_activation_fee_d == '0') {
                    $genfee = GenFee::where('account_id', $account->id)->where('account_type', 'D')->where('fee_type', 'A')->first();
                    if (empty($genfee)) {
                        $genfee = new GenFee();
                        $genfee->account_id = $account->id;
                        $genfee->account_type = 'D';
                        $genfee->fee_type = 'A';
                        $genfee->fee_amount = $request->gen_activation_fee_d;
                        $genfee->cdate = Carbon::now();
                        $genfee->save();
                    } else {
                        $genfee->fee_amount = $request->gen_activation_fee_d;
                        $genfee->mdate = Carbon::now();
                        $genfee->update();
                    }
                } else {
                    GenFee::where('account_id', $account->id)->where('account_type', 'D')->where('fee_type', 'A')->delete();
                }
            }

            $file_name_array = Helper::get_file_types();

            foreach ($file_name_array as $key) {
                if (Input::hasFile($key) && Input::file($key)->isValid()) {
                    $path = Input::file($key)->getRealPath();

                    Helper::log('### FILE ###', [
                        'key' => $key,
                        'path' => $path
                    ]);

                    $contents = file_get_contents($path);
                    $name = Input::file($key)->getClientOriginalName();

                    if($key == 'FILE_ATT_AGREEMENT'
                        || $key == 'FILE_ATT_DRIVER_LICENSE'
                        || $key == 'FILE_ATT_BUSINESS_CERTIFICATION'
                        || $key == 'FILE_ATT_VOID_CHECK')
                    {
                        $file = AccountFileAtt::where('account_id', $account->id)
                            ->where('type', $key)
                            ->first();
                        if (empty($file)) {
                            $file = new AccountFileAtt();
                        }
                    }else {
                        $file = AccountFile::where('account_id', $account->id)
                            ->where('type', $key)
                            ->first();
                        if (empty($file)) {
                            $file = new AccountFile;
                        }
                    }

                    $file->type = $key;
                    $file->account_id = $account->id;
                    $file->data = base64_encode($contents);
                    $file->file_name = $name;
                    $file->created_by = Auth::user()->user_id;
                    $file->cdate = Carbon::now();
                    $file->save();
                }


                if (in_array(Auth::user()->account_type, ['L'])) {

                    if($key == 'FILE_ATT_AGREEMENT'
                        || $key == 'FILE_ATT_DRIVER_LICENSE'
                        || $key == 'FILE_ATT_BUSINESS_CERTIFICATION'
                        || $key == 'FILE_ATT_VOID_CHECK')
                    {
                        $file = AccountFileAtt::where('account_id', $account->id)
                            ->where('type', $key)
                            ->first();
                    }else{
                        $file = AccountFile::where('account_id', $account->id)
                            ->where('type', $key)
                            ->first();
                    }
                    if (!empty($file)) {
                        $locked = $request->get($key . '_LOCKED');
                        if (empty($locked)) {
                            $locked = 'N';
                        }

                        $file->locked = $locked;
                        $file->save();
                    }
                }

            }

            AccountStoreType::where('account_id', $account->id)->delete();

            ### store type
            if (is_array($request->store_type_id)) {
                foreach ($request->store_type_id as $o) {
                    $ast = AccountStoreType::where('account_id', $account->id)
                        ->where('store_type_id', $o)
                        ->first();
                    if (empty($ast)) {
                        $ast = new AccountStoreType;
                    }

                    $ast->account_id = $account->id;
                    $ast->store_type_id = $o;
                    $ast->save();
                }
            }

            if ($old_pay_method != $account->pay_method) {
                # Send credit limit update success email to creditlimit@softpayplus.com
                $subject = "Pay Method Updated (Acct.ID : " . $account->id . ")";
                $msg = "Acct.ID - " . $account->id . "<br/>";
                $msg .= "Acct.Name - " . $account->name . "<br/>";
                $msg .= "Old.Pay.Method - " . $old_pay_method . "<br/>";
                $msg .= "New.Pay.Method - " . $account->pay_method . "<br/>";
                $msg .= "Date - " . $account->mdate . "<br/>";
                $msg .= "By - " . $account->modified_by . "<br/>";

                if (getenv('APP_ENV') == 'production') {
                    Helper::send_mail('creditlimit@softpayplus.com', $subject, $msg);
                } else {
                    Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
                }
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }

            //$this->output('Your request has been processed successfully!', 'edit', $close_modal = true, $is_error = false, $account->id);
            $this->output_msg('Your request has been processed successfully!', 'div_account_detail', false, true);

        } catch (\Exception $ex) {
            /*return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);*/
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']', 'edit');
        }
    }

    public function updateCreditInfo(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'credit_limit' => 'required|numeric',
                'ach_bank' => 'required',
                'ach_holder' => 'required',
                'ach_routeno' => 'required',
                'ach_acctno' => 'required',
                'no_ach' => 'in:Y,N',
                'no_postpay' => 'in:Y,N',
                'ach_tue' => 'in:Y,N',
                'ach_wed' => 'in:Y,N',
                'ach_thu' => 'in:Y,N',
                'ach_fri' => 'in:Y,N'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $account = Account::find($request->id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid account ID provided'
                ]);
            }

            $parent = Account::find($account->parent_id);
            if (empty($parent)) {
                return response()->json([
                    'msg' => 'Parent not found. Please contact IT'
                ]);
            }

            if ($parent->type != 'L' && $parent->credit_limit < $request->credit_limit) {
                return response()->json([
                    'msg' => 'Credit limit exceeds parent limit. Parent limit is ' . $parent->credit_limit
                ]);
            }

            $account->credit_limit = $request->credit_limit;
            $account->allow_cash_limit = $request->allow_cash_limit;
            $account->min_ach_amt = $request->min_ach_amt;
            $account->ach_bank = $request->ach_bank;
            $account->ach_holder = $request->ach_holder;
            $account->ach_routeno = $request->ach_routeno;
            $account->ach_acctno = $request->ach_acctno;
            $account->no_ach = $request->no_ach;
            $account->no_postpay = $request->no_postpay;
            $account->ach_tue = $request->ach_tue;
            $account->ach_wed = $request->ach_wed;
            $account->ach_thu = $request->ach_thu;
            $account->ach_fri = $request->ach_fri;

            $account->mdate = Carbon::now();
            $account->modified_by = Auth::user()->user_id;
            $account->save();

            switch ($account->type) {
                case 'M':
                    $account->balance = PaymentProcessor::get_master_limit($account->id);
                    break;
                case 'D':
                    $account->balance = PaymentProcessor::get_dist_limit($account->id);
                    break;
                case 'S':
                    $account->balance = PaymentProcessor::get_limit($account->id);
                    break;
                default:
                    $account->balance = 0;
                    break;
            }


            # Send credit limit update success email to creditlimit@softpayplus.com
            $subject = "Update Credit Info. Success (Acct.ID : " . $account->id . ", Credit.Limit : $" . $account->credit_limit . ")";
            $msg = "<b>Updated Credit Info.</b> <br/><br/>";
            $msg .= "Acct.ID - " . $account->id . "<br/>";
            $msg .= "Acct.Name - " . $account->name . "<br/>";
            $msg .= "Credit.Limit - $" . $account->credit_limit . "<br/>";
            $msg .= "Minimun.Payment.Amount - $" . $account->min_ach_amt . "<br/>";
            $msg .= "AllowCash.Limit - $" . $account->allow_cash_limit . "<br/>";
            $msg .= "ACH.Tue - " . $account->ach_tue . "<br/>";
            $msg .= "ACH.Wed - " . $account->ach_wed . "<br/>";
            $msg .= "ACH.Thu - " . $account->ach_thu . "<br/>";
            $msg .= "ACH.Fri - " . $account->ach_fri . "<br/>";
            $msg .= "Date - " . $account->mdate . "<br/>";
            $msg .= "By - " . $account->modified_by . "<br/>";

            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('creditlimit@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);


            return response()->json([
                'msg' => '',
                'balance' => $account->balance

            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    private function output_msg($msg, $modal_to_close = '', $is_error = true, $reload = false) {
        echo "<script>";

        echo "parent.myApp.showMsg('$msg', '$modal_to_close', '$is_error', '$reload');";

        echo "</script>";
    }

    private function output($msg, $mode = 'new', $close_modal = false, $is_error = true, $id = null) {
        echo "<script>";

        if (is_null($id)) {
            $id= '';
        }

        if ($close_modal) {
            echo "parent.close_modal('div_account_detail', '$id');";
        }

        if ($is_error) {
            echo "parent.myApp.hidePleaseWait('$mode');";
            echo "parent.myApp.showError(\"$msg\");";
        } else {
            if ($mode == 'edit') {
                echo "parent.myApp.hidePleaseWait('$mode');";
                echo "parent.myApp.showSuccess(\"$msg\");";
            } else if ($mode == 'new') {
                echo "parent.reload_after_create('$id');";
            }
        }

        echo "</script>";
        exit;
    }

    public function getUserList(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'account_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $users = User::where('account_id', $request->account_id)->orderBy('user_id', 'asc');

            if (!empty($request->user_id)) {
                $users->where('user_id', 'like', '%' . $request->user_id . '%');
            }

            if (!empty($request->status)) {
                $users->where('status', $request->status);
            }

            $users = $users->get();

            foreach($users as $o) {
                $o->status_name = $o->satatus_name;
            }

            return response()->json([
                'msg' => '',
                'users' => $users
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function getUserInfo(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'user_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $user = User::find($request->user_id);
            if (empty($user)) {
                return response()->json([
                    'msg' => 'Invalid user ID provided'
                ]);
            }

            $login_history = LoginHistory::where('user_id', $user->user_id)
                ->where('cdate', '>=', Carbon::today()->addDays(-30))
                ->orderBy('cdate', 'desc')
                ->get();

            $account = Account::find($user->account_id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid user account ID found'
                ]);
            }

            $roles = Role::where('account_type', $account->type)->get();

            return response()->json([
                'msg' => '',
                'user' => $user,
                'login_history' => $login_history,
                'roles' => $roles
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function addUser(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'user_id' => 'required',
                'name' => 'required',
                'role' => 'required',
                'status' => 'required|in:A,H,C',
                'password' => 'required|confirmed',
                'email' => 'required|email'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $account = Account::find($request->account_id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid account ID provided'
                ]);
            }

            $user = User::find($request->user_id);
            if (!empty($user)) {
                return response()->json([
                    'msg' => 'User ID duplicated'
                ]);
            }

            $user = new User;
            $user->user_id = $request->user_id;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->account_id = $account->id;
            $user->password = bcrypt($request->password);
            $user->status = $request->status;
            $user->created_at = Carbon::now();
            $user->role = $request->role;
            $user->save();

            Mail::to($user->email)
                ->bcc('tom@perfectmobileinc.com')
                ->send(new UserCreated($user, $request->password));

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updateUser(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'user_id' => 'required',
                'name' => 'required',
                'role' => 'required',
                'status' => 'required|in:A,H,C',
                'password' => 'confirmed',
                'email' => 'required|email'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $account = Account::find($request->account_id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid account ID provided'
                ]);
            }

            $user = User::find($request->user_id);
            if (empty($user)) {
                return response()->json([
                    'msg' => 'Invalid user ID provided'
                ]);
            }
            $old_user = $user->replicate();
            $old_user->user_id = $user->user_id;

            //$user->user_id = $request->user_id;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->account_id = $account->id;

            if (!empty($request->password)) {
                $user->password = bcrypt($request->password);
            }

            $user->status = $request->status;
            $user->updated_at = Carbon::now();
            $user->role = $request->role;
            $user->save();

            $hasher = app('hash');

            if(!empty($request->copy_email)){
                Mail::to($user->email)
                    ->cc('tom@perfectmobileinc.com')
                    ->bcc($request->copy_email)
                    ->send(new UserUpdated($old_user, $user, $request->password, $request->comment, $account->id));
            }else {
                ### user information update email ###
                Mail::to($user->email)
                    ->bcc('tom@perfectmobileinc.com')
                    ->send(new UserUpdated($old_user, $user, $request->password, $request->comment, $account->id));
            }
            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loginAs(Request $request) {

        if (!Permission::can($request->path(), 'login-as')) {
            return back()->withErrors([
                'exception' => 'You are not authorized to use login-as feature'
            ])->withInput();
        }

        Helper::log('### inside postLoginAs ###', [
            'user_id' => $request->user_id
        ]);

        $user = User::find($request->user_id);
        if (empty($user)) {
            return back()->withErrors([
                'exception' => 'Invalid user ID provided'
            ])->withInput();
        }

        if ($user->status != 'A') {
            return back()->withErrors([
                'exception' => 'User is not in activate status'
            ])->withInput();
        }

        $account = Account::find($user->account_id);
        if ($account->status != 'A') {
            return back()->withErrors([
                'exception' => 'Account is not in activate status'
            ])->withInput();
        }

        $login_as_user = Auth::user();

        Auth::logout();
        Session::flush();
        Session::regenerate();

        Auth::login($user);
        Session::put('login-as-user', $login_as_user);

        switch ($account->type) {
            case 'L':
            case 'M':
            case 'D':
            case 'A':
                return redirect('/admin');
            case 'S':
                return redirect('/sub-agent');

        }
    }

    public function remove(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                $this->output('You are not authorized to modify any information', 'edit');
            }

            $login_account = Account::find(Auth::user()->account_id);
            if (empty($login_account)) {
                $this->output('Session expired');
            }

            if ($login_account->type != 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && env('APP_ENV') == 'local')) {
                $this->output('You are not allowed to remove account');
            }

            $account = Account::find($request->id);
            if (empty($account)) {
                $this->output('Invalid account ID provided');
            }

            if ($account->type == 'L') {
                $this->output('Root account cannot be removed');
            }

            $trans_qty = Transaction::join('accounts', 'transaction.account_id', 'accounts.id')
                ->where('accounts.path', 'like', $account->path . '%')
                ->count();

            if ($trans_qty > 0) {
                $this->output('Account with transactions and / or their parents are protected');
            }

            $acct_list = Account::where('path', 'like', $account->path . '%')->get();

            Account::where('path', 'like', $account->path . '%')->delete();

            ## Remove Users when remove account ID
            if (!empty($acct_list) && count($acct_list) > 0) {
                foreach ($acct_list as $acct) {
                    User::where('account_id', $acct->id)->delete();
                }
            }

            //$this->output('Your request has been processed successfully!', 'edit', $close_modal = true, $is_error = false, null);
            $this->output_msg('Your request has been processed successfully!', 'div_account_detail', false, false);

        } catch (\Exception $ex) {

            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function removeUser(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'user_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $user = User::find($request->user_id);
            if (empty($user)) {
                return response()->json([
                    'msg' => 'Unable to find user with ID : ' . $request->user_id
                ]);
            }

            $user->delete();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode . ']'
            ]);
        }
    }

    public function authority(Request $request) {
        try {
            $authority = AccountAuthority::where('account_id', $request->account_id)->first();

            if (empty($authority)) {
                $authority = new AccountAuthority();
                $authority->account_id = $request->account_id;
                $authority->auth_batch_rtr = 'N';
                $authority->auth_batch_sim_swap = 'N';
                $authority->auth_batch_plan_change = 'N';
                $authority->for_rtr_daily = 20;
                $authority->for_rtr_weekly = 100;
                $authority->for_rtr_monthly = 300;
                $authority->for_sim_swap_daily = 20;
                $authority->for_sim_swap_weekly = 100;
                $authority->for_sim_swap_monthly = 300;
                $authority->for_plan_change_daily = 20;
                $authority->for_plan_change_weekly = 100;
                $authority->for_plan_change_monthly = 300;
                $authority->cdate = Carbon::now();
                $authority->save();
            }

            $batch_fee = ATTBatchFee::get_batch_fee($request->account_id);


            return response()->json([
                'msg' => '',
                'data' => [
                    'auth_batch_rtr'        => $authority->auth_batch_rtr,
                    'auth_batch_sim_swap'   => $authority->auth_batch_sim_swap,
                    'auth_batch_plan_change' => $authority->auth_batch_plan_change,
                    'for_rtr_daily'         => $authority->for_rtr_daily,
                    'for_rtr_weekly'        => $authority->for_rtr_weekly,
                    'for_rtr_monthly'       => $authority->for_rtr_monthly,
                    'for_sim_swap_daily'    => $authority->for_sim_swap_daily,
                    'for_sim_swap_weekly'   => $authority->for_sim_swap_weekly,
                    'for_sim_swap_monthly'  => $authority->for_sim_swap_monthly,
                    'for_plan_change_daily' => $authority->for_plan_change_daily,
                    'for_plan_change_weekly' => $authority->for_plan_change_weekly,
                    'for_plan_change_monthly' => $authority->for_plan_change_monthly,
                    'for_rtr_tier'          => empty($batch_fee) ? '' : $batch_fee->for_rtr_tier,
                    'for_sim_swap_tier'     => empty($batch_fee) ? '' : $batch_fee->for_sim_swap_tier,
                    'for_plan_change_tier'  => empty($batch_fee) ? '' : $batch_fee->for_plan_change_tier,
                    'for_sim_swap_sdate'    => empty($batch_fee) ? '' : $batch_fee->for_sim_swap_sdate,
                    'for_sim_swap_edate'    => empty($batch_fee) ? '' : $batch_fee->for_sim_swap_edate,
                    'for_sim_swap'          => empty($batch_fee) ? '' : $batch_fee->for_sim_swap,
                    'for_plan_change_sdate' => empty($batch_fee) ? '' : $batch_fee->for_plan_change_sdate,
                    'for_plan_change_edate' => empty($batch_fee) ? '' : $batch_fee->for_plan_change_edate,
                    'for_plan_change'       => empty($batch_fee) ? '' : $batch_fee->for_plan_change,
                    'for_rtr_sdate'         => empty($batch_fee) ? '' : $batch_fee->for_rtr_sdate,
                    'for_rtr_edate'         => empty($batch_fee) ? '' : $batch_fee->for_rtr_edate,
                    'for_rtr'               => empty($batch_fee) ? '' : $batch_fee->for_rtr
                ]
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode . ']'
            ]);
        }
    }

    public function authority_post(Request $request) {
        try {
            $authority = AccountAuthority::where('account_id', $request->account_id)->first();

            if (empty($authority)) {
                $authority = new AccountAuthority();
                $authority->account_id = $request->account_id;
                $authority->auth_batch_rtr = 'N';
                $authority->auth_batch_sim_swap = 'N';
                $authority->auth_batch_plan_change = 'N';
                $authority->cdate = Carbon::now();
                $authority->save();
            }

            $authority->auth_batch_rtr = $request->auth_batch_rtr;
            $authority->auth_batch_sim_swap = $request->auth_batch_sim_swap;
            $authority->auth_batch_plan_change = $request->auth_batch_plan_change;
            $authority->for_rtr_daily = $request->for_rtr_daily;
            $authority->for_rtr_weekly = $request->for_rtr_weekly;
            $authority->for_rtr_monthly = $request->for_rtr_monthly;
            $authority->for_sim_swap_daily = $request->for_sim_swap_daily;
            $authority->for_sim_swap_weekly = $request->for_sim_swap_weekly;
            $authority->for_sim_swap_monthly = $request->for_sim_swap_monthly;
            $authority->for_plan_change_daily = $request->for_plan_change_daily;
            $authority->for_plan_change_weekly = $request->for_plan_change_weekly;
            $authority->for_plan_change_monthly = $request->for_plan_change_monthly;
            $authority->mdate = Carbon::now();
            $authority->update();


            $batch_fee = ATTBatchFee::get_batch_fee($request->account_id);
            if (empty($batch_fee)) {
                $batch_fee = new ATTBatchFee();
                $batch_fee->account_id      = $request->account_id;
                $batch_fee->cdate = Carbon::now();
                $batch_fee->save();
            }
            $batch_fee->for_rtr_tier      = $request->for_rtr_tier;
            $batch_fee->for_sim_swap_tier      = $request->for_sim_swap_tier;
            $batch_fee->for_plan_change_tier      = $request->for_plan_change_tier;
            $batch_fee->for_sim_swap_sdate      = $request->for_sim_swap_sdate;
            $batch_fee->for_sim_swap_edate      = $request->for_sim_swap_edate;
            $batch_fee->for_sim_swap      = $request->for_sim_swap_fee;
            $batch_fee->for_plan_change_sdate      = $request->for_plan_change_sdate;
            $batch_fee->for_plan_change_edate      = $request->for_plan_change_edate;
            $batch_fee->for_plan_change      = $request->for_plan_change_fee;
            $batch_fee->for_rtr_sdate      = $request->for_rtr_sdate;
            $batch_fee->for_rtr_edate      = $request->for_rtr_edate;
            $batch_fee->for_rtr      = $request->for_rtr_fee;
            $batch_fee->mdate = Carbon::now();
            $batch_fee->update();

            return response()->json([
                'msg' => 'Successfully updated !!!'
            ]);
        } catch (\Exception $ex) {
//            Helper::log('##### EXCEPTION ######', $ex.getTraceAsString());

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }


    public function load_parent_account_info(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                  'msg' => $msg
                ]);
            }

            $account = Account::find($request->id);
            if (empty($account)) {
                return response()->json([
                  'msg' => 'Invalid account ID provided'
                ]);
            }

            $rate_plans = RatePlan::where('owner_id', $account->id)->get();

            if (count($rate_plans) > 0) {
                foreach ($rate_plans as $o) {
                    $o->last_updated = $o->last_updated;
                    $o->type_img = Helper::get_hierarchy_img($o->type);
                }
            }

            return response()->json([
                'msg' => '',
                'account' => $account,
                'rate_plans' => $rate_plans
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ]);
        }
    }

    public function parent_transfer(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'acct_id' => 'required',
                'parent_acct_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                  'msg' => $msg
                ]);
            }

            $account = Account::find($request->acct_id);
            if (empty($account)) {
                return response()->json([
                  'msg' => 'Invalid account ID provided'
                ]);
            }

            $parent = Account::find($request->parent_acct_id);
            if (empty($parent)) {
                return response()->json([
                  'msg' => 'Invalid parent provided'
                ]);
            }

            $account->master_id = $parent->master_id;
            $account->parent_id = $parent->id;
            $account->path      = $parent->path . $account->id;
            $account->modified_by = Auth::user()->user_id;
            $account->mdate     = Carbon::now();
            $account->rate_plan_id = $request->rate_plan_id;
            $account->update();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString()
            ]);
        }
    }

    public function spiff_detail_load(Request $request) {
        if (empty($request->template_id) || empty($request->carrier)) {
            return view('admin.account.tbl_spiff_detail')->with([
              'spiff_setups' => ''
            ]);
        }

        $spiff_setups = SpiffSetup::where('template', $request->template_id)
            ->whereRaw("product_id in (select id from product where carrier = '" . $request->carrier . "')")
            ->orderBy("product_id", 'asc')
            ->orderBy("denom", 'asc')
            ->get();

        foreach ($spiff_setups as $s) {
            $product = Product::find($s->product_id);
            if (!empty($product)) {
                $s->product = $product->name;
            }
        }

        return view('admin.account.tbl_spiff_detail')->with([
            'spiff_setups' => $spiff_setups
        ]);
    }

    public function spiff_template(Request $request) {
        $spiff_templates = SpiffTemplate::whereRaw('id in (select template_id from spiff_template_owner where account_id = ' . $request->account_id . ')')
            ->get([
                'id',
                'account_type'
            ]);

         $count = empty($spiff_templates) ? 0 : count($spiff_templates);

         return response()->json([
            'count' => $count,
            'spiff_templates' => $spiff_templates
         ]);
    }

    public function spiff_template_check(Request $request) {
        $template = SpiffTemplate::find($request->template_id);
        $owner = SpiffTemplateOwner::where('template_id', $request->template_id)->where('account_id', $request->account_id)->first();

        if (empty($owner)) {
            $owner = new SpiffTemplateOwner();
            $owner->template_id = $request->template_id;
            $owner->account_id = $request->account_id;
            $owner->cdate = \Carbon\Carbon::now();
            $owner->save();

            ## Set All template
            Account::where('parent_id', $owner->account_id)
                ->where('type', $template->account_type)
                ->whereNull('spiff_template')
                ->update([
                    'spiff_template' => $owner->template_id
                ]);

            if (!empty($used_qty) && $used_qty > 0) {
                return response()->json([
                  'code'  => '-1',
                  'msg' => 'Can not delete the template. ' . $used_qty . ' accounts are using the template.'
                ]);
            }

            return response()->json([
                'code'  => '0',
                'msg' => 'Template added. [' . $owner->cdate . ']'
            ]);
        } else {

            $used_qty = Account::where('parent_id', $owner->account_id)
                ->whereRaw('( spiff_template = ' . $owner->template_id .
                    ')')
                ->count();

            if (!empty($used_qty) && $used_qty > 0) {
                return response()->json([
                  'code'  => '-1',
                  'msg' => 'Can not delete the template. ' . $used_qty . ' accounts are using the template.'
                ]);
            }

            $owner->delete();

            return response()->json([
                'code'  => '0',
                'msg' => 'Template deleted'
            ]);
        }
    }

    public function activation_controller(Request $request) {
        $ac = ActivationController::where('account_id', $request->account_id)->where('carrier', $request->carrier)->first();
        if (empty($ac)) {
            $ac = new ActivationController();
            $ac->account_id = $request->account_id;
            $ac->carrier = $request->carrier;
            $ac->cdate = Carbon::now();
            $ac->save();

            return response()->json([
              'code'  => '0',
              'msg' => 'Updated (Add)'
            ]);

        } else {
            $ac->delete();

            return response()->json([
              'code'  => '0',
              'msg' => 'Updated (Remove)'
            ]);
        }
    }

    public function vr(Request $request) {
        $vras = AccountVRAuth::where('account_id', $request->account_id)->get();
        foreach ($vras as $v) {
            $v->carrier_key = $v->carrier;
            $v->carrier_key = str_replace('&', '', $v->carrier);
            $v->carrier_key = str_replace(' ', '', $v->carrier_key);
        }

        return response()->json([
          'code'  => '0',
          'vras' => $vras
        ]);
    }

    public function vr_product(Request $request) {
        $vras = AccountVRAuth::where('account_id', $request->account_id)->get();

        return response()->json([
            'code'  => '0',
            'vras' => $vras
        ]);
    }

    public function vr_save(Request $request) {
        $vra = AccountVRAuth::where('carrier', $request->carrier)->where('account_id', $request->account_id)->first();

        if (empty($vra)) {
            $vra = new AccountVRAuth();
            $vra->account_id = $request->account_id;
            $vra->carrier = $request->carrier;
            $vra->status = 'A';
            $vra->cdate = Carbon::now();
            $vra->save();

            return response()->json([
              'code'  => '0',
              'msg' => 'Updated (Add)'
            ]);
        }

        $vra->delete();

        return response()->json([
          'code'  => '0',
          'msg' => 'Updated (Remove)'
        ]);
    }

    public function vr_product_save(Request $request) {

        $vra = AccountVRAuth::where('vr_product_id', $request->id)->where('account_id', $request->account_id)->first();

        if (empty($vra)) {
            $vra = new AccountVRAuth();
            $vra->account_id = $request->account_id;
            $vra->vr_product_id = $request->id;
            $vra->status = 'A';
            $vra->cdate = Carbon::now();
            $vra->save();

            return response()->json([
                'code'  => '0',
                'msg' => 'Updated (Add)'
            ]);
        }

        $vra->delete();

        return response()->json([
            'code'  => '0',
            'msg' => 'Updated (Remove)'
        ]);
    }

    public function remove_plan(Request $request) {
        try {

            RatePlan::where('id', $request->id)->delete();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

    public function add_account_shipping_fee(Request $request) {
        try {

            $asf = new AccountShipFee();
            $asf->account_id = $request->account_id;
            $asf->min_amt   = $request->ship_fee_min;
            $asf->max_amt   = $request->ship_fee_max;
            $asf->fee       = $request->ship_fee;
            $asf->cdate     = \Carbon\Carbon::now();
            $asf->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

    public function delete_account_shipping_fee(Request $request) {
        try {

            AccountShipFee::where('id', $request->account_ship_fee_id)->delete();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }
}