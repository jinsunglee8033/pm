<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 10/10/19
 * Time: 04:58 PM
 */

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\AccountUserHours;
use App\Model\AccountUserIp;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserHourController extends Controller
{

    public function show() {

        $account_id = Auth::user()->account_id;

        $users = User::leftJoin('account_user_hours', 'account_user_hours.user_id', 'users.user_id')
            ->where('users.account_id', $account_id)
            ->where('users.status', 'A')
            ->select('users.*', 'account_user_hours.*', 'users.user_id as u_id')
            ->get();

        $account_info = Account::where('id', $account_id)->first();

        $ips = AccountUserIp::where('account_id', $account_id)->get();

        return view('admin.account.user-hour',[
            'account_id'    => $account_id,
            'users'         => $users,
            'account_info'  => $account_info,
            'ips'           => $ips
        ]);
    }

    public function update(Request $request) {

        try {

            $account_id = Auth::user()->account_id;
            $user_id    = $request->user;
            $day        = ucfirst($request->day);

            $auh = AccountUserHours::where('account_id', $account_id)
                ->where('user_id', $user_id)
//                                    ->where('day', '=', $day)
                ->first();

            $stime = date( "H:i:s", strtotime( $request->stime ) );
            $etime = date( "H:i:s", strtotime( $request->etime ) );

            if(empty($auh)){
                $new_auh = new AccountUserHours();
                $new_auh->account_id    = $account_id;
                $new_auh->user_id       = $user_id;
                if ($day == 'Mon') {
                    $new_auh->mon_stime         = $stime;
                    $new_auh->mon_etime         = $etime;
                }elseif ($day == 'Tue'){
                    $new_auh->tue_stime         = $stime;
                    $new_auh->tue_etime         = $etime;
                }elseif ($day == 'Wed'){
                    $new_auh->wed_stime         = $stime;
                    $new_auh->wed_etime         = $etime;
                }elseif ($day == 'Thu'){
                    $new_auh->thu_stime         = $stime;
                    $new_auh->thu_etime         = $etime;
                }elseif ($day == 'Fri'){
                    $new_auh->fri_stime         = $stime;
                    $new_auh->fri_etime         = $etime;
                }elseif ($day == 'Sat'){
                    $new_auh->sat_stime         = $stime;
                    $new_auh->sat_etime         = $etime;
                }elseif ($day == 'Sun'){
                    $new_auh->sun_stime         = $stime;
                    $new_auh->sun_etime         = $etime;
                }
                $new_auh->cdate         = Carbon::now();
                $new_auh->save();

            }else{
                if ($day == 'Mon') {
                    $auh->mon_stime         = $stime;
                    $auh->mon_etime         = $etime;
                }elseif ($day == 'Tue'){
                    $auh->tue_stime         = $stime;
                    $auh->tue_etime         = $etime;
                }elseif ($day == 'Wed'){
                    $auh->wed_stime         = $stime;
                    $auh->wed_etime         = $etime;
                }elseif ($day == 'Thu'){
                    $auh->thu_stime         = $stime;
                    $auh->thu_etime         = $etime;
                }elseif ($day == 'Fri'){
                    $auh->fri_stime         = $stime;
                    $auh->fri_etime         = $etime;
                }elseif ($day == 'Sat'){
                    $auh->sat_stime         = $stime;
                    $auh->sat_etime         = $etime;
                }elseif ($day == 'Sun'){
                    $auh->sun_stime         = $stime;
                    $auh->sun_etime         = $etime;
                }
                $auh->udate = Carbon::now();
                $auh->updated_by = $user_id;
                $auh->update();
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
            $user_id    = $request->user;

            $auh = AccountUserHours::where('account_id', $account_id)->where('user_id', $user_id)->first();

            if($day == 'Mon'){
                $auh->mon_stime = null;
                $auh->mon_etime = null;
            }elseif($day == 'Tue'){
                $auh->tue_stime = null;
                $auh->tue_etime = null;
            }elseif($day == 'Wed'){
                $auh->wed_stime = null;
                $auh->wed_etime = null;
            }elseif($day == 'Thu'){
                $auh->thu_stime = null;
                $auh->thu_etime = null;
            }elseif($day == 'Fri'){
                $auh->fri_stime = null;
                $auh->fri_etime = null;
            }elseif($day == 'Sat'){
                $auh->sat_stime = null;
                $auh->sat_etime = null;
            }elseif($day == 'Sun'){
                $auh->sun_stime = null;
                $auh->sun_etime = null;
            }

            $auh->save();

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
            $user_id    = $request->user_id;
            $ip         = $request->ip;
            $comment    = $request->comment;

            $new_aui = new AccountUserIp();
            $new_aui->account_id    = $account_id;
            $new_aui->user_id       = $user_id;
            $new_aui->ip            = $ip;
            $new_aui->comment       = $comment;
            $new_aui->cdate         = Carbon::now();
            $new_aui->save();

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

            $aui = AccountUserIp::where('id', $id)->first();
            $aui->udate = Carbon::now();
            $aui->updated_by = Auth::user()->user_id;
            $aui->update();

            AccountUserIp::where('id', $id)->delete();

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