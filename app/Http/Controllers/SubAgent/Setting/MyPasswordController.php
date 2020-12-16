<?php

namespace App\Http\Controllers\SubAgent\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Session;
use Auth;
use Log;
use Excel;
use Carbon\Carbon;

/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/16/17
 * Time: 3:26 PM
 */
class MyPasswordController extends Controller
{

    public function show() {
        return view('sub-agent.setting.my-password');
    }

    public function post(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'current_password' => 'required',
                'password' => 'required|confirmed|min:6'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)->withInput();
            }


            $user = Auth::user();
            if (!Auth::validate(['user_id' => $user->user_id, 'password' => $request->current_password])) {
                return back()->withErrors([
                    'current_password' => 'Invalid current password provided'
                ])->withInput();
            }

            $user->password = bcrypt($request->password);
            $user->updated_at = Carbon::now();
            $user->save();

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