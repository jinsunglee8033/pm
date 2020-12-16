<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 2/25/20
 * Time: 10:38 AM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\RebateProcessor;
use App\Model\AccountFee;
use App\Model\BoostFee;
use App\Model\ConsignmentVendor;
use App\Model\CricketFee;
use App\Model\MetroFee;
use App\Model\Product;
use App\Model\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Model\Account;
use App\Model\AccountActivationLimit;
use App\Lib\Helper;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class VendorConsignmentController extends Controller
{
    public function show(Request $request)
    {

//        $accounts = Account::leftJoin('consignment_vendor', function ($join){
//            $join->on('accounts.id', 'consignment_vendor.account_id');
//        });

        $accounts = Account::whereIn('type', ['M', 'D'])->orderBy('path', 'asc');

        if (!empty($request->type)) {
            if ($request->include_sub_account == 'Y') {
                $types = [];
                switch ($request->type) {
                    case 'L':
                        $types = ['L', 'M', 'D', 'S'];
                        break;
                    case 'M':
                        $types = ['M', 'D', 'S'];
                        break;
                    case 'D':
                        $types = ['D', 'S'];
                        break;
                    case 'S':
                        $types = ['S'];
                        break;
                }
                $accounts = $accounts->whereIn('type', $types);
            } else {
                $accounts = $accounts->where('type', $request->type);
            }
        }

        if (!empty($request->acct_id)) {
            $target_account = Account::find($request->acct_id);
            if ($request->include_sub_account_id == 'Y') {
                $accounts = $accounts->where('path', 'like', $target_account->path . '%');
            } else {
                $accounts = $accounts->where('id', $request->acct_id);
            }
        }

        if (!empty($request->acct_name)) {
            $accounts = $accounts->whereRaw("lower(name) like ?", '%' . strtolower($request->acct_name) . '%');
        }

        $accounts = $accounts->paginate(100);

        return view('admin.settings.vendor-consignment', [
            'accounts'  => $accounts,
            'type'  => $request->type,
            'acct_id'    => $request->acct_id,
            'acct_name' => $request->acct_name,
            'include_sub_account' => $request->include_sub_account,
            'balance' => $request->balance
        ]);
    }

}