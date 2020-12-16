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
use App\Model\RebateTrans;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class RebateController extends Controller
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

        $query = RebateTrans::join('accounts', function($join) use ($login_account) {
            $join->on('accounts.id', 'rebate_trans.account_id');
            $join->where('accounts.path', 'like', $login_account->path . '%');
            if ($login_account->type != 'L') {
                $join->where('accounts.id', $login_account->id);
            }

        });

        if (!empty($sdate)) {
            $query = $query->where('rebate_trans.cdate', '>=', Carbon::parse($sdate . ' 00:00:00'));
        }

        if (!empty($edate)) {
            $query = $query->where('rebate_trans.cdate', '<=', Carbon::parse($edate . ' 23:59:59'));
        }

        if (!empty($request->phone)) {
            $query = $query->where('rebate_trans.phone', $request->phone);
        }

        if (!empty($request->trans_id)) {
            $query = $query->where('rebate_trans.trans_id', $request->trans_id);
        }

        if (!empty($request->account_id)) {
            $query = $query->where('accounts.id', $request->account_id);
        }

        if (!empty($request->rebate_account_type)) {
            $query = $query->where('rebate_trans.account_type', $request->rebate_account_type);
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('rebate_trans.cdate', 'desc')->select('rebate_trans.*')->get();
            Excel::create('rebate_report', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'Rebate.ID' => $a->id,
                            'Parent' => $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id)),
                            'Account' => $wizard->toRichTextObject('<span>' . Helper::get_hierarchy_img($a->account_type) . '</span>' . $a->account_name . ' ( ' . $a->account_id . ' )'),
                            'Type' => $a->type_name,
                            'Tx.ID' => $a->trans_id,
                            'Phone' => $a->phone,
                            'Product' => $a->product,
                            'Denom($)' => '$' . number_format($a->denom, 2),
                            'Rebate.Account.Type' => $wizard->toRichTextObject(Helper::get_hierarchy_img($a->rebate_account_type)),
                            'Spiff.Month' => $a->rebate_month,
                            'Spiff.Amt($)' => '$' . number_format($a->rebate_amt, 2),
                            'Date' => $a->cdate
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $rebate_amt = $query->sum(DB::raw("if(rebate_trans.type = 'S', rebate_trans.rebate_amt, -rebate_trans.rebate_amt)"));

        $data = $query->orderBy('rebate_trans.cdate', 'desc')->select('rebate_trans.*')->paginate();


        return view('admin.reports.rebate', [
            'data' => $data,
            'sdate' => $sdate,
            'edate' => $edate,
            'quick' => $request->quick,
            'phone' => $request->phone,
            'trans_id' => $request->trans_id,
            'account_id' => $request->account_id,
            'rebate_account_type' => $request->rebate_account_type,
            'rebate_amt' => $rebate_amt
        ]);
    }

}