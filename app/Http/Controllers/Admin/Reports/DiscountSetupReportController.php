<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 04/17/20
 * Time: 01:21 PM
 */

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\Product;
use App\Model\RateDetail;
use App\Model\SpiffSetup;
use App\Model\SpiffSetupSpecial;
use App\Model\SpiffTemplate;
use App\Model\SpiffTemplateOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class DiscountSetupReportController extends Controller
{

    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/admin/error')->with([
                'error_msg' => 'Your session has been expired. Please login again.'
            ]);
        }

        if ($account->show_discount_setup_report != 'Y'){
            return redirect('/admin/error')->with([
                'error_msg' => 'Can not access this page.'
            ]);
        }

        $query = RateDetail::join('denomination', 'denomination.id', '=', 'rate_detail.denom_id')
            ->join('product', 'product.id', '=', 'denomination.product_id')
            ->where('rate_detail.rate_plan_id', $account->rate_plan_id)
            ->where('denomination.status', 'A')
            ->where('product.status', 'A')
        ;

        if (!empty($request->action)) {
            $query = $query->where('rate_detail.action', $request->action);
        }

        if (!empty($request->carrier)) {

            $query = $query->where('product.carrier', $request->carrier);
        }

        $data = $query->select(
            'product.name',
            'product.carrier',
            'denomination.product_id',
            'denomination.denom',
            'denomination.status',
            'rate_detail.id',
            'rate_detail.rate_plan_id',
            'rate_detail.denom_id',
            'rate_detail.action',
            'rate_detail.rates'
        )
            ->orderBy('rate_detail.action', 'desc')
            ->orderBy('product.carrier', 'asc')
            ->orderBy('denomination.product_id', 'asc')
            ->orderBy('denomination.denom', 'ASC')
            ->get();

        $carriers = Carrier::orderBy('name', 'asc')->get();

        return view('admin.reports.discount-setup', [
            'data'      => $data,
            'carriers'  => $carriers,
            'action'    => $request->action,
            'carrier'   => $request->carrier,
            'account_type' => $account->type
        ]);

    }

}