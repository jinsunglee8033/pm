<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 4/5/17
 * Time: 10:04 AM
 */

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Model\State;
use App\Model\Transaction;
use Illuminate\Http\Request;
//use Illuminate\Http\Response;
use App\Model\Account;
use App\Model\AccountFileAtt;
use Validator;
use Session;
use Auth;
use Log;
use Excel;
use App\Lib\PDF;
use Carbon\Carbon;
use Response;
use URL;
use Helper;
use Illuminate\Support\Facades\DB;


class DocumentAttController extends Controller
{

    public function show(Request $request) {
        try {

            if (Auth::user()->account_type != 'L') {
                return redirect('/admin');
            }

            $accounts = Account::Leftjoin('account_files_att as v_afa', function($join) {
                $join->on('accounts.id', 'v_afa.account_id')
                    ->where('v_afa.type', 'FILE_ATT_VOID_CHECK');
            })
            ->Leftjoin('account_files_att as a_afa', function($join) {
                $join->on('accounts.id', 'a_afa.account_id')
                    ->where('a_afa.type', 'FILE_ATT_AGREEMENT');
            })
            ->Leftjoin('account_files_att as d_afa', function($join) {
                $join->on('accounts.id', 'd_afa.account_id')
                    ->where('d_afa.type', 'FILE_ATT_DRIVER_LICENSE');
            })
            ->Leftjoin('account_files_att as b_afa', function($join) {
                $join->on('accounts.id', 'b_afa.account_id')
                    ->where('b_afa.type', 'FILE_ATT_BUSINESS_CERTIFICATION');
            })
            ->Leftjoin(DB::raw('(select account_id, max(cdate) as all_cdate from account_files_att group by 1) as all_afa'), function($join) {
                $join->on('accounts.id', 'all_afa.account_id');
            })
            ->where('accounts.type', 'S');

            if (!empty($request->doc_status)) {

                switch ($request->doc_status) {
                    case '1':
                        $accounts = $accounts->whereRaw("
                            (
                                select count(*)
                                from account_files_att a
                                where a.account_id = accounts.id
                                and  a.type = 'FILE_ATT_AGREEMENT' and a.data is not null
                            ) = 1 and ifnull(doc_status_att, '') = ''
                        ");
                        break;
                    case '2':
                        $accounts = $accounts->where('doc_status_att', 2);
                        break;
                    case '3':
                        $accounts = $accounts->where('doc_status_att', 3);
                        break;
                    case '4':
                        $accounts = $accounts->where('doc_status_att', 4);
                        break;
                    case '5':
                        $accounts = $accounts->where('doc_status_att', 5);
                        break;
                    case '7':
                        $accounts = $accounts->where('doc_status_att', 7);
                        break;
                    case '8':
                        $accounts = $accounts->where('doc_status_att', 8);
                        break;
                    case '6':
                        $accounts = $accounts->whereNull('doc_status_att');
                        break;
                }
            }

            $search_option = array();

            if ($request->agreement == 'Y') {
                $search_option[] = 'FILE_ATT_AGREEMENT';
            }

            if ($request->license == 'Y') {
                $search_option[] = 'FILE_ATT_DRIVER_LICENSE';
            }

            if ($request->certification == 'Y') {
                $search_option[] = 'FILE_ATT_BUSINESS_CERTIFICATION';
            }

            if ($request->void == 'Y') {
                $search_option[] = 'FILE_ATT_VOID_CHECK';
            }

            $search_option_str = '';

            if (!empty($search_option)) {
                $option_count = count($search_option);

                $search_option_str = "('" . implode("', '", $search_option) . "')";
            }

            if ($request->reverse == 'Y' && $search_option_str != '') {
                $accounts = $accounts->whereRaw("
                    (
                    select count(*)
                    from account_files_att a
                    where a.account_id = accounts.id
                    and ( 
                        a.type in " . $search_option_str  . "
                        )
                    ) <> " . $option_count );
            }

            if ($request->reverse != 'Y' && $search_option_str != ''){
                $accounts = $accounts->whereRaw("
                    (
                    select count(*)
                    from account_files_att a
                    where a.account_id = accounts.id
                    and (
                        a.type in " . $search_option_str . "
                        )
                    ) = " . $option_count );
            }

//            if (!empty($request->acct_id)) {
//                $accounts = $accounts->where('accounts.id', $request->acct_id);
//            }

            if (!empty($request->acct_ids)) {
                $acct_ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
                $accounts = $accounts->whereIn('accounts.id', $acct_ids);
            }

            if (!empty($request->att_ids)) {
                $att_ids = preg_split('/[\ \r\n\,]+/', $request->att_ids);
                $accounts = $accounts->whereIn('accounts.att_tid', $att_ids)
                                    ->orWhereIn('accounts.att_tid2', $att_ids);
            }

            if (!empty($request->codes)) {
                $codes = preg_split('/[\ \r\n\,]+/', $request->codes);
                $accounts = $accounts->whereIn('att_dealer_code', $codes);
            }

//            if (!empty($request->code_like)) {
//                $accounts = $accounts->whereRaw('lower(att_dealer_code) like "%' . strtolower($request->code_like) . '%"');
//            }
            if (!empty($request->code_like)) {
                $accounts = $accounts->whereRaw('lower(att_dc_notes ) like "%' . strtolower($request->code_like) . '%"');
            }

            if (!empty($request->notes)) {
                $accounts = $accounts->whereRaw("lower(notes) like ?", '%' . strtolower($request->notes) . '%');
            }

            if (!empty($request->acct_name)) {
                $accounts = $accounts->whereRaw("lower(name) like ?", '%' . strtolower($request->acct_name) . '%');
            }

            if (!empty($request->state)) {
                $accounts = $accounts->where("state", $request->state);
            }

            if (!empty($request->city)) {
                $accounts = $accounts->whereRaw("lower(city) like ?", '%' . strtolower($request->city) . '%');
            }

            $sort = null;

            if ($request->sort == '3') {
                $accounts = $accounts->orderBy('v_cdate', 'asc');
            }else if ($request->sort == '4') {
                $accounts = $accounts->orderBy('v_cdate', 'desc');
            }else if ($request->sort == '5') {
                $accounts = $accounts->orderBy('a_cdate', 'asc');
            }else if ($request->sort == '6') {
                $accounts = $accounts->orderBy('a_cdate', 'desc');
            }else if ($request->sort == '7') {
                $accounts = $accounts->orderBy('d_cdate', 'asc');
            }else if ($request->sort == '8') {
                $accounts = $accounts->orderBy('d_cdate', 'desc');
            }else if ($request->sort == '9') {
                $accounts = $accounts->orderBy('b_cdate', 'asc');
            }else if ($request->sort == '10') {
                $accounts = $accounts->orderBy('b_cdate', 'desc');
            }else if ($request->sort == '11') {
                $accounts = $accounts->orderBy('all_cdate', 'asc');
            }else if ($request->sort == '12') {
                $accounts = $accounts->orderBy('all_cdate', 'desc');
            }

            if ($request->excel == 'Y') {
                $data = $accounts->select('accounts.*', 'v_afa.cdate as v_cdate', 'a_afa.cdate as a_cdate', 'd_afa.cdate as d_cdate', 'b_afa.cdate as b_cdate', 'all_afa.all_cdate as all_cdate')
                    ->orderBy('accounts.path', 'asc')->distinct()->get();
                Excel::create('ATT_Document' . date("mdY_h:i:s_A"), function($excel) use($data) {

                    $excel->sheet('reports', function($sheet) use($data) {

                        $reports = [];

                        foreach ($data as $a) {

                            $reports[] = [
                                'Account' => $a->id,
                                'Name' => $a->name,
                                'State' => $a->state,
                                'ATT'   => (!empty($a->att_tid) || !empty($a->att_tid2)) ? 'A' : '',
                                'ATT.TID' => $a->att_tid,
                                'ATT.TID2' => $a->att_tid2,
                                'Note' => $a->notes,
                                'created.At' => $a->cdate,
                                'Doc.Status' => $a->att_doc_status_name(),
                                'Agreement' => $a->a_cdate,
                                'Driver.License' => $a->d_cdate,
                                'Business.Certification' => $a->b_cdate,
                                'Void.Check' => $a->v_cdate,
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }

            $temp = $accounts->select('accounts.*', 'v_afa.cdate as v_cdate', 'a_afa.cdate as a_cdate', 'd_afa.cdate as d_cdate', 'b_afa.cdate as b_cdate', 'all_afa.all_cdate as all_cdate')
                ->orderBy('accounts.path', 'asc')->distinct()->get();
            $count = count($temp);

            $accounts = $accounts->select('accounts.*', 'v_afa.cdate as v_cdate', 'a_afa.cdate as a_cdate', 'd_afa.cdate as d_cdate', 'b_afa.cdate as b_cdate', 'all_afa.all_cdate as all_cdate')
            ->orderBy('accounts.path', 'asc')->distinct()->paginate(20);

            $states = State::orderBy('name', 'asc')->get();

            return view('admin.reports.document-att', [
                'accounts' => $accounts,
                'notes' => $request->notes,
                'doc_status' => $request->doc_status,
                'sort'      => $request->sort,
                'agreement' => $request->agreement,
                'license' => $request->license,
                'certification' => $request->certification,
                'void' => $request->void,
                'reverse' => $request->reverse,
//                'acct_id' => $request->acct_id,
                'acct_ids'  => $request->acct_ids,
                'att_ids'   => $request->att_ids,
                'acct_name' => $request->acct_name,
                'states'    => $states,
                'state'     => $request->state,
                'city'      => $request->city,
                'count'     => $count,
                'codes'     => $request->codes,
                'code_like' => $request->code_like
            ]);

        } catch (\Exception $ex) {
            throw $ex;
        }
    }


    public function pdf(Request $request) {
        try {

            if (empty($request->id)) {
                die("<script>alert('Invalid account ID provided');</script>");
            }

            $account = Account::find($request->id);
            if (empty($account)) {
                die("<script>alert('Invalid account ID provided');</script>");
            }

//            if (!$account->is_att_ready()) {
//                die("<script>alert('Provided account ID is not ATT ready');</script>");
//            }

            $license = AccountFileAtt::where('account_id', $account->id)
                ->where('type', 'FILE_ATT_DRIVER_LICENSE')
                ->first();

//            if (empty($license)) {
//                die("<script>alert('Driver License image does not exist');</script>");
//            }

            $certification = AccountFileAtt::where('account_id', $account->id)
                ->where('type', 'FILE_ATT_BUSINESS_CERTIFICATION')
                ->first();
//            if (empty($certification)) {
//                die("<script>alert('Business Certification image does not exist');</script>");
//            }

            $void = AccountFileAtt::where('account_id', $account->id)
                ->where('type', 'FILE_ATT_VOID_CHECK')
                ->first();
//            if (empty($void)) {
//                die("<script>alert('Void Check image does not exist');</script>");
//            }

            $agreement = AccountFileAtt::where('account_id', $account->id)
                ->where('type', 'FILE_ATT_AGREEMENT')
//                ->where('signed', 'Y')
                ->first();
//            if (empty($agreement)) {
//                die("<script>alert('Att Retailer agreement does not exist');</script>");
//            }

            $pdf = new PDF();

            $pdf->SetFont('Arial','',11);

            //// Driver License
            if(!empty($license)) {
                $pdf->addPage();

                // (LOGO) //
                $url_logo = URL::to("/") . "/file/att_view/1";
                $logo_img_path = "/tmp/file_1";
                file_put_contents($logo_img_path, fopen($url_logo, "r"));
                Helper::image_disable_interlacing($logo_img_path);
                $pdf->Image($logo_img_path, 0, 10, 90, 20, 'png');

                $pdf->Ln(20);
                $pdf->cell(0, 5, 'Submit by Perfect Mobile. Inc', 0, 1);
                $pdf->Ln(5);
                $pdf->cell(0, 5, 'SPP# ' . $account->id, 0, 1);
                $pdf->Ln(5);
                $pdf->cell(0, 5, 'DATE: ' . Carbon::today()->format('m/d/Y'), 0, 1);
                $pdf->cell(0, 5, 'SUB-AGENT NAME : ' . $account->name, 0, 1);
                $pdf->cell(0, 5, 'SUB-AGENT PHONE # : ' . $account->office_number, 0, 1);
                $pdf->cell(0, 5, 'SUB-AGENT EMAIL : ' . $account->email, 0, 1);
                $pdf->cell(0, 5, 'CONTACT : ' . $account->contact, 0, 1);
                $pdf->Ln(5);
                $pdf->cell(0, 5, 'DRIVER LICENSE', 0, 1);

                $extend = strtolower(substr($license->file_name, -3));
                $license_img_path = "/tmp/file_" . $license->id;
                if ($extend == 'pdf') {
                    $url = URL::to("/") . "/file/att_view/" . $license->id;
                    $file_path = "/tmp/file_" . $license->id;
                    $out_path = "/tmp/file_" . $license->id . ".pdf";
                    file_put_contents($file_path, fopen($url, 'r'));
                    exec("qpdf --decrypt $file_path $out_path");
                    $pdf->setSourceFile($out_path);
                    $pagecount = $pdf->setSourceFile($out_path);
                    $currentPage = 1;
                    while ($currentPage <= $pagecount) {
                        $pdf->addPage();
                        $tplIdx = $pdf->importPage($currentPage);
                        $pdf->useTemplate($tplIdx);
                        $currentPage++;
                    }
                } else {
                    $url = URL::to("/") . "/file/att_view/" . $license->id;
                    $license_img_path = "/tmp/file_" . $license->id;
                    file_put_contents($license_img_path, fopen($url, "r"));
                    Helper::image_disable_interlacing($license_img_path);
                    $type = Helper::get_image_type($url);
                    $pdf->cell(0, 90, '', 1, 1);
                    $pdf->centreImage($license_img_path, 90, $type);
                }
            }


            //// Business Certification
            if(!empty($certification)) {
                $pdf->addPage();

                // (LOGO) //
                $url_logo = URL::to("/") . "/file/att_view/1";
                $logo_img_path = "/tmp/file_1";
                file_put_contents($logo_img_path, fopen($url_logo, "r"));
                Helper::image_disable_interlacing($logo_img_path);
                $pdf->Image($logo_img_path, 0, 10, 90, 20, 'png');

                $pdf->Ln(20);
                $pdf->cell(0, 5, 'Submit by Perfect Mobile. Inc', 0, 1);
                $pdf->Ln(5);
                $pdf->cell(0, 5, 'Business Certification', 0, 1);

                $extend = strtolower(substr($certification->file_name, -3));
                $certification_img_path = "/tmp/file_" . $certification->id;
                if ($extend == 'pdf') {
                    $url = URL::to("/") . "/file/att_view/" . $certification->id;
                    $file_path = "/tmp/file_" . $certification->id;
                    $out_path = "/tmp/file_" . $certification->id . ".pdf";
                    file_put_contents($file_path, fopen($url, 'r'));
                    exec("qpdf --decrypt $file_path $out_path");
                    $pdf->setSourceFile($out_path);
                    $pagecount = $pdf->setSourceFile($out_path);
                    $currentPage = 1;
                    while ($currentPage <= $pagecount) {
                        $pdf->addPage();
                        $tplIdx = $pdf->importPage($currentPage);
                        $pdf->useTemplate($tplIdx);
                        $currentPage++;
                    }
                } else {
                    $url = URL::to("/") . "/file/att_view/" . $certification->id;
                    $certification_img_path = "/tmp/file_" . $certification->id;
                    file_put_contents($certification_img_path, fopen($url, "r"));
                    Helper::image_disable_interlacing($certification_img_path);
                    $type = Helper::get_image_type($url);
                    $pdf->cell(0, 90, '', 1, 1);
                    $pdf->centreImage($certification_img_path, 50, $type);
                }
            }


            //// Void Check
            if(!empty($void)) {
                $pdf->addPage();

                // (LOGO) //
                $url_logo = URL::to("/") . "/file/att_view/1";
                $logo_img_path = "/tmp/file_1";
                file_put_contents($logo_img_path, fopen($url_logo, "r"));
                Helper::image_disable_interlacing($logo_img_path);
                $pdf->Image($logo_img_path, 0, 10, 90, 20, 'png');

                $pdf->Ln(20);
                $pdf->cell(0, 5, 'Submit by Perfect Mobile. Inc', 0, 1);
                $pdf->Ln(5);
                $pdf->cell(0, 5, 'Void Check', 0, 1);

                $extend = strtolower(substr($void->file_name, -3));
                $void_img_path = "/tmp/file_" . $void->id;
                if ($extend == 'pdf') {
                    $url = URL::to("/") . "/file/att_view/" . $void->id;
                    $file_path = "/tmp/file_" . $void->id;
                    $out_path = "/tmp/file_" . $void->id . ".pdf";
                    file_put_contents($file_path, fopen($url, 'r'));
                    exec("qpdf --decrypt $file_path $out_path");
                    $pdf->setSourceFile($out_path);
                    $pagecount = $pdf->setSourceFile($out_path);
                    $currentPage = 1;
                    while ($currentPage <= $pagecount) {
                        $pdf->addPage();
                        $tplIdx = $pdf->importPage($currentPage);
                        $pdf->useTemplate($tplIdx);
                        $currentPage++;
                    }
                } else {
                    $url = URL::to("/") . "/file/att_view/" . $void->id;
                    $void_img_path = "/tmp/file_" . $void->id;
                    file_put_contents($void_img_path, fopen($url, "r"));
                    Helper::image_disable_interlacing($void_img_path);
                    $type = Helper::get_image_type($url);
                    $pdf->cell(0, 90, '', 1, 1);
                    $pdf->centreImage($void_img_path, 50, $type);
                }
            }

            //// Agreement Form
            if(!empty($agreement)) {
                $url = URL::to("/") . "/file/att_view/" . $agreement->id;
                $file_path = "/tmp/dealer_agreement_" . $agreement->id;
                $out_path = "/tmp/dealer_agreement_" . $agreement->id . ".pdf";
                file_put_contents($file_path, fopen($url, 'r'));
                exec("qpdf --decrypt $file_path $out_path");
                $pdf->setSourceFile($out_path);

                $pagecount = $pdf->setSourceFile($out_path);

                $currentPage = 1;
                while ($currentPage <= $pagecount) {
                    $pdf->addPage();
                    $tplIdx = $pdf->importPage($currentPage);
                    $pdf->useTemplate($tplIdx);
                    $currentPage++;
                }
            }

            $pdf_string = $pdf->Output("S");
            //$pdf->Close();

            if(!empty($license)) {
                unlink($license_img_path);
            }
            if(!empty($certification)) {
                unlink($certification_img_path);
            }
            if(!empty($void)) {
                unlink($void_img_path);
            }
            if(!empty($agreement)) {
                unlink($file_path);
                unlink($out_path);
            }

            $response = Response::make($pdf_string, 200);
            $response->header('Content-Type', 'application/octet-stream');
            $response->header('Content-Disposition', 'attachment; filename="att_' . $account->id . '.pdf"');

            return $response;

        } catch (\Exception $ex) {
            die("<script>alert('" . $ex->getMessage() . "');</script>'");
        }
    }

    public function setStatus(Request $request, $id) {
        try {

            $account = Account::find($id);
            if (empty($account)) {
                return back()->withErrors([
                    'exception' => 'Invalid account ID provided: ' . $id
                ])->withInput();
            }

            $account->doc_status_att = $request->get('doc_status_att');

            $account->save();

            return back()->withInput();

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}