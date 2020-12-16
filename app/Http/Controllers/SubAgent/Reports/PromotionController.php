<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/27/18
 * Time: 11:47 AM
 */

namespace App\Http\Controllers\SubAgent\Reports;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\Promotion;
use App\Model\PromotionCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class PromotionController extends Controller
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

        $query = Promotion::join('accounts', function($join) use ($login_account) {
            $join->on('accounts.id', 'promotion.account_id');
            $join->where('accounts.path', 'like', $login_account->path . '%');
            if ($login_account->type != 'L') {
                $join->where('accounts.id', $login_account->id);
            }
        });

        if (!empty($sdate)) {
            $query = $query->where('promotion.cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('promotion.cdate', '<', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->category_id)) {
            $query = $query->where('promotion.category_id', $request->category_id);
        }

        if (!empty($request->account_id)) {
            $query = $query->where('accounts.id', $request->account_id);
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('promotion.cdate', 'desc')->select('promotion.*', 'accounts.type as account_type', 'accounts.name as account_name')->get();
            Excel::create('promotion_report', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'Type' => $a->type_name,
                            'Category' => $a->category_name,
                            'Parent' => $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id)),
                            'Account' => $wizard->toRichTextObject('<span>' . Helper::get_hierarchy_img($a->account_type) . '</span>' . $a->account_name . ' ( ' . $a->account_id . ' )'),
                            'Amount($)' => '$' . number_format($a->amount, 2),
                            'Date' => $a->cdate,
                            'By' => $a->created_by
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $total = $query->sum(DB::raw("if(promotion.type = 'C', promotion.amount, -promotion.amount)"));

        $data = $query->orderBy('promotion.cdate', 'desc')->select('promotion.*', 'accounts.type as account_type', 'accounts.name as account_name')->paginate();

        $categories = PromotionCategory::all();

        return view('sub-agent.reports.promotion', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'category_id' => $request->category_id,
            'account_id' => $request->account_id,
            'total' => $total,
            'categories' => $categories
        ]);
    }
}