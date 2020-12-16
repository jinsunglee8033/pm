<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/13/17
 * Time: 3:50 PM
 */

namespace App\Http\Controllers;

use App\Lib\Helper;
use App\Mail\ApplyMasterAgent;
use App\Model\Account;
use App\Model\AccountStoreType;
use App\User;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use App\Model\State;
use App\Model\StoreType;
use Mail;
use Illuminate\Support\Facades\Session;

class ApplyMasteragentController extends Controller
{
    public function show(Request $request) {

        $states = State::orderBy('name', 'asc')->get();
        $code = Helper::generate_code(6);
        Session::put('verification-code', $code);

        return view('apply-masteragent', [
            'states' => $states,
            'verification_code' => $code
        ]);
    }

    public function post(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'business_name' => 'required',
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
                'phone' => 'required|regex:/^\d{10}$/',
                'city' => 'required',
                'state' => 'required',
                'retail_location_no' => 'required',
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

            if (getenv('APP_ENV') == 'production') {
                $email = ['register@softpayplus.com', $request->email];
            } else {
                $email = ['it@jjonbp.com', $request->email];
            }

            Mail::to($email)
              ->bcc('it@perfectmobileinc.com')
              ->send(new ApplyMasterAgent($request));

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