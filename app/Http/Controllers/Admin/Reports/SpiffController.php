<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 1/19/18
 * Time: 2:23 PM
 */

namespace App\Http\Controllers\Admin\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Product;
use App\Model\SpiffTrans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class SpiffController extends Controller
{

    public function show(Request $request) {

        $sdate = $request->get('sdate', Carbon::today()->startOfWeek()->format('Y-m-d'));
        $edate = $request->get('edate', Carbon::today()->format('Y-m-d'));

        if (empty($sdate)) {
            $sdate = Carbon::today()->startOfWeek()->format('Y-m-d');
        }

        if (empty($edate)) {
            $edate = Carbon::today()->format('Y-m-d');
        }

        $login_account = Account::find(Auth::user()->account_id);
        if (empty($login_account)) {
            return redirect('/admin/error')->with([
                'error_msg' => 'Your session has been expired. Please login again.'
            ]);
        }

        $query = SpiffTrans::join('accounts', function($join) use ($login_account) {
                $join->on('accounts.id', 'spiff_trans.account_id');
                $join->where('accounts.path', 'like', $login_account->path . '%');
                if ($login_account->type != 'L') {
                    $join->where('accounts.id', $login_account->id);
                }
            })->join('transaction', 'spiff_trans.trans_id', '=', 'transaction.id')
            ->Leftjoin("stock_sim", function($join) {
                $join->on('transaction.sim', 'stock_sim.sim_serial')
                    ->where('transaction.product_id', 'stock_sim.product');
              });

        $query2 = SpiffTrans::join('accounts', function($join) use ($login_account) {
                $join->on('accounts.id', 'spiff_trans.account_id');
                $join->where('accounts.path', 'like', $login_account->path . '%');
                if ($login_account->type != 'L') {
                    $join->where('accounts.id', $login_account->id);
                }
            })->where('trans_id',0)
            ->where('note','Bonus By Account')
            ->select("spiff_trans.id as id",
            "spiff_trans.account_id as account_id",
            'accounts.type as account_type',
            "accounts.name as account_name" ,
            "accounts.parent_id as parent_id" ,
            "spiff_trans.type as type",
            "spiff_trans.trans_id as trans_id",
            "spiff_trans.phone as phone",
            "spiff_trans.product_id as product_id",
            "spiff_trans.denom as denom",
            "spiff_trans.account_type as spiff_account_type",
            "spiff_trans.spiff_month as spiff_month",
            "spiff_trans.spiff_amt as spiff_amt",
            "spiff_trans.type as is_byos",
            "spiff_trans.note as note",
            "spiff_trans.cdate as cdate");

        if (!empty($sdate)) {
            $query = $query->where('spiff_trans.cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
            $query2 = $query2->where('spiff_trans.cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('spiff_trans.cdate', '<=', Carbon::parse($edate . ' 23:59:59'));
            $query2 = $query2->where('spiff_trans.cdate', '<=', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->phone)) {
            $query = $query->where('spiff_trans.phone', $request->phone);
            $query2 = $query2->where('spiff_trans.phone', $request->phone);
        }

        if (!empty($request->trans_id)) {
            $query = $query->where('spiff_trans.trans_id', $request->trans_id);

        }

        if (!empty($request->account_id)) {
            $query = $query->where('spiff_trans.account_id', $request->account_id);
            $query2 = $query2->where('spiff_trans.account_id', $request->account_id);
        }

        if (!empty($request->spiff_account_type)) {
            $query = $query->where('spiff_trans.account_type', $request->spiff_account_type);
            $query2 = $query2->where('spiff_trans.account_type', $request->spiff_account_type);
        }

        if (!empty($request->product)) {
            $query = $query->where('spiff_trans.product_id', $request->product);
            $query2 = $query2->where('spiff_trans.product_id', $request->product);
        }

        if (!empty($request->carrier)) {
            $products = Product::where('carrier', $request->carrier)->where('activation', 'Y')->select('id')->get();
            $product = [];
            foreach ($products as $p){
                array_push($product, $p->id);
            }
            $query = $query->whereIn('spiff_trans.product_id', $product);
            $query2 = $query2->where('spiff_trans.product_id', $product);
        }

        $data = $query->select(
            'spiff_trans.id as id',
            'spiff_trans.account_id as account_id',
            'accounts.type as account_type',
            'accounts.name as account_name',
            "accounts.parent_id as parent_id" ,
            'spiff_trans.type as type',
            'spiff_trans.trans_id as trans_id',
            'spiff_trans.phone as phone',
            'spiff_trans.product_id as product_id',
            'spiff_trans.denom as denom',
            'spiff_trans.account_type as spiff_account_type',
            'spiff_trans.spiff_month as spiff_month',
            'spiff_trans.spiff_amt as spiff_amt',
            'stock_sim.is_byos as is_byos',
            'spiff_trans.note as note',
            'spiff_trans.cdate as cdate'
        )
            ->union($query2)
            ->orderBy('id', 'desc')
            ->get();

        $count = $data->count();
        $denom_total = $data->sum('denom');

        $amt_total = $data->where('type', 'S')->sum('spiff_amt');
        $amt_total_n = $data->where('type', 'V')->sum('spiff_amt');
        $amt_total = $amt_total - $amt_total_n;

        if ($request->excel == 'Y') {
            //$data = $query//->orderBy('spiff_trans.cdate', 'desc')
            //->get(['spiff_trans.*','stock_sim.is_byos', 'accounts.parent_id']);
            Excel::create('spiff_report', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {
                        $parent = Account::find($a->parent_id);
                        if( !empty($parent) ) {
                            $wizard = new PHPExcel_Helper_HTML;

                            $reports[] = [
                                'Spiff.ID' => $a->id,
                                'Parent #' => $parent->id,
                                'Parent Name' => $parent->name,
                                'Account #' => $a->account_id,
                                'Account Name' => $a->account_name,
                                'Type' => $a->type_name,
                                'Tx.ID' => $a->trans_id,
                                'Phone' => $a->phone,
                                'Product' => $a->product,
                                'Denom($)' => '$' . number_format($a->denom, 2),
                                'Spiff.Account.Type' => $wizard->toRichTextObject(Helper::get_hierarchy_img($a->spiff_account_type)),
                                'Spiff.Month' => $a->spiff_month,
                                'Spiff.Amt($)' => '$' . ($a->type_name == 'Void' ? '-'.number_format($a->spiff_amt, 2) : number_format($a->spiff_amt, 2)),
                                'Is.BYOS' => ($a->is_byos == 'Y' ? 'Yes' : 'No'),
                                'Note'  => $a->note,
                                'Date' => $a->cdate
                            ];
                        }

                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

//        $spiff_amt = $query->sum(DB::raw("if(spiff_trans.type = 'S', spiff_trans.spiff_amt, -spiff_trans.spiff_amt)"));

//        $data = $query->orderBy('spiff_trans.cdate', 'desc')->paginate(15,['spiff_trans.*', 'stock_sim.is_byos']);

        $carriers = Carrier::where('has_activation', 'Y')->get();
        $products = Product::where('status', 'A')->where('activation', 'Y')->get();

        return view('admin.reports.spiff', [
            'data' => $data,
            'count' => $count,
            'denom_total' => $denom_total,
            'amt_total' => $amt_total,
            'sdate' => $sdate,
            'edate' => $edate,
            'quick' => $request->quick,
            'phone' => $request->phone,
            'trans_id' => $request->trans_id,
            'account_id' => $request->account_id,
            'spiff_account_type' => $request->spiff_account_type,
            'carriers' => $carriers,
            'carrier' => $request->carrier,
            'products' => $products,
            'product' => $request->product
//            'spiff_amt' => $spiff_amt
        ]);
    }

}