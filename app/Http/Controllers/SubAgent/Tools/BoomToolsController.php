<?php

namespace App\Http\Controllers\SubAgent\Tools;

use App\Lib\boom;
use App\Lib\gen;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\BoomSimSwap;
use App\Model\State;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class BoomToolsController
{
    public function show() {

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

        $states = State::all();

        return view('sub-agent.tools.boom')->with([
//            'transactions'  => $transactions,
            'states'        => $states,
            'account'       => $account
        ]);
    }

    public function post(Request $request){

        $network    = $request->network;
        $phone      = $request->phone;
        $sim        = $request->sim;

        $res = boom::changeSim($network, $phone, $sim);

        $bss = new BoomSimSwap();
        $bss->account_id = Auth::user()->account_id;
        $bss->network   = $network;
        $bss->phone     = $phone;
//            $bss->sim       = '';
        $bss->target_sim    = $sim;

        if (!empty($res['error_code'])) {
            $bss->result    = 'F';
            $bss->error_msg = $res['error_msg'];
            $bss->cdate     = Carbon::now();
            $bss->save();

            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }else{
            $bss->result    = 'S';
            $bss->error_msg = $res['error_msg'];
            $bss->cdate     = Carbon::now();
            $bss->save();
        }

        return response()->json([
            'code'  => '0',
            'msg'   => ''
        ]);
    }

}