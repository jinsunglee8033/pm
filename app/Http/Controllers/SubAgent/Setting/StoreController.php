<?php

namespace App\Http\Controllers\SubAgent\Setting;

use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\AccountStoreHours;
use App\Model\AccountStoreIp;
use Illuminate\Http\Request;
use Validator;
use Session;
use Auth;
use Log;
use Excel;
use Carbon\Carbon;

/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 10/02/19
 * Time: 3:16 PM
 */

class StoreController extends Controller
{

    public function show() {

//        dd(\request()->ip());
        $account_id = Auth::user()->account_id;

        $mon = AccountStoreHours::where('account_id', $account_id)->where('day', 'Mon')->first();
        $tue = AccountStoreHours::where('account_id', $account_id)->where('day', 'Tue')->first();
        $wed = AccountStoreHours::where('account_id', $account_id)->where('day', 'Wed')->first();
        $thu = AccountStoreHours::where('account_id', $account_id)->where('day', 'Thu')->first();
        $fri = AccountStoreHours::where('account_id', $account_id)->where('day', 'Fri')->first();
        $sat = AccountStoreHours::where('account_id', $account_id)->where('day', 'Sat')->first();
        $sun = AccountStoreHours::where('account_id', $account_id)->where('day', 'Sun')->first();

        $ips = AccountStoreIp::where('account_id', $account_id)->get();

        $account_info = Account::where('id', $account_id)->first();
        $tz_name = '';
        switch ($account_info->time_zone){
            case null:
                $tz_name = '';
                break;
            case '0':
                $tz_name = 'Eastern Time Zone';
                break;
            case '1':
                $tz_name = 'Central Time Zone';
                break;
            case '2':
                $tz_name = 'Mountain Time Zone';
                break;
            case '3':
                $tz_name = 'Pacific Time Zone';
                break;
            case '4':
                $tz_name = 'Alaska Time Zone';
                break;
            case '6':
                $tz_name = 'Hawaii Time Zone';
                break;
        }

        if($account_info->time_zone == ''){
            $time_zone = 's';
        }elseif ($account_info->time_zone == 0){
            $time_zone = '0';
        }else{
            $time_zone = $account_info->time_zone;
        }

        return view('sub-agent.setting.store',[
            'account'   => $account_id,
            'mon' => $mon,
            'tue' => $tue,
            'wed' => $wed,
            'thu' => $thu,
            'fri' => $fri,
            'sat' => $sat,
            'sun' => $sun,
            'ips' => $ips,
            'time_zone' => $time_zone,
            'tz_name'   => $tz_name
        ]);
    }

    public function update(Request $request) {

        try {

            $account_id = Auth::user()->account_id;
            $day        = ucfirst($request->day);

            $ash = AccountStoreHours::where('account_id', $account_id)
                                    ->where('day', '=', $day)
                                    ->first();

            $stime = date( "H:i:s", strtotime( $request->stime ) );
            $etime = date( "H:i:s", strtotime( $request->etime ) );

            if(empty($ash)){
                $new_ash = new AccountStoreHours();
                $new_ash->account_id    = $account_id;
                $new_ash->day           = $day;
                $new_ash->stime         = $stime;
                $new_ash->etime         = $etime;
                $new_ash->cdate         = Carbon::now();
                $new_ash->save();

            }else{
                $ash->stime = $stime;
                $ash->etime = $etime;
                $ash->udate = Carbon::now();
                $ash->updated_by = Auth::user()->user_id;
                $ash->update();
            }

            return response()->json([
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function remove(Request $request) {

        try {

            $account_id = Auth::user()->account_id;
            $day        = ucfirst($request->day);

            AccountStoreHours::where('account_id', $account_id)->where('day', '=', $day)->delete();

            return response()->json([
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function add_ip(Request $request) {

        try {

            $account_id = Auth::user()->account_id;
            $ip         = $request->ip;
            $comment    = $request->comment;

            $new_asi = new AccountStoreIp();
            $new_asi->account_id    = $account_id;
            $new_asi->ip            = $ip;
            $new_asi->comment       = $comment;
            $new_asi->cdate         = Carbon::now();
            $new_asi->save();

            return response()->json([
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function remove_ip(Request $request) {

        try {

            $id         = $request->id;

            AccountStoreIp::where('id', $id)->delete();

            return response()->json([
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function update_ip(Request $request) {

        try {

            $id         = $request->id;
            $ip         = $request->ip;
            $comment    = $request->comment;

            $asi = AccountStoreIp::where('id', $id)->first();

            $asi->ip        = $ip;
            $asi->comment   = $comment;
            $asi->udate = Carbon::now();
            $asi->updated_by = Auth::user()->user_id;
            $asi->update();

            return response()->json([
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function update_tz(Request $request) {

        try {

            $time_zone  = $request->time_zone;
            $account_id = Auth::user()->account_id;

            $acct = Account::where('id', $account_id)->first();

            $acct->time_zone = $time_zone;
            $acct->update();

            return response()->json([
                'msg'   => ''
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

}