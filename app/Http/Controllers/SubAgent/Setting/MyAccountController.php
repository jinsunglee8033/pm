<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/16/17
 * Time: 3:48 PM
 */

namespace App\Http\Controllers\SubAgent\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Session;
use Auth;
use Log;
use Excel;
use Carbon\Carbon;
use App\Model\State;
use App\Model\Account;

class MyAccountController extends Controller
{

    public function show() {

        $states = State::all();
        $account = Account::find(Auth::user()->account_id);
        return view('sub-agent.setting.my-account', [
            'states' => $states,
            'account' => $account
        ]);
    }

    public function post(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'name' => 'required',
                'tax_id' => 'required',
                'contact' => 'required',
                'office_number' => 'required|regex:/^\d{10}$/',
                'email' => 'required|email',
                'address1' => 'required',
                'city' => 'required',
                'state' => 'required',
                'zip' => 'required|regex:/^\d{5}$/'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)->withInput();
            }

            $account = Account::find(Auth::user()->account_id);
            $account->name = $request->name;
            $account->tax_id = $request->tax_id;
            $account->contact = $request->contact;
            $account->office_number = $request->office_number;
            $account->email = strtolower($request->email);
            $account->address1 = $request->address1;
            $account->address2 = $request->address2;
            $account->city = $request->city;
            $account->state = $request->state;
            $account->zip = $request->zip;
            $account->save();

            return back()->with([
                'success' => 'Your request has been processed successfully!'
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}