<?php

namespace App\Http\Controllers\SubAgent;

use App\Http\Controllers\Controller;
use App\Model\AccountFile;
use Auth;
use http\Cookie;
use Illuminate\Http\Request;
use App\Lib\eSignature;
use Carbon\Carbon;
use App\Model\Account;
use DB;

/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/3/17
 * Time: 3:02 PM
 */
class MainController extends Controller
{

    public function show() {
        $file = AccountFile::where('account_id', Auth::user()->account_id)
            ->where('type', 'FILE_DEALER_AGREEMENT')
            ->first();

        $login_account = Account::find(Auth::user()->account_id);

        \Cookie::queue('repeated_customer', 'yes', 60*24*30);

        return view('sub-agent.main', [
            'show_dealer_agreement_popup' => empty($file) && $login_account->act_verizon == 'Y' ? 'Y' : 'N'
        ]);
    }

    public function download_signed_pdf(Request $request, $envelope_id) {
        try {

            if ($request->event == 'signing_complete') {
                $ret = eSignature::download_doc($envelope_id);
                if (empty($ret['msg'])) {
                    $file = new AccountFile;
                    $file->type = 'FILE_DEALER_AGREEMENT';
                    $file->account_id = Auth::user()->account_id;
                    $file->file_name = 'dealer_agreement.pdf';
                    $file->data = base64_encode($ret['signed_pdf']);
                    $file->created_by = Auth::user()->user_id;
                    $file->cdate = Carbon::now();
                    $file->save();
                }

                echo $ret["msg"];
                exit;
            }

            echo "event: " . $request->event;

        } catch (\Exception $ex) {

            dd($ex);

        }

    }

}