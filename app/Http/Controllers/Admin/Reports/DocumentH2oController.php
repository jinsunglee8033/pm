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


class DocumentH2oController extends Controller
{

    public function show(Request $request) {
        try {

            if (Auth::user()->account_type != 'L') {
                return redirect('/admin');
            }

            $accounts = Account::where('type', 'S');
            if (!empty($request->doc_status_h2o)) {

                switch ($request->doc_status_h2o) {
                    case '1':
                        $accounts = $accounts->whereRaw("
                            (
                                select count(*)
                                from account_files a
                                where a.account_id = accounts.id
                                and (
                                        ( a.type = 'FILE_H2O_DEALER_FORM' and length(a.data) > 0 )
                                        or 
                                        (a.type = 'FILE_H2O_ACH' and length(a.data) > 0 )
                                )
                            ) = 2 and ifnull(doc_status_h2o, '') = ''
                        ");
                        break;
                    case '2':
                        $accounts = $accounts->where('doc_status_h2o', 2);
                        break;
                    case '3':
                        $accounts = $accounts->where('doc_status_h2o', 3);
                        break;
                    case '4':
                        $accounts = $accounts->whereNull('doc_status_h2o');
                        break;
                }

            }

            $search_option = array();
            $search_option_str = '';
            $option_count = 0;
            $dealer_agreement_query = '';

            if ($request->h2o_dealer_form == 'Y') {
                $search_option[] = 'FILE_H2O_DEALER_FORM';
            }

            if ($request->h2o_ach == 'Y') {
                $search_option[] = 'FILE_H2O_ACH';
            }

            if (!empty($search_option)) {
                $option_count = count($search_option);
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
                Excel::create('H2O_Document' . date("mdY_h:i:s_A"), function($excel) use($data) {

                    $excel->sheet('reports', function($sheet) use($data) {

                        $reports = [];

                        foreach ($data as $a) {

                            $reports[] = [
                                'Account' => $a->id,
                                'Name' => $a->name,
                                'created.At' => $a->cdate,
                                'Doc.Status' => $a->h2o_doc_status_name(),
                                'H2O.Dealer.Form' => !empty($a->file('FILE_H2O_DEALER_FORM')) ? 'view' : '',
                                'H2O.ACH' => !empty($a->file('FILE_H2O_ACH')) ? 'view' : '',
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }

            $accounts = $accounts->orderBy('accounts.path', 'asc')->distinct()->paginate(20);
            $states = State::orderBy('name', 'asc')->get();

            return view('admin.reports.document-h2o', [
                'accounts' => $accounts,
                'doc_status_h2o' => $request->doc_status_h2o,
                'sort'      => $request->sort,
                'reverse' => $request->reverse,
                'h2o_dealer_form' => $request->h2o_dealer_form,
                'h2o_ach' => $request->h2o_ach,
                'acct_id' => $request->acct_id,
                'acct_name' => $request->acct_name,
                'states'    => $states,
                'state'     => $request->state,
                'city'      => $request->city
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

            if (!$account->is_h2o_ready()) {
                die("<script>alert('Provided account ID is not Verizon ready');</script>");
            }

            $dealer_form = AccountFile::where('account_id', $account->id)
                ->where('type', 'FILE_H2O_DEALER_FORM')
                ->whereRaw('LENGTH(data) > 0')
                ->first();

            if (empty($dealer_form)) {
                die("<script>alert('H2O Dealer Form does not exist');</script>");
            }

            $ach = AccountFile::where('account_id', $account->id)
                ->where('type', 'FILE_H2O_ACH')
                ->whereRaw('LENGTH(data) > 0')
                ->first();

            if (empty($ach)) {
                die("<script>alert('H2O ACH does not exist');</script>");
            }

            $pdf = new PDF();

            $pdf->SetFont('Arial','',11);

            //// Dealer Form
            $url = URL::to("/") . "/file/view/" . $dealer_form->id;
            $first_file_path = "/tmp/FILE_H2O_DEALER_FORM_" . $dealer_form->id;
            $first_out_path = "/tmp/FILE_H2O_DEALER_FORM_" . $dealer_form->id . ".pdf";
            file_put_contents($first_file_path, fopen($url, 'r'));
            exec("qpdf --decrypt $first_file_path $first_out_path");
            $pdf->setSourceFile($first_out_path);

            $pagecount = $pdf->setSourceFile($first_out_path);

            $currentPage = 1;
            while ($currentPage <= $pagecount) {
                $pdf->addPage();
                $tplIdx = $pdf->importPage($currentPage);
                $pdf->useTemplate($tplIdx);
                $currentPage++;
            }

            //// ACH Form
            $url = URL::to("/") . "/file/view/" . $ach->id;
            $second_file_path = "/tmp/FILE_ACH_DOC_" . $ach->id;
            $second_out_path = "/tmp/FILE_ACH_DOC_" . $ach->id . ".pdf";
            file_put_contents($second_file_path, fopen($url, 'r'));
            exec("qpdf --decrypt $second_file_path $second_out_path");
            $pdf->setSourceFile($second_out_path);

            $pagecount = $pdf->setSourceFile($second_out_path);

            $currentPage = 1;
            while ($currentPage <= $pagecount) {
                $pdf->addPage();
                $tplIdx = $pdf->importPage($currentPage);
                $pdf->useTemplate($tplIdx);
                $currentPage++;
            }


            $pdf_string = $pdf->Output("S");

            unlink($first_file_path);
            unlink($first_out_path);

            unlink($second_file_path);
            unlink($second_out_path);

            $response = Response::make($pdf_string, 200);
            $response->header('Content-Type', 'application/octet-stream');
            $response->header('Content-Disposition', 'attachment; filename="h2o_' . $account->id . '.pdf"');

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

            $account->doc_status_h2o = $request->get('doc_status_h2o');

            $account->save();

            return back()->withInput();

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }
}