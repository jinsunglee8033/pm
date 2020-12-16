<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/13/17
 * Time: 3:50 PM
 */

namespace App\Http\Controllers;

use App\Lib\Helper;
use App\Mail\ApplySubagent;
use App\Mail\CheckList;
use App\Mail\HowToUsePortal;
use App\Model\Account;
use App\Model\AccountStoreType;
use App\Model\DefaultSubSpiff;
use App\User;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use App\Model\State;
use App\Model\StoreType;
use Mail;
use Illuminate\Support\Facades\Session;

class ApplySubagentController extends Controller
{
    public function show(Request $request) {

        $states = State::orderBy('name', 'asc')->get();
        $store_types = StoreType::orderBy('name', 'asc')->get();
        $code = Helper::generate_code(6);
        Session::put('verification-code', $code);

        if (empty($request->agent)) {
            return view('apply-subagent', [
                'store_types' => $store_types,
                'states' => $states,
                'verification_code' => $code
            ]);
        } else {
            $account = Account::find($request->agent);

            return view('apply-subagent-master', [
                'store_types' => $store_types,
                'states' => $states,
                'account' => $account,
                'verification_code' => $code
            ]);
        }
    }

    public function post(Request $request) {
        try {
            $v = Validator::make($request->all(), [
                'business_name' => 'required',
                'biz_license' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'phone' => 'required|regex:/^\d{10}$/',
                'store_type' => 'required',
                'address1' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required|regex:/^\d{5}$/',
                'sales_email' => 'sometimes|nullable|email',
                'sales_phone' => 'sometimes|nullable|regex:/^\d{10}$/',
                'user_name' => 'required',
                'password' => 'required',
                'verification_code' => 'required'
            ]);

            if ($v->fails()) {

                $failed_fields = [];

                foreach ($v->messages()->toArray() as $k => $v) {
                    $failed_fields[$k] = $v[0];
                }

                return back()->withErrors($failed_fields)->withInput();
            }

            $scode = Session::get('verification-code');
            if ($request->verification_code != $scode) {

                return back()->withErrors([
                    'exception' => 'Invalid Verification Code Provided !!'
                ])->withInput();
            }

            # check user_id duplication

            $chk_user = User::where("user_id", $request->user_name)->first();

            if (!empty($chk_user)) {
                return back()->withErrors([
                    'user_name' => 'Duplicated user name! Please enter other user name.',
                    'exception' => 'Duplicated user name! Please enter other user name.'
                ])->withInput();
            }

            $agent_id = empty($request->agent_id) ? 100036 : $request->agent_id;
            $agent_obj = Account::where('id', $agent_id)->first();

            $rate_plan_id = (count((array)$agent_obj) == 0 ) ? NULL : $agent_obj->default_subagent_plan;

            ### CREATE TEMP ACCOUNT ### START
            $account = new Account();
            $account->name      = $request->business_name;
            $account->tax_id    = $request->biz_license;
            $account->contact   = $request->first_name .' '. $request->last_name;
            $account->email     = $request->email;
            $account->office_number = $request->phone;
            $account->address1  = $request->address1;
            $account->address2  = $request->address2;
            $account->city      = $request->city;
            $account->state     = $request->state;
            $account->zip       = $request->zip;
            $account->sales_email = $request->sales_email;
//            $account->phone2    = $request->sales_phone;
            $account->notes     = 'From Become a dealer. ' . $request->sales_name . ' Phone : ' . $request->sales_phone . ' Email : ' . $request->sales_email ;
            ## --

            if($agent_id != 100036){
                $account->master_id = $agent_obj->master_id;
            }else{
                $account->master_id = 100036;
            }

            $account->parent_id = empty($request->agent_id) ? 100036 : $request->agent_id;
            $account->rate_plan_id = $rate_plan_id;
            $account->pay_method = ( empty($request->account_type) || !in_array( $request->account_type,['P','C']) ) ? 'P' : $request->account_type ;
            $account->type      = 'S';

            // Init status is P instead of B (9/1/2020)
            $account->status    = 'B';
            $account->wait_for_approve = 'Y';
            $account->created_by = 'system';
            $account->cdate     = Carbon::now();
            $account->save();

            $account->path = '100000' . $account->parent_id . $account->id;
            $account->update();

            $store_type = "";
            if (is_array($request->store_type)) {
                foreach ($request->store_type as $o) {
                    $store_type .= (empty($store_type) ? "" : ", ") . $o;

                    $st = StoreType::where('name', '' . $o)->first();
                    if (!empty($st)) {
                        $acct_st = new AccountStoreType();
                        $acct_st->account_id = $account->id;
                        $acct_st->store_type_id = $st->id;
                        $acct_st->save();
                    }
                }
            }

            $parent_id = $account->parent_id;

            // Setting products same as parents
            $p_acct = Account::where('id', $parent_id)->first();
            if(!empty($p_acct)){
                if($p_acct->act_lyca == 'Y'){
                    $account->act_lyca = 'Y';
                }
                if($p_acct->act_h2o == 'Y'){
                    $account->act_h2o = 'Y';
                }
                if($p_acct->act_att == 'Y'){
                    $account->act_att = 'Y';
                }
                if($p_acct->act_freeup == 'Y'){
                    $account->act_freeup = 'Y';
                }
                if($p_acct->act_gen == 'Y'){
                    $account->act_gen = 'Y';
                }
                if($p_acct->act_liberty == 'Y'){
                    $account->act_liberty = 'Y';
                }
                if($p_acct->act_boom == 'Y'){
                    $account->act_boom = 'Y';
                }
            }

            // Default Spiff
            $default_spiff = DefaultSubSpiff::where('acct_id', $parent_id)->first();

            if(!empty($default_spiff)){
                $account->spiff_template = $default_spiff->spiff_id;
            }

            $account->update();

            $user = new User;
            $user->user_id  = $request->user_name;
            $user->name     = $request->first_name .' '. $request->last_name;
            $user->email    = $request->email;
            $user->account_id = $account->id;
            $user->password = bcrypt($request->password);
            $user->status   = 'A';
            $user->created_at = Carbon::now();
            $user->role     = 'M';
            $user->save();
            ### CREATE TEMP ACCOUNT ### END

            $request->store_type = $store_type;

            if (getenv('APP_ENV') == 'production') {
                $email = [$request->email];
                $cc_email = ['register@softpayplus.com', 'it@perfectmobileinc.com'];
            } else {
                $email = [$request->email];
                $cc_email = ['register@softpayplus.com', 'it@jjonbp.com'];
            }
            
            Mail::to($email)
                ->bcc($cc_email)
                ->send(new ApplySubagent($request, $account));

            if($request->account_type == 'C'){
                Mail::to($email)->send(new CheckList($request));
            }else{
                Mail::to($email)->send(new HowToUsePortal($request));
            }

            return back()->with([
                'success' => 'Y'
            ]);

        } catch (\Exception $ex) {
            Helper::log('###### EXCEPTION ######', $ex->getTraceAsString());

            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}