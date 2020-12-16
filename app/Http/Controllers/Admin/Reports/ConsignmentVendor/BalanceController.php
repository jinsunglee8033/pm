<?php
/**
 * Created by Royce.
 * Date: 6/27/18
 */

namespace App\Http\Controllers\Admin\Reports\ConsignmentVendor;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\ConsignmentVendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BalanceController extends Controller
{

    public function show(Request $request) {

        if (empty($request->type)) {
            $data = ConsignmentVendor::where('account_id', Auth::user()->account_id)->orderBy('id', 'desc')->paginate(20);
        } else {
            $data = ConsignmentVendor::where('account_id', Auth::user()->account_id)->where('type', $request->type)->orderBy('id', 'desc')->paginate(20);
        }

        return view('admin.reports.consignment-vendor.balance', [
            'data' => $data,
            'type' => $request->type
        ]);
    }

}