<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/5/19
 * Time: 2:10 PM
 */

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\Permission;
use App\Mail\UserCreated;
use App\Mail\UserUpdated;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\GenFee;
use App\Model\LoginHistory;
use App\Model\Product;
use App\Model\Role;
use App\Model\SpiffSetup;
use App\Model\SpiffTemplate;
use App\Model\VWAccountSpiffATT;
use App\Model\VWAccountSpiffBoom;
use App\Model\VWAccountSpiffFreeup;
use App\Model\VWAccountSpiffGEN;
use App\Model\VWAccountSpiffH2o;
use App\Model\VWAccountSpiffLiberty;
use App\Model\VWAccountSpiffLyca;
use App\Model\VWAccountSpiffTotal;
use Illuminate\Http\Request;
use App\Model\Account;
use App\Model\AccountAuthority;
use App\Model\AccountFile;
use App\Model\AccountStoreType;
use App\Model\ATTBatchFee;
use App\Model\ATTBatchFeeBase;
use App\Model\State;
use App\Model\Transaction;
use App\Model\StoreType;
use App\Model\RatePlan;
use App\Model\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Session;
use Illuminate\Support\Facades\Input;
use Maatwebsite\Excel\Facades\Excel;
use Log;
use App\User;
use Mail;
use DB;

/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/15/17
 * Time: 5:54 PM
 */
class SpiffController extends Controller
{
    public function show(Request $request)
    {

//        $carrier = empty($request->carrier) ? 'AT&T' : $request->carrier;
        $carrier = $request->carrier;

        switch ($carrier) {
            case 'AT&T':
                $query = VWAccountSpiffATT::query();
                break;
            case 'FreeUP':
                $query = VWAccountSpiffFreeup::query();
                break;
            case 'GEN Mobile':
                $query = VWAccountSpiffGEN::query();
                break;
            case 'Boom Mobile':
                $query = VWAccountSpiffBoom::query();
                break;
            case 'H2O':
                $query = VWAccountSpiffH2o::query();
                break;
            case 'Liberty Mobile':
                $query = VWAccountSpiffLiberty::query();
                break;
            case 'Lyca':
                $query = VWAccountSpiffLyca::query();
                break;
            default:
                $query = VWAccountSpiffTotal::query();
        }

        $query->whereRaw("(product_id, denom) in (select product_id, denom from denomination where status = 'A')");

        if (!empty($request->product_id)) {
            $query->where('product_id', $request->product_id);


            if (!empty($request->denom)) {
                $query->where('denom', $request->denom);
            }
        }

        if (!empty($request->account)) {
            $query->whereRaw("(account_id = '" . $request->account . "' or parent_id = '" . $request->account . "' or master_id = '" . $request->account . "' or lower(account_name) like '%" . strtolower($request->account) . "%' or lower(d_account_name) like '%" . strtolower($request->account) . "%' or lower(m_account_name) like '%" . strtolower($request->account) . "%')");
        }

        if (!empty($request->template)) {
            $query->whereRaw(
              "(template in (select id from spiff_template where account_type = 'S' and lower(template) like '%" . strtolower($request->template) . "%') or d_template in (select id from spiff_template where account_type = 'D' and lower(template) like '%" . strtolower($request->template) . "%') or m_template in (select id from spiff_template where account_type = 'M' and lower(template) like '%" . strtolower($request->template) . "%') )"
            );
        }

        if (isset($request->s_spiff)) {
            $query->where('spiff', $request->s_spiff_type, $request->s_spiff);
        }

        if (isset($request->d_spiff)) {
            $query->where('d_spiff', $request->d_spiff_type, $request->d_spiff);
        }

        if (isset($request->m_spiff)) {
            $query->where('m_spiff', $request->m_spiff_type, $request->m_spiff);
        }

        if ($request->excel == 'Y') {

            $spiffs = $query->orderBy('has_issue', 'desc')
                ->orderBy('account_id', 'asc')
                ->orderBy('product_id', 'asc')
                ->orderBY('denom', 'asc')
                ->get();

            Excel::create('spiff_lookup', function($excel) use($spiffs) {

                ini_set('memory_limit', '2048M');

                $excel->sheet('reports', function($sheet) use($spiffs) {

                    $data = [];
                    foreach ($spiffs as $o) {

                        $parent = Account::find($o->parent_id);
                        $product = Product::find($o->product_id);

                        $row = [
                          'Parent.ID' => $parent->id,
                          'Parent.Name' => $parent->name,
                          'Acct.ID' => $o->account_id,
                          'Acct.Name' => $o->account_name,
                          'Product' => $product->id . ', ' . $product->name,
                          'Denom' => $o->denom,
                          'S.Template' => empty($o->template) ? '' : \App\Model\SpiffTemplate::get_template_name($o->template),
                          'S.Spiff' => $o->spiff,
                          'D.Template' => empty($o->d_template) ? '' : \App\Model\SpiffTemplate::get_template_name($o->d_template),
                          'D.Spiff' => $o->d_spiff,
                          'M.Template' => empty($o->m_template) ? '' : \App\Model\SpiffTemplate::get_template_name($o->m_template),
                          'M.Spiff' => $o->m_spiff
                        ];

                        $data[] = $row;

                    }

                    $sheet->fromArray($data);

                });

            })->export('xlsx');

        }


        $spiffs = $query->orderBy('has_issue', 'desc')
            ->orderBy('account_id', 'asc')
            ->orderBy('product_id', 'asc')
            ->orderBY('denom', 'asc')
            ->paginate(20);

        $products = Product::where('carrier', $carrier)->where('status', 'A')->where('activation', 'Y')->get();
        $denoms = new \stdClass();
        if (!empty($request->product_id)) {
            $denoms = Denom::where('product_id', $request->product_id)->where('status', 'A')->get();
        }

        $carriers =  Carrier::where('has_activation', 'Y')->orderBy('name', 'asc')->get();

        return view('admin.account.spiff', [
            'carriers'  => $carriers,
            'carrier'   => $carrier,
            'product_id' => $request->product_id,
            'denom'     => $request->denom,
            'account'   => $request->account,
            'template'  => $request->template,
            's_spiff'   => $request->s_spiff,
            's_spiff_type'   => $request->s_spiff_type,
            'd_spiff'   => $request->d_spiff,
            'd_spiff_type'   => $request->d_spiff_type,
            'm_spiff'   => $request->m_spiff,
            'm_spiff_type'   => $request->m_spiff_type,
            'products'  => $products,
            'denoms'    => $denoms,
            'spiffs'    => $spiffs,
        ]);
    }
}