<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/16/17
 * Time: 4:29 PM
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
use App\User;

class UsersController extends Controller
{

    public function show(Request $request) {

        $users = User::where('account_id', Auth::user()->account_id);

        if (!empty($request->user_id)) {
            $users = $users->where('user_id', 'like', '%' . $request->user_id . '%');
        }

        $users = $users->orderBy('user_id', 'asc')->paginate(20);

        return view('sub-agent.setting.users', [
            'users' => $users,
            'user_id' => $request->user_id
        ]);
    }

    public function detail($user_id) {

        $user = User::find($user_id);
        return view('sub-agent.setting.user-detail', [
            'user' => $user
        ]);
    }

    public function newUser() {

        return view('sub-agent.setting.new-user');
    }

    public function updateUser(Request $request, $user_id) {

        try {

            $v = Validator::make($request->all(), [
                'user_id' => 'required',
                'name' => 'required',
                'password' => 'nullable|confirmed|min:6',
                'email' => 'required|email',
                'role' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)->withInput();
            }

            $user = User::find($user_id);
            if (empty($user)) {
                return back()->withErrors([
                    'exception' => 'Invalid user ID provided'
                ]);
            }

            $user->name = $request->name;
            if (!empty($request->password)) {
                $user->password = bcrypt($request->password);
            }
            $user->email = $request->email;
            $user->role = $request->role;
            $user->status = $request->status;
            $user->updated_at = Carbon::now();
            $user->save();

            return back()->with([
                'success' => 'Your request has been successfully processed!'
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }

    }

    public function createUser(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'user_id' => 'required|unique:users',
                'name' => 'required',
                'password' => 'required|confirmed|min:6',
                'email' => 'required|email',
                'role' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)->withInput();
            }

            $user = new User;

            $user->user_id = $request->user_id;
            $user->name = $request->name;
            $user->account_id = Auth::user()->account_id;
            $user->password = bcrypt($request->password);
            $user->email = $request->email;
            $user->role = $request->role;
            $user->status = $request->status;
            $user->created_at = Carbon::now();
            $user->save();

            return redirect('/sub-agent/setting/users');

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }

    }
}