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
use Illuminate\Http\Request;
//use Illuminate\Http\Response;
use App\Model\Account;
use App\Model\AccountFile;
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


class DocumentController extends Controller
{

    public function show(Request $request) {
        try {

            if (Auth::user()->account_type != 'L') {
                return redirect('/admin');
            }

            $accounts = Account::where('type', 'S');
            if (!empty($request->doc_status)) {

                switch ($request->doc_status) {
                    case '1':
                        $accounts = $accounts->whereRaw("
                            (
                                select count(*)
                                from account_files a
                                where a.account_id = accounts.id
                                and (
                                    a.type in (
                                        'FILE_STORE_FRONT',
                                        'FILE_STORE_INSIDE'
                                    ) or 
                                    ( a.type = 'FILE_DEALER_AGREEMENT' and a.signed = 'Y')
                                )
                            ) = 3 and ifnull(doc_status, '') = ''
                        ");
                        break;
                    case '2':
                        $accounts = $accounts->where('doc_status', 2);
                        break;
                    case '3':
                        $accounts = $accounts->where('doc_status', 3);
                        break;
                }

            }

            $search_option = array();
            $search_option_str = '';
            $option_count = 0;
            $dealer_agreement_query = '';

            if ($request->store_front == 'Y') {
                $search_option[] = 'FILE_STORE_FRONT';
            }

            if ($request->store_inside == 'Y') {
                $search_option[] = 'FILE_STORE_INSIDE';
            }

            if ($request->w9 == 'Y') {
                $search_option[] = 'FILE_W_9';
            }

            if ($request->pr_sales_tax == 'Y') {
                $search_option[] = "FILE_PR_SALES_TAX";
            }

            if ($request->usuc == 'Y') {
                $search_option[] = "FILE_USUC";
            }

            if ($request->tax_id == 'Y') {
                $search_option[] = 'FILE_TAX_ID';
            }

            if ($request->biz_cert == 'Y') {
                $search_option[] = 'FILE_BUSINESS_CERTIFICATION';
            }

            if ($request->dealer_agreement == 'Y') {
                $search_option[] = 'FILE_DEALER_AGREEMENT';
            }

            if ($request->driver_license == 'Y') {
                $search_option[] = 'FILE_DRIVER_LICENSE';
            }

            if ($request->void_check == 'Y') {
                $search_option[] = 'FILE_VOID_CHECK';
            }

            if ($request->ach_doc == 'Y') {
                $search_option[] = 'FILE_ACH_DOC';
            }

//            if ($request->h2o_dealer_form == 'Y') {
//                $search_option[] = 'FILE_H2O_DEALER_FORM';
//            }
//
//            if ($request->h2o_ach == 'Y') {
//                $search_option[] = 'FILE_H2O_ACH';
//            }

            if (!empty($search_option)) {
                $option_count = count($search_option);

                ## remove FILE_DEALER_AGREEMENT from the search_option to manage separately
                if(($key = array_search('FILE_DEALER_AGREEMENT', $search_option)) !== false) {
                    unset($search_option[$key]);
                    $dealer_agreement_query = " or ( a.type = 'FILE_DEALER_AGREEMENT' and a.signed = 'Y') ";
                }

                $search_option_str = "('" . implode("', '", $search_option) . "')";
            }

            if ($request->reverse == 'Y' && $search_option_str != '') {
                $accounts = $accounts->whereRaw("
                    (
                    select count(*)
                    from account_files a
                    where a.account_id = accounts.id
                    and ( 
                        a.type in " . $search_option_str . $dealer_agreement_query . "
                        )
                    ) <> " . $option_count );
            }

            if ($request->reverse != 'Y' && $search_option_str != ''){
                $accounts = $accounts->whereRaw("
                    (
                    select count(*)
                    from account_files a
                    where a.account_id = accounts.id
                    and (
                        a.type in " . $search_option_str . $dealer_agreement_query . "
                        )
                    ) = " . $option_count );
            }

            if (!empty($request->acct_id)) {
                $accounts = $accounts->where('id', $request->acct_id);
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

            if ($request->sort == '1') {
                $accounts = $accounts->orderBy('accounts.cdate', 'asc');
            }

            if ($request->sort == '2') {
                $accounts = $accounts->orderBy('accounts.cdate', 'desc');
            }

            if ($request->excel == 'Y') {
                $data = $accounts->orderBy('accounts.path', 'asc')->get();
                Excel::create('Document' . date("mdY_h:i:s_A"), function($excel) use($data) {

                    $excel->sheet('reports', function($sheet) use($data) {

                        $reports = [];

                        foreach ($data as $a) {

                            $reports[] = [
                                'Account' => $a->id,
                                'Name' => $a->name,
                                'created.At' => $a->cdate,
                                'Doc.Status' => $a->doc_status_name(),
                                'Store.Front' => !empty($a->file('FILE_STORE_FRONT')) ? 'view' : '',
                                'Store.Inside' => !empty($a->file('FILE_STORE_INSIDE')) ? 'view' : '',
                                'W9' => !empty($a->file('FILE_W_9')) ? 'view' : '',
                                'PR.SALES.TAX' => !empty($a->file('FILE_PR_SALES_TAX')) ? 'view' : '',
                                'Uniform.Sales.Use.Certificate' => !empty($a->file('FILE_USUC')) ? 'view' : '',
                                'Tax.ID' => !empty($a->file('FILE_TAX_ID')) ? 'view' : '',
                                'Business.Certification' => !empty($a->file('FILE_BUSINESS_CERTIFICATION')) ? 'view' : '',
                                'ACH.DOC' => !empty($a->file('FILE_ACH_DOC')) ? 'view' : '',
                                'H2O.Dealer.Form' => (!empty($a->file('FILE_DEALER_AGREEMENT')) && !empty($a->file('FILE_DEALER_AGREEMENT')->signed == 'Y' )) ? 'view' : '',
                                'Driver.License' => !empty($a->file('FILE_DRIVER_LICENSE')) ? 'view' : '',
                                'Void.Check' => !empty($a->file('FILE_VOID_CHECK')) ? 'view' : ''
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }

            $temp = $accounts->orderBy('accounts.path', 'asc')->distinct()->get();
            $count = count($temp);

            $accounts = $accounts->orderBy('accounts.path', 'asc')->distinct()->paginate(20);
            $states = State::orderBy('name', 'asc')->get();

            return view('admin.reports.document', [
                'accounts' => $accounts,
                'doc_status' => $request->doc_status,
                'sort'      => $request->sort,
                'store_front' => $request->store_front,
                'store_inside' => $request->store_inside,
                'w9' => $request->w9,
                'pr_sales_tax' => $request->pr_sales_tax,
                'usuc' => $request->usuc,
                'tax_id' => $request->tax_id,
                'biz_cert' => $request->biz_cert,
                'dealer_agreement' => $request->dealer_agreement,
                'driver_license' => $request->driver_license,
                'void_check' => $request->void_check,
                'reverse' => $request->reverse,
                'ach_doc' => $request->ach_doc,
//                'h2o_dealer_form' => $request->h2o_dealer_form,
//                'h2o_ach' => $request->h2o_ach,
                'acct_id' => $request->acct_id,
                'acct_name' => $request->acct_name,
                'states'    => $states,
                'state'     => $request->state,
                'city'      => $request->city,
                'count'     => $count
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

            if (!$account->is_verizon_ready()) {
                die("<script>alert('Provided account ID is not Verizon ready');</script>");
            }

            $store_front = AccountFile::where('account_id', $account->id)
                ->where('type', 'FILE_STORE_FRONT')
                ->first();

            if (empty($store_front)) {
                die("<script>alert('Store front image does not exist');</script>");
            }

            $store_inside = AccountFile::where('account_id', $account->id)
                ->where('type', 'FILE_STORE_INSIDE')
                ->first();
            if (empty($store_inside)) {
                die("<script>alert('Store inside image does not exist');</script>");
            }

            $dealer_agreement = AccountFile::where('account_id', $account->id)
                ->where('type', 'FILE_DEALER_AGREEMENT')
                ->where('signed', 'Y')
                ->first();
            if (empty($dealer_agreement)) {
                die("<script>alert('Dealer agreement does not exist');</script>");
            }

            $pdf = new PDF();

            $pdf->SetFont('Arial','',11);

            $pdf->addPage();

            $pdf->cell(0, 5, 'DATE: ' . Carbon::today()->format('m/d/Y'), 0, 1);
            $pdf->cell(0, 5, 'SUB-AGENT NAME : ' . $account->name, 0, 1);
            $pdf->cell(0, 5, 'SUB-AGENT PHONE # : ' . $account->office_number, 0, 1);
            $pdf->cell(0, 5, 'SUB-AGENT EMAIL : ' . $account->email, 0, 1);
            $pdf->cell(0, 5, 'CONTACT : ' . $account->contact, 0, 1);
            $pdf->Ln(5);

            $pdf->cell(0, 5, 'VZW REP : ', 0, 1);
            $pdf->cell(0, 5, 'VZW MARKET : ', 0, 1);
            $pdf->Ln(10);

            $pdf->cell(0, 5, 'EXTERIOR', 0, 1);
            $url = URL::to("/") . "/file/view/" . $store_front->id;
            $store_front_img_path = "/tmp/file_" . $store_front->id;
            file_put_contents($store_front_img_path, fopen($url, "r"));
            Helper::image_disable_interlacing($store_front_img_path);

            $type = Helper::get_image_type($url);
            $pdf->cell(0, 85, '', 1, 1);
            $pdf->centreImage($store_front_img_path, 68, $type);
            //$pdf->Ln(90);

            $pdf->Ln(5);
            $pdf->cell(0, 5, 'INTERIOR', 0, 1);
            $url = URL::to("/") . "/file/view/" . $store_inside->id;
            $store_inside_img_path = "/tmp/file_" . $store_inside->id;
            file_put_contents($store_inside_img_path, fopen($url, "r"));
            Helper::image_disable_interlacing($store_inside_img_path);

            $type = Helper::get_image_type($url);
            $pdf->cell(0, 85, '', 1, 1);
            $pdf->centreImage($store_inside_img_path, 165,  $type);

            $pdf->addPage();
            $pdf->cell(0, 260, '', 1, 1);

            $url = URL::to("/") . "/file/view/" . $dealer_agreement->id;
            $file_path = "/tmp/dealer_agreement_" . $dealer_agreement->id;
            $out_path = "/tmp/dealer_agreement_" . $dealer_agreement->id . ".pdf";
            file_put_contents($file_path, fopen($url, 'r'));
            exec("qpdf --decrypt $file_path $out_path");

            $pdf->setSourceFile($out_path);
            $tplIdx = $pdf->importPage(8);
            $pdf->useTemplate($tplIdx, 0, 0);

            $pdf_string = $pdf->Output("S");
            //$pdf->Close();

            unlink($store_front_img_path);
            unlink($store_inside_img_path);
            unlink($file_path);
            unlink($out_path);

            $response = Response::make($pdf_string, 200);
            $response->header('Content-Type', 'application/octet-stream');
            $response->header('Content-Disposition', 'attachment; filename="verizon_' . $account->id . '.pdf"');

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

            if ($account->doc_status == 3 && $request->get('doc_status') == '') {
                $account->doc_status = 2;
            } else {
                $account->doc_status = $request->get('doc_status');
            }

            $account->save();

            return back()->withInput();

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}