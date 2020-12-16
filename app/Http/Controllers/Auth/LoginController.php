<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\AccountStoreHours;
use App\Model\AccountStoreIp;
use App\Model\AccountUserHours;
use App\Model\AccountUserIp;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use Log;
use App\Model\Account;
use Illuminate\Http\Request;
use Session;
use App\Model\LoginHistory;
use Carbon\Carbon;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/sub-agent';

    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'logout']);
    }

    public function username()
    {
        return 'user_id';
    }

    public function redirectPath()
    {
        Helper::log('### inside redirectPath ###');

        $user = Auth::user();

        if (empty($user)) {
            return '/';
        }

        $account = Account::find($user->account_id);

        Helper::log('### account type ###', [
            'type' => $account->type
        ]);

        switch ($account->type) {
            case 'L':
                if (in_array($user->user_id,['thomas', 'admin', 'PMCHRIS'])) {
                    $this->redirectTo = '/admin/reports/vr-request';
                } else {
                    $this->redirectTo = '/admin';
                }
                break;
            case 'M':
            case 'D':
            case 'A':
                $this->redirectTo = '/admin';
                break;
            case 'S':
                $this->redirectTo = '/sub-agent';
                break;
            default:
                $this->redirectTo = '/';
                break;
        }

        return $this->redirectTo;
    }

    protected function validateLogin(Request $request)
    {
        $this->validate($request, [
            $this->username() => 'required', 'password' => 'required',
        ]);


    }

    public function logout(Request $request)
    {
        $login_as_user = Session::get('login-as-user');

        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        if (!empty($login_as_user)) {
            Auth::login($login_as_user);
            return redirect($this->redirectPath());
        }

        return redirect('/login');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if (strlen($request->user_id) > 50) { // Too long length (50)
            $this->guard()->logout();
            $request->session()->flush();
            $request->session()->regenerate();
            return $this->sendLengthLoginResponse($request);
        }

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            ### save login history ###
            $this->save_login_history('F', 'Too man login attempts', $request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            ### if login user's account is invalid mark it failed.
            $account = Account::find(Auth::user()->account_id);

            if ($account->status != 'A') {
                $this->guard()->logout();
                $request->session()->flush();
                $request->session()->regenerate();

                $this->save_login_history('F', 'Account is not active', $request);

                if ($account->status == 'C'){
                    return $this->sendFailedLoginResponse($request, 'auth.c');
                }elseif ($account->status == 'F'){
                    return $this->sendFailedLoginResponse($request, 'auth.f');
                }elseif ($account->status == 'H'){
                    return $this->sendFailedLoginResponse($request, 'auth.h');
                }elseif ($account->status == 'B') {
                    $msg = "Registered Date : $account->cdate" ;
                    $account_id = $account->id;
                    return $this->sendPendingLoginResponse($request, $msg, $account_id);
                }else {
                    return $this->sendFailedLoginResponse($request);
                }
            }else{
                $account_id = $account->id;
                $user_id = Auth::user()->user_id;
                $day        = Carbon::now()->format('D');

                ### User hour check
                $temp_stime = strtolower($day).'_stime';
                $temp_etime = strtolower($day).'_etime';
                $exist_user = AccountUserHours::where('user_id', $user_id)->whereNotNull($temp_stime)->get();

                if(count($exist_user) > 0) { // exist
                    $time_zone = $account->time_zone;

                    if($time_zone == null){ // Time zone not setting
                        $this->guard()->logout();
                        $request->session()->flush();
                        $request->session()->regenerate();
                        return $this->sendWrongTzLoginResponse($request);
                    }else {

                        $final_time = Carbon::now()->subHours($time_zone)->format('H:i:s'); // set with time zone
//                        dd($final_time); die();
                        $available = AccountUserHours::where('account_id', $account_id)
                            ->where('user_id', $user_id)
                            ->where($temp_stime, '<', $final_time)
                            ->where($temp_etime, '>', $final_time)
                            ->get();

                        if (count($available) == 0) { // Not Available
                            $this->guard()->logout();
                            $request->session()->flush();
                            $request->session()->regenerate();
                            return $this->sendWrongUserHoursLoginResponse($request);
                        }
                    }
                }

                ### User IP check
                $cur_user_ip = $request->ip();

                $exist_user_ips = AccountUserIp::where('account_id', $account_id)->where('user_id', $user_id)->get();

                if(count($exist_user_ips) > 0){ // exist

                    $available_ip = AccountUserIp::where('account_id', $account_id)
                        ->where('user_id', $user_id)
                        ->where('ip', $cur_user_ip)
                        ->get();

                    if(count($available_ip) == 0){ // Not Available
                        $this->guard()->logout();
                        $request->session()->flush();
                        $request->session()->regenerate();
                        return $this->sendWrongUserIpLoginResponse($request);
                    }

                }

                ### Store Office hours check
                $exist = AccountStoreHours::where('account_id', $account_id)->where('day', '=', $day)->get();

                if(count($exist) > 0) { // exist

                    $time_zone = $account->time_zone;

                    if($time_zone == null){ // Time zone not setting
                        $this->guard()->logout();
                        $request->session()->flush();
                        $request->session()->regenerate();
                        return $this->sendWrongTzLoginResponse($request);
                    }else {

                        $final_time = Carbon::now()->subHours($time_zone)->format('H:i:s'); // set with time zone
//                        dd($final_time); die();
                        $available = AccountStoreHours::where('account_id', $account_id)
                            ->where('day', '=', $day)
                            ->where('stime', '<', $final_time)
                            ->where('etime', '>', $final_time)
                            ->get();

                        if (count($available) == 0) { // Not Available
                            $this->guard()->logout();
                            $request->session()->flush();
                            $request->session()->regenerate();
                            return $this->sendWrongHoursLoginResponse($request);
                        }
                    }
                }

                ### Store IP check
                $cur_store_ip = $request->ip();

                $exist_ips = AccountStoreIp::where('account_id', $account_id)->get();

                if(count($exist_ips) > 0){ // exist

                    $available_ip = AccountStoreIp::where('account_id', $account_id)
                        ->where('ip', $cur_store_ip)
                        ->get();

                    if(count($available_ip) == 0){ // Not Available
                        $this->guard()->logout();
                        $request->session()->flush();
                        $request->session()->regenerate();
                        return $this->sendWrongIpLoginResponse($request);
                    }

                }

            }

            if (Auth::user()->status != 'A') {
                $this->guard()->logout();
                $request->session()->flush();
                $request->session()->regenerate();

                $this->save_login_history('F', 'User is not active', $request);
                return $this->sendFailedLoginResponse($request);
            }

            ### save login history ###
            $this->save_login_history('S', '', $request);

            ### last login update ###
            $user = User::find($request->user_id);
            $user->last_login = Carbon::now();
            $user->save();

            return $this->sendLoginResponse($request);
        }

        $this->save_login_history('F', trans('auth.failed'), $request);

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    private function save_login_history($result, $msg, $request) {
        $data = new LoginHistory;
        $data->user_id = $request->user_id;
        $data->password = $result == 'S' ? '' : $request->password;
        $data->result = $result;
        $data->result_msg = $msg;
        $data->ip = $request->ip();
        $data->cdate = Carbon::now();
        $data->save();
    }

    protected function sendFailedLoginResponse(Request $request, $trans = 'auth.failed')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendPendingLoginResponse(Request $request, $msg, $account_id)
    {
        $errors = ["pending" => $msg, "account_id" => $account_id];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendLengthLoginResponse(Request $request, $trans = 'auth.length')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendWrongHoursLoginResponse(Request $request, $trans = 'auth.hours')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendWrongUserHoursLoginResponse(Request $request, $trans = 'auth.user_hours')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendWrongIpLoginResponse(Request $request, $trans = 'auth.ip')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendWrongUserIpLoginResponse(Request $request, $trans = 'auth.user_ip')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }

    protected function sendWrongTzLoginResponse(Request $request, $trans = 'auth.tz')
    {
        $errors = [$this->username() => trans($trans)];
        if ($request->expectsJson()) {
            return response()->json($errors, 422);
        }
        return redirect()->back()
            ->withInput($request->only($this->username(), 'remember'))
            ->withErrors($errors);
    }
}
