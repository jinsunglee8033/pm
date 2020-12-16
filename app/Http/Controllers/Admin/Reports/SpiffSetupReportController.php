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

class SpiffSetupReportController extends Controller
{

    public function show(Request $request)
    {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/admin/error')->with([
                'error_msg' => 'Your session has been expired. Please login again.'
            ]);
        }

        if ($account->show_spiff_setup_report != 'Y'){
            return redirect('/admin/error')->with([
                'error_msg' => 'Can not access this page.'
            ]);
        }

        $spiff_template = $account->spiff_template;

        $query = "select * from spiff_setup
                        inner join product on product.id = spiff_setup.product_id
                        where spiff_setup.template ='$spiff_template'";

        if(!empty($request->carrier)){
            $query .= " and spiff_setup.product_id in (select id from product where carrier = '$request->carrier') ";
        }

        $query .= " order by carrier, name, denom ASC ";
        $data = DB::select($query);

        $carriers = Carrier::where('has_activation', 'Y')->get();

        return view('admin.reports.spiff-setup', [
            'data' => $data,
            'carrier' => $request->carrier,
            'carriers' => $carriers
        ]);
    }

}