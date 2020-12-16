<?php

namespace App\Http\Controllers\SubAgent\Tools;

use App\Lib\gen;
use App\Lib\Helper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class GenToolsController
{
    public function show() {

        return view('sub-agent.tools.gen');
    }

    public function puk(Request $request){

        $res = gen::GetCustomerInfo($request->mdn);
        Helper::log('##### GET CUSTOMER INFO ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }
        
        $res = gen::GetPuk($res['esn_number']);
        Helper::log('##### GET PUK/MSL/MSID ###', $res);

        if (!empty($res['error_code'])) {
            return response()->json([
                'code'  => '-1',
                'msg' => $res['error_msg']
            ]);
        }

        return response()->json([
            'code'  => '0',
            'esn'   => $res['esn'],
            'msl'   => $res['msl'],
            'msid'  => $res['msid'],
            'mdn'   => $res['mdn'],
            'msg'   => ''
        ]);
    }

}