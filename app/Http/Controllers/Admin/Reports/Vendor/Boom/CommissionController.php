<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 7/30/20
 * Time: 2:58 PM
 */

namespace App\Http\Controllers\Admin\Reports\Vendor\Boom;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\CommissionUpload;
use App\Model\CommissionUploadTemp;
use App\Model\Product;
use App\Model\Residual;
use App\Model\ReUPCommission;
use App\Model\ReupRTR;
use App\Model\SpiffSetup;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use App\Model\ROKSim;
use App\Model\ROKESN;
use App\Model\TransactionBoom;
use Carbon\Carbon;
use http\Env\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class CommissionController extends Controller
{

    public function show(Request $request) {
        $sdate = Carbon::now()->copy()->subDays(90);
        $edate = Carbon::now();

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $query = TransactionBoom::query();

        if (!empty($request->phone)) {
            $query = $query->where('mdn', 'like', '%' . $request->phone . '%');
        }

        if (!empty($sdate)) {
            $query = $query->where('cdate', '>=', $sdate);
        }

        if (!empty($edate)) {
            $query = $query->where('cdate', '<=', $edate);
        }

        if (!empty($request->file_name)) {
            $query = $query->where(DB::raw('lower(file_name)'), 'like', '%' . strtolower($request->file_name) . '%');
        }

        if (!empty($request->description)) {
            $query = $query->where(DB::raw('lower(description)'), 'like', '%' . strtolower($request->description) . '%');
        }

        if (!empty($request->sim)) {
            $query = $query->where('iccid', 'like', '%' . $request->sim . '%');
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('id', 'desc')->get();
            Excel::create('commissions', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'File.Name' => $a->file_name,
                            'MDN' => $a->mdn,
                            'Device.ID' => $a->device_id,
                            'ICCID' => $a->iccid,
                            'Plan.Value($)' => '$' . number_format($a->plan_value, 2),
                            'Dealer.On.File.M2.Spiff' => '$' . number_format($a->dealer_month2_payout, 2),
                            'Dealer.On.File.M3.Spiff' => '$' . number_format($a->dealer_month3_payout, 2),
                            'Dealer.On.File.Residual' => '$' . number_format($a->dealer_residual_payout, 2),
                            'Dealer.On.File.Total' => '$' . number_format($a->total_dealer_payout, 2),
                            'Dealer.Paid.M2.Spiff' => '$' . number_format($a->dealer_m2_paid, 2),
                            'Dealer.Paid.M3.Spiff' => '$' . number_format($a->dealer_m3_paid, 2),
                            'Dealer.Paid.Residual' => '$' . number_format($a->dealer_residual_paid, 2),
                            'Dealer.Paid.Total' => '$' . number_format($a->total_dealer_paid, 2),
                            'Master.M2.Spiff' => '$' . number_format($a->master_month2_payout, 2),
                            'Master.M3.Spiff' => '$' . number_format($a->master_month3_payout, 2),
                            'Master.Residual' => '$' . number_format($a->master_residual_payout, 2),
                            'Master.Total' => '$' . number_format($a->total_master_payout, 2),
                            'Description' => $a->description,
                            'Account' => empty($a->account_id) ? '' : $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id) . '<span>' . Helper::get_hierarchy_img($a->account_type)  . '</span>' . $a->account_name . ' (' . $a->account_id . ')' ),
                            'Act.Tx.ID' => $a->act_trans_id,
                            'Upload.Date' => $a->upload_date,
                            'Upload.By' => $a->upload_by
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->orderBy('id', 'asc')->paginate();

        return view('admin.reports.vendor.boom.commission', [
            'data' => $data,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'phone' => $request->phone,
            'file_name' => $request->file_name,
            'description' =>$request->description,
            'sim' => $request->sim
        ]);
    }

    public function upload(Request $request) {

        DB::beginTransaction();

        try {

            $v = Validator::make($request->all(), [
                'file' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

                DB::rollback();
                $this->output($msg);
            }

            $key = 'file';

            if (Input::hasFile($key) && Input::file($key)->isValid()) {
                $path = Input::file($key)->getRealPath();

                Helper::log('### FILE ###', [
                    'key' => $key,
                    'path' => $path
                ]);

                $file_name = Input::file($key)->getClientOriginalName();
                /*if (!ends_with($name, '.csv')) {
                    DB::rollback();
                    $this->output('Please select valid .csv file from ReUP export');
                }*/

                $binder = new SimValueBinder();
                $results = Excel::setValueBinder($binder)->load($path)->setSeparator('_')->get();

                $line_no = 0;

                foreach ($results as $row) {

                    $line_no++;

                    $custnbr = trim($row->custnbr);
                    $mdn = trim($row->mdn);
                    $transaction_date = Carbon::createFromFormat('m/d/Y', $row->transactiondate)->format('Y-m-d');
                    $orignal_dealer = trim($row->orignaldealer);
                    $transaction_dealer = trim($row->transactiondealer);
                    $sku= trim($row->sku);
                    $description = trim($row->description);
                    $iccid = trim($row->iccid);
                    $transaction_cost = trim($row->transactioncost);
                    $added_amount = trim($row->addedamount);
                    $balance = trim($row->balance);
                    $transaction_text = trim($row->transactiontext);

//                    if(empty($custnbr) || empty($mdn) ) continue;

//                    $tb = TransactionBoom::where('file_name', $file_name)
//                        ->where('mdn', $mdn)
//                        ->where('custnbr', $custnbr)
//                        ->where('transaction_date', $transaction_date)
//                        ->where('transaction_text', $transaction_text)
//                        ->where('description', $description)
//                        ->first();
//
//                    if (!empty($tb)) {
//                        throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
//                    }

                    $tb = new TransactionBoom();
                    $tb->file_name = $file_name;
                    $tb->custnbr = $custnbr;
                    $tb->mdn = $mdn;
                    $tb->transaction_date = $transaction_date;
                    $tb->orignal_dealer = $orignal_dealer;
                    $tb->transaction_dealer = $transaction_dealer;
                    $tb->sku = $sku;
                    $tb->description = $description;
                    $tb->iccid = $iccid;
                    $tb->transaction_cost = $transaction_cost;
                    $tb->added_amount = $added_amount;
                    $tb->balance = $balance;
                    $tb->transaction_text = $transaction_text;

                    $tb->cdate = Carbon::now();
                    $tb->created_by = Auth::user()->user_id;

                    $tb->save();

                }

            } else {
                DB::rollback();
                $this->output('Please select valid file');
            }

            DB::commit();

            $this->output('Your request has been processed successfully! Total ' . $line_no . ' records imported', true, false);

        } catch (\Exception $ex) {
            DB::rollback();
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    private function output($msg, $close_modal = false, $is_error = true) {
        echo "<script>";

        if ($close_modal) {
            echo "parent.close_modal('div_upload');";
        }

        $msg = addslashes($msg);
        $msg = str_replace("\r\n", "\t", $msg);
        $msg = str_replace("\n", "\t", $msg);
        $msg = str_replace("\r", "\t", $msg);

        if ($is_error) {
            echo "parent.myApp.hideLoading();";
            echo "parent.myApp.showError('$msg');";
        } else {
            echo "parent.myApp.hideLoading();";
            echo "parent.myApp.showSuccess('$msg');";
        }

        echo "</script>";
        exit;
    }

    public function upload_temp(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            ### Delete commission_upload_temp ###
            $ret = DB::statement("truncate table commission_upload_temp");

            $target_month = $request->target_month;

            $results = DB::select(" 
               select get_boom_month(id, custnbr, mdn, transaction_date) as month, transaction_boom.* 
                 from transaction_boom 
                where CONCAT(YEAR(transaction_date),'-',MONTH(transaction_date)) = :target_month
                and transaction_text in ( 'Update Plan')
                and description not in ( 'Regulatory Recovery Fee' )
                order by  transaction_date ;  
                ", [
                    'target_month' => $target_month
                ]);

            if(empty($results)){
                return back()->withErrors([
                    'exception' => 'No Data on this period'
                ])->withInput();
            }

            $line_no = 0;

            foreach ($results as $row) {

                $line_no++;

                $file_name = $target_month; //trim($row->file_name);

                $phone = trim($row->mdn);
                if(empty($phone)){
                    continue;
                }
                $month = trim($row->month);
                if($month != "2" && $month != "3"){
                    continue;
                }
//                $update_plan = trim($row->transaction_text);
//                if($update_plan != 'Update Plan'){
//                    continue;
//                }
//                $notes = trim($row->description);
//                if($notes == 'Regulatory Recovery Fee'){
//                    continue;
//                }

                $sim = trim($row->iccid);
                $date_added = trim($row->cdate);

                $value = 0;
                $spiff = 0;
                $residual = 0;
                $bonus = 0;

                $sku = trim($row->sku);

                $rec = CommissionUpload::where('file_name', $file_name)
                    ->where('phone', $phone)
                    ->where('month', $month)
                    ->first();

                if (!empty($rec)) {

                    return back()->withErrors([
                        'exception' => 'Duplicated record found with same file name : ' . $file_name . ' and MDN : ' . $phone . ' Possible re-upload. Line : ' . $line_no
                    ])->withInput();

                }


                $rec = new CommissionUploadTemp();
                $rec->file_name = $target_month;
                $rec->phone = $phone;
                $rec->sim = $sim;
                $rec->date_added = $date_added;
                $rec->value = $value;
                $rec->total = 0;
                $rec->spiff = $spiff;
                $rec->residual = $residual;
                $rec->month = $month;
                $rec->bonus = $bonus;
                //$rec->notes = $notes;
                $rec->status = 'N';



                $trans = Transaction::join('accounts', 'transaction.account_id', '=', 'accounts.id')
                    ->join('product', 'transaction.product_id', '=', 'product.id')
                    ->where('transaction.phone', $phone)
                    ->whereRaw(" transaction.product_id in (select product_id from vendor_denom where vendor_code='BOM' and (act_pid = '".$sku."' or rtr_pid = '".$sku."' ) ) ")
                    ->where('transaction.type', 'S')
                    ->whereIn('transaction.action', ['Activation', 'Port-In'])
                    ->where('transaction.status', 'C')
                    ->orderBy('transaction.id', 'desc')
                    ->select(
                        DB::raw('transaction.id as trans_id'),
                        'transaction.product_id',
                        'transaction.account_id',
                        'transaction.denom',
                        'transaction.sim',
                        'transaction.esn',
                        DB::raw('accounts.name as account_name'),
                        DB::raw('product.name as product_name'),
                        'transaction.cdate'
                    )->first();

                if (!empty($trans)) {
                    $rec->product_id = $trans->product_id;
                    $rec->product_name = $trans->product_name;
                    $rec->sim = !empty($trans->sim) ? $trans->sim : null;
                    $rec->esn = !empty($trans->esn) ? $trans->esn : null;
                    $rec->denom = $trans->denom;
                    $rec->account_id = $trans->account_id;
                    $rec->account_name = $trans->account_name;
                    $rec->trans_id = $trans->trans_id;
                    $rec->account_type = 'S';
                    $rec->act_date = $trans->cdate;
                }

                $rec->cdate = Carbon::now();
                $rec->created_by = Auth::user()->user_id;
                $rec->save();
            }

            # Spiff Month setup with SIM
            $ret = DB::statement("
                update commission_upload_temp c
                  join transaction t on c.trans_id = t.id
                  join stock_sim s on t.sim = s.sim_serial
                   set c.spiff_month = s.spiff_month
                 where c.file_name = '$file_name'
            ");

            # Spiff Month setup with ESN
            $ret = DB::statement("
                update commission_upload_temp c
                  join transaction t on c.trans_id = t.id
                  join stock_esn s on t.esn = s.esn
                   set c.spiff_month = s.spiff_month
                 where c.file_name = '$file_name'
                   and ifnull(c.spiff_month, '') = '' 
            ");

            ### Pay target to 'P'
            $ret = DB::statement("
                update commission_upload_temp c
                set c.status = 'P'
                where c.file_name = '$file_name'
                and c.spiff_month like concat('%', c.month, '%')
                and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type in ('S','D','M') )
            ");

            ### Don't know where to pay 'Q'
            $ret = DB::statement("
                update commission_upload_temp c
                   set c.status = 'Q'
                 where c.file_name = '$file_name'
                   and c.status <> 'P'
            ");

//            ## Carrier Selected ##
//            $vw_carrier = 'vw_account_spiff_payable_boom';
//
//            # 2nd Spiff Update
//            $ret = DB::statement("
//                update commission_upload_temp c
//                  join $vw_carrier ss on c.product_id = ss.product_id and c.denom = ss.denom and c.account_id = ss.account_id
//                   set c.paid_spiff = ss.spiff_2nd
//                     , c.d_account_id = ss.d_account_id
//                     , c.m_account_id = ss.m_account_id
//                     , c.paid_d_spiff = ss.pay_d_spiff_2nd
//                     , c.paid_m_spiff = ss.pay_m_spiff_2nd
//                 where c.file_name = '$file_name'
//                   and c.status = 'P'
//                   and c.month = 2
//            ");

            # 2nd Spiff Update (NEW)
            $data_second = CommissionUploadTemp::where('file_name', $file_name)
                ->where('status', 'P')
                ->where('month', 2)
                ->get();

            foreach ($data_second as $d){

                if(!empty($d->account_id)){

                    $s_id = $d->account_id;
                    $s_acct_obj = Account::where('id', $s_id)->first();

                    $sim = !empty($d->sim) ? $d->sim : null;
                    $esn = !empty($d->esn) ? $d->esn : null;

                    $s_ret = SpiffProcessor::get_account_spiff_amt($s_acct_obj, $d->product_id, $d->denom, 2, 1, null, $sim, $esn, null);

                    //paid_spiff
                    $s_spiff = $s_ret['spiff_amt'];
                    $s_paid_spiff = $s_spiff - 0;
                    $d->paid_spiff = $s_paid_spiff;

                    $p_id = $s_acct_obj->parent_id;
                    $m_id = $s_acct_obj->master_id;

                    if($p_id == $m_id){ // M -> S

                        $m_acct_obj = Account::where('id', $m_id)->first();
                        $m_ret = SpiffProcessor::get_account_spiff_amt($m_acct_obj, $d->product_id, $d->denom, 2, 1, null, $sim, $esn, null);

                        // m_account_id
                        $d->m_account_id = $m_id;

                        // paid_m_spiff
                        $m_spiff = $m_ret['spiff_amt'];
                        $m_paid_spiff = $s_spiff - $m_spiff;
                        if($s_spiff < $m_spiff) {
                            $d->paid_m_spiff = $m_paid_spiff;
                        }
                    }else {  // M -> D -> S

                        $d_acct_obj = Account::where('id', $p_id)->first();
                        $d_ret = SpiffProcessor::get_account_spiff_amt($d_acct_obj, $d->product_id, $d->denom, 2, 1, null, $sim, $esn, null);

                        // d_account_id
                        $d->d_account_id = $p_id;

                        // paid_d_spiff
                        $d_spiff = $d_ret['spiff_amt'];
                        $d_paid_spiff = $d_spiff - $s_spiff;
                        if ($s_spiff < $d_spiff) {
                            $d->paid_d_spiff = $d_paid_spiff;
                        }

                        $m_acct_obj = Account::where('id', $m_id)->first();
                        $m_ret = SpiffProcessor::get_account_spiff_amt($m_acct_obj, $d->product_id, $d->denom, 2, 1, null, $sim, $esn, null);

                        // m_account_id
                        $d->m_account_id = $m_id;

                        // paid_m_spiff
                        $m_spiff = $m_ret['spiff_amt'];
                        $m_paid_spiff = $m_spiff - $d_spiff;
                        if ($d_spiff < $m_spiff) {
                            $d->paid_m_spiff = $m_paid_spiff;
                        }
                    }
                    $d->save();
                }
            }

//            # 3rd Spiff Update
//            $ret = DB::statement("
//                update commission_upload_temp c
//                  join $vw_carrier ss on c.product_id = ss.product_id and c.denom = ss.denom and c.account_id = ss.account_id
//                   set c.paid_spiff = ss.spiff_3rd
//                     , c.d_account_id = ss.d_account_id
//                     , c.m_account_id = ss.m_account_id
//                     , c.paid_d_spiff = ss.pay_d_spiff_3rd
//                     , c.paid_m_spiff = ss.pay_m_spiff_3rd
//                 where c.file_name = '$file_name'
//                   and c.status = 'P'
//                   and c.month = 3
//            ");

            # 3rd Spiff Update (NEW)
            $data_second = CommissionUploadTemp::where('file_name', $file_name)
                ->where('status', 'P')
                ->where('month', 3)
                ->get();

            foreach ($data_second as $d){

                if(!empty($d->account_id)){

                    $s_id = $d->account_id;
                    $s_acct_obj = Account::where('id', $s_id)->first();
                    $s_ret = SpiffProcessor::get_account_spiff_amt($s_acct_obj, $d->product_id, $d->denom, 3, 1, null, $sim, $esn, null);

                    //paid_spiff
                    $s_spiff = $s_ret['spiff_amt'];
                    $s_paid_spiff = $s_spiff - 0;
                    $d->paid_spiff = $s_paid_spiff;

                    $p_id = $s_acct_obj->parent_id;
                    $m_id = $s_acct_obj->master_id;

                    if($p_id == $m_id){ // M -> S

                        $m_acct_obj = Account::where('id', $m_id)->first();
                        $m_ret = SpiffProcessor::get_account_spiff_amt($m_acct_obj, $d->product_id, $d->denom, 3, 1, null, $sim, $esn, null);

                        // m_account_id
                        $d->m_account_id = $m_id;

                        // paid_m_spiff
                        $m_spiff = $m_ret['spiff_amt'];
                        $m_paid_spiff = $s_spiff - $m_spiff;
                        if($s_spiff < $m_spiff) {
                            $d->paid_m_spiff = $m_paid_spiff;
                        }
                    }else{  // M -> D -> S

                        $d_acct_obj = Account::where('id', $p_id)->first();
                        $d_ret = SpiffProcessor::get_account_spiff_amt($d_acct_obj, $d->product_id, $d->denom, 3, 1, null, $sim, $esn, null);

                        // d_account_id
                        $d->d_account_id = $p_id;

                        // paid_d_spiff
                        $d_spiff = $d_ret['spiff_amt'];
                        $d_paid_spiff = $d_spiff - $s_spiff;
                        if($s_spiff < $d_spiff) {
                            $d->paid_d_spiff = $d_paid_spiff;
                        }

                        $m_acct_obj = Account::where('id', $m_id)->first();
                        $m_ret = SpiffProcessor::get_account_spiff_amt($m_acct_obj, $d->product_id, $d->denom, 3, 1, null, $sim, $esn, null);

                        // m_account_id
                        $d->m_account_id = $m_id;

                        // paid_m_spiff
                        $m_spiff = $m_ret['spiff_amt'];
                        $m_paid_spiff = $m_spiff - $d_spiff;
                        if($d_spiff < $m_spiff) {
                            $d->paid_m_spiff = $m_paid_spiff;
                        }
                    }
                    $d->save();
                }
            }


            return redirect('/admin/reports/vendor/commission_temp')->with('status', 'Upload Preview Completed!');

        } catch (\Exception $ex) {
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    private function output_error($msg) {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.myApp.showError(\"" . str_replace("\"", "'", $msg) . "\");";
        echo "</script>";
        exit;
    }

}