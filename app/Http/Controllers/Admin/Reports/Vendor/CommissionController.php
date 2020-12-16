<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/8/18
 * Time: 2:58 PM
 */

namespace App\Http\Controllers\Admin\Reports\Vendor;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Lib\SpiffProcessor;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\CommissionUploadTemp;
use App\Model\Product;
use App\Model\Residual;
use App\Model\SpiffSetup;
use App\Model\Transaction;
use App\Model\CommissionUpload;
use Carbon\Carbon;
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

        $cancel = Input::get('cancel');

        if($cancel == 'Y'){
            ### Delete commission_upload_temp ###
            DB::statement("truncate table commission_upload_temp");
        }

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        $sdate = Carbon::now()->copy()->subDays(90);
        $edate = Carbon::now();

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $query = CommissionUpload::query();

        if (!empty($request->phone)) {
            $query = $query->where('phone', 'like', '%' . $request->phone . '%');
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

        if (!empty($request->account)) {
            $query = $query->whereRaw('account_id in (select id from accounts where lower(id) like \'%' . strtolower($request->account) . '%\' or lower(name) like \'%' . strtolower($request->account) . '%\')' );
        }

        if (!empty($request->month)) {
            $query = $query->where('month', $request->month);
        }

        if (!empty($request->status)) {
            $query = $query->where('status', $request->status);
        }

        if (!empty($request->sim)) {
            $query = $query->where('sim', 'like', '%' . $request->sim . '%');
        }

        if (!empty($request->carrier)) {
            $products = Product::where('carrier', $request->carrier)->where('activation', 'Y')->select('id')->get();
            $product = [];
            foreach ($products as $p){
                array_push($product, $p->id);
            }
            $query = $query->whereIn('product_id', $product);
        }

        if (!empty($request->denom)) {
            $query = $query->where('denom', $request->denom);
        }

        if (!empty($request->product)) {
            $query = $query->whereRaw("lower(product_id) like ?", '%'. strtolower($request->product) . '%');
        }

        if (!empty($request->product_name)) {
            $query = $query->whereRaw("lower(product_name) like ?", '%'. strtolower($request->product_name) . '%');
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
                            'Phone' => $a->phone,
                            'SIM' => $a->sim,
                            'Month' => $a->month,
                            'Spiff' => '$' . number_format($a->spiff, 2),
                            'Spiff.Paid' => '$' . number_format($a->paid_spiff, 2),
                            'Residual' => '$' . number_format($a->residual, 2),
                            'Residual.Paid' => '$' . number_format($a->paid_residual, 2),
                            'Total' => '$' . number_format($a->total, 2),
                            'Status' => $a->status,
                            'Description' => $a->notes,
                            'Product' => $a->product_name,
                            'Face.Value' => $a->denom,
                            'Account' => empty($a->account_id) ? '' : $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id) . '<span>' . Helper::get_hierarchy_img($a->account_type)  . '</span>' . $a->account_name . ' (' . $a->account_id . ')' ),
                            'Act.Tx.ID' => $a->trans_id,
                            'Act.Date' => $a->act_date,
                            'Upload.Date' => $a->cdate,
                            'Upload.By' => $a->created_by
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->orderBy('id', 'desc')->paginate();

        $products = Product::where('activation', 'Y')->where('status', 'A')->get();
        $carriers = Carrier::where('has_activation', 'Y')->get();

        return view('admin.reports.vendor.commission', [
            'data' => $data,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'phone' => $request->phone,
            'file_name' => $request->file_name,
            'sim' => $request->sim,
            'account' => $request->account,
            'products' => $products,
            'product' => $request->product,
            'product_name' => $request->product_name,
            'month' => $request->month,
            'status' => $request->status,
            'carriers' => $carriers,
            'carrier' => $request->carrier,
            'denom' => $request->denom
        ]);
    }

    public function upload(Request $request) {
        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

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

                $products = Product::where('carrier', $request->carrier)->where('activation', 'Y')->select('id')->get();
                $product = [];
                foreach ($products as $p){
                    array_push($product, $p->id);
                }

                $line_no = 0;
                foreach ($results as $row) {

                    $line_no++;

                    $phone = trim($row->phone);
                    $sim = trim($row->sim);
                    $date_added = trim($row->time_stamp);
                    $value = trim($row->value);
                    if($value == ''){
                        $value = 0.00;
                    }
                    $total = trim($row->total);
                    $spiff = trim($row->spiff);
                    $residual = trim($row->residual);
                    $month = trim($row->month);
                    $bonus = trim($row->bonus);
                    if($bonus == ''){
                        $bonus = 0.00;
                    }
                    $notes = trim($row->notes);

                    if (in_array($month, [2,3])) {
                        $rec = CommissionUpload::where('file_name', $file_name)
                          ->where('phone', $phone)
                          ->where('month', $month)
                          ->first();

                        if (!empty($rec)) {
                            throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
                        }
                    }

                    $rec = new CommissionUpload;
                    $rec->file_name = $file_name;
                    $rec->phone = $phone;
                    $rec->sim = $sim;
                    $rec->date_added = $date_added;
                    $rec->value = $value;
                    $rec->total = $total;
                    $rec->spiff = $spiff;
                    $rec->residual = $residual;
                    $rec->month = $month;
                    $rec->bonus = $bonus;
                    $rec->notes = $notes;
                    $rec->status = 'N';

                    $trans = Transaction::join('accounts', 'transaction.account_id', '=', 'accounts.id')
                        ->join('product', 'transaction.product_id', '=', 'product.id')
                        ->where('transaction.phone', $phone)
                        ->whereIn('transaction.product_id', $product)
                        ->where('transaction.type', 'S')
                        ->whereIn('transaction.action', ['Activation', 'Port-In'])
                        ->where('transaction.status', 'C')
                        ->orderBy('transaction.id', 'desc')
                        ->select(
                            DB::raw('transaction.id as trans_id'),
                            'transaction.product_id',
                            'transaction.account_id',
                            'transaction.denom',
                            DB::raw('accounts.name as account_name'),
                            DB::raw('product.name as product_name'),
                            'transaction.cdate'
                        )->first();

                    if (!empty($trans)) {
                        $rec->product_id = $trans->product_id;
                        $rec->product_name = $trans->product_name;
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
                    update commission_upload c
                      join transaction t on c.trans_id = t.id
                      join stock_sim s on t.sim = s.sim_serial
                       set c.spiff_month = s.spiff_month
                     where c.file_name = '$file_name'
                ");

                # Spiff Month setup with ESN
                $ret = DB::statement("
                    update commission_upload c
                      join transaction t on c.trans_id = t.id
                      join stock_esn s on t.esn = s.esn
                       set c.spiff_month = s.spiff_month
                     where c.file_name = '$file_name'
                       and ifnull(c.spiff_month, '') = '' 
                ");

                ### Pay target to 'P'
                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'P'
                     where c.file_name = '$file_name'
                       and c.spiff_month like concat('%', c.month, '%')
                ");

                ### Don't know where to pay 'Q'
                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'Q'
                     where c.file_name = '$file_name'
                       and c.status <> 'P'
                ");

                # 2nd Spiff Update
                $ret = DB::statement("
                    update commission_upload c
                      join vw_account_spiff_payable ss on c.product_id = ss.product_id and c.denom = ss.denom and c.account_id = ss.account_id
                       set c.paid_spiff = ss.spiff_2nd
                         , c.d_account_id = ss.d_account_id
                         , c.m_account_id = ss.m_account_id
                         , c.paid_d_spiff = ss.pay_d_spiff_2nd
                         , c.paid_m_spiff = ss.pay_m_spiff_2nd
                     where c.file_name = '$file_name'
                       and c.status = 'P'
                       and c.month = 2
                ");

                # 3rd Spiff Update
                $ret = DB::statement("
                    update commission_upload c
                      join vw_account_spiff_payable ss on c.product_id = ss.product_id and c.denom = ss.denom and c.account_id = ss.account_id
                       set c.paid_spiff = ss.spiff_3rd
                         , c.d_account_id = ss.d_account_id
                         , c.m_account_id = ss.m_account_id
                         , c.paid_d_spiff = ss.pay_d_spiff_3rd
                         , c.paid_m_spiff = ss.pay_m_spiff_3rd
                     where c.file_name = '$file_name'
                       and c.status = 'P'
                       and c.month = 3
                ");

                ### Pay spiff, Sub-Agent
                $ret = DB::statement("
                    insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, orig_spiff_amt, created_by, cdate)
                    select 'S', c.trans_id, c.phone, c.account_id, c.product_id, c.denom, 'S', c.month, c.paid_spiff, 0, c.created_by, c.cdate
                      from commission_upload c
                     where c.status = 'P'
                       and c.paid_spiff <> 0
                       and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type = 'S')");

                ### Pay spiff, Distributor
                $ret = DB::statement("
                    insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, orig_spiff_amt, created_by, cdate)
                    select 'S', c.trans_id, c.phone, c.d_account_id, c.product_id, c.denom, 'D', c.month, c.paid_d_spiff, 0, c.created_by, c.cdate
                      from commission_upload c
                     where c.status = 'P'
                       and c.paid_d_spiff <> 0
                       and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type = 'D')");

                ### Pay spiff, Master
                $ret = DB::statement("
                    insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, orig_spiff_amt, created_by, cdate)
                    select 'S', c.trans_id, c.phone, c.m_account_id, c.product_id, c.denom, 'M', c.month, c.paid_m_spiff, 0, c.created_by, c.cdate
                      from commission_upload c
                     where c.status = 'P'
                       and c.paid_m_spiff <> 0
                       and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type = 'M')");

                ### Pay residual
                // $ret = DB::statement("
                //     insert into residual (type, account_id, trans_id, mdn, act_date, amt, amt_org, comments, created_by, cdate) 
                //     select 'S', c.account_id, c.trans_id, c.phone, c.act_date, c.residual, 0, concat(c.product_name, ' Residual'), c.created_by, c.cdate
                //       from commission_upload c
                //      where c.status = 'P'
                //        and c.residual <> 0
                // ");

                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'S'
                     where c.status = 'P'
                ");

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

    public function upload_temp(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            $v = Validator::make($request->all(), [
                'file' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

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

                $binder = new SimValueBinder();
                $results = Excel::setValueBinder($binder)->load($path)->setSeparator('_')->get();

                $products = Product::where('carrier', $request->carrier)->where('activation', 'Y')->select('id')->get();
                $product = [];
                foreach ($products as $p){
                    array_push($product, $p->id);
                }

                ### Delete commission_upload_temp ###
                $ret = DB::statement("truncate table commission_upload_temp");

                $line_no = 0;
                foreach ($results as $row) {

                    $line_no++;

                    $phone = trim($row->phone);
                    if(strlen($phone) != 10){
                        continue;
                    }
                    $sim = trim($row->sim);
                    $date_added = trim($row->time_stamp);
                    $value = trim($row->value);
                    if($value == ''){
                        $value = 0.00;
                    }
                    $total = trim($row->total);
                    $spiff = trim($row->spiff);
                    $residual = trim($row->residual);
                    $month = trim($row->month);
                    $bonus = trim($row->bonus);
                    if($bonus == ''){
                        $bonus = 0.00;
                    }
                    $notes = trim($row->notes);

                    if (in_array($month, [2,3])) {
                        $rec = CommissionUpload::where('file_name', $file_name)
                            ->where('phone', $phone)
                            ->where('month', $month)
                            ->first();

                        if (!empty($rec)) {

                            return back()->withErrors([
                                'exception' => 'Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no
                            ])->withInput();

//                            throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
                        }
                    }

                    $rec = new CommissionUploadTemp();
                    $rec->file_name = $file_name;
                    $rec->phone = $phone;
                    $rec->sim = $sim;
                    $rec->date_added = $date_added;
                    $rec->value = $value;
                    $rec->total = $total;
                    $rec->spiff = $spiff;
                    $rec->residual = $residual;
                    $rec->month = $month;
                    $rec->bonus = $bonus;
                    $rec->notes = $notes;
                    $rec->status = 'N';

                    $trans = Transaction::join('accounts', 'transaction.account_id', '=', 'accounts.id')
                        ->join('product', 'transaction.product_id', '=', 'product.id')
                        ->where('transaction.phone', $phone)
                        ->whereIn('transaction.product_id', $product)
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

                ## Carrier Selected ##
//                if($request->carrier == 'AT&T'){
//                    $vw_carrier = 'vw_account_spiff_payable_att';
//                }elseif ($request->carrier == 'Boom Mobile'){
//                    $vw_carrier = 'vw_account_spiff_payable_boom';
//                }elseif ($request->carrier == 'FreeUP'){
//                    $vw_carrier = 'vw_account_spiff_payable_freeup';
//                }elseif ($request->carrier == 'GEN Mobile'){
//                    $vw_carrier = 'vw_account_spiff_payable_gen';
//                }elseif ($request->carrier == 'H2O'){
//                    $vw_carrier = 'vw_account_spiff_payable_h2o';
//                }elseif ($request->carrier == 'Liberty Mobile'){
//                    $vw_carrier = 'vw_account_spiff_payable_liberty';
//                }elseif ($request->carrier == 'Lyca'){
//                    $vw_carrier = 'vw_account_spiff_payable_lyca';
//                }

//                # 2nd Spiff Update
//                $ret = DB::statement("
//                    update commission_upload_temp c
//                      join $vw_carrier ss on c.product_id = ss.product_id and c.denom = ss.denom and c.account_id = ss.account_id
//                       set c.paid_spiff = ss.spiff_2nd
//                         , c.d_account_id = ss.d_account_id
//                         , c.m_account_id = ss.m_account_id
//                         , c.paid_d_spiff = ss.pay_d_spiff_2nd
//                         , c.paid_m_spiff = ss.pay_m_spiff_2nd
//                     where c.file_name = '$file_name'
//                       and c.status = 'P'
//                       and c.month = 2
//                ");

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
                        }else{  // M -> D -> S

                            $d_acct_obj = Account::where('id', $p_id)->first();
                            $d_ret = SpiffProcessor::get_account_spiff_amt($d_acct_obj, $d->product_id, $d->denom, 2, 1, null, $sim, $esn, null);

                            // d_account_id
                            $d->d_account_id = $p_id;

                            // paid_d_spiff
                            $d_spiff = $d_ret['spiff_amt'];
                            $d_paid_spiff = $d_spiff - $s_spiff;
                            if($s_spiff < $d_spiff) {
                                $d->paid_d_spiff = $d_paid_spiff;
                            }

                            $m_acct_obj = Account::where('id', $m_id)->first();
                            $m_ret = SpiffProcessor::get_account_spiff_amt($m_acct_obj, $d->product_id, $d->denom, 2, 1, null, $sim, $esn, null);

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


//                # 3rd Spiff Update
//                $ret = DB::statement("
//                    update commission_upload_temp c
//                      join $vw_carrier ss on c.product_id = ss.product_id and c.denom = ss.denom and c.account_id = ss.account_id
//                       set c.paid_spiff = ss.spiff_3rd
//                         , c.d_account_id = ss.d_account_id
//                         , c.m_account_id = ss.m_account_id
//                         , c.paid_d_spiff = ss.pay_d_spiff_3rd
//                         , c.paid_m_spiff = ss.pay_m_spiff_3rd
//                     where c.file_name = '$file_name'
//                       and c.status = 'P'
//                       and c.month = 3
//                ");

                # 3rd Spiff Update (NEW)
                $data_second = CommissionUploadTemp::where('file_name', $file_name)
                    ->where('status', 'P')
                    ->where('month', 3)
                    ->get();

                foreach ($data_second as $d){

                    if(!empty($d->account_id)){

                        $s_id = $d->account_id;
                        $s_acct_obj = Account::where('id', $s_id)->first();

                        $sim = !empty($d->sim) ? $d->sim : null;
                        $esn = !empty($d->esn) ? $d->esn : null;

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

            } else {

                $this->output('Please select valid file');
            }

            return redirect('/admin/reports/vendor/commission_temp')->with('status', 'Upload Preview Completed!');

        } catch (\Exception $ex) {
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function show_temp(Request $request) {

        $excel = Input::get('excel');

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        $query = CommissionUploadTemp::query();

        if ($excel == 'Y') {
            $data = $query->orderBy('id', 'desc')->get();
            Excel::create('commissions', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'File.Name' => $a->file_name,
                            'Product' => $a->product_id,
                            'Product.Name' => $a->product_name,
                            'Phone' => $a->phone,
                            'SIM' => $a->sim,
                            'Month' => $a->month,
                            'Spiff.Month' => $a->spiff_month,
                            'Status' => $a->status == 'S' ? 'Paid' : 'Unpaid',
                            'Denom' => empty($a->denom) ? '' : '$' . number_format($a->denom, 2),
                            'Spiff' => '$' . number_format($a->spiff, 2),
                            'Spiff.Will' => '$' . number_format($a->paid_spiff, 2),
                            'Residual' => '$' . number_format($a->residual, 2),
                            'Residual.Will' => '$' . number_format($a->paid_residual, 2),
                            'Bonus' => '$' . number_format($a->bouns, 2),
                            'Bonus.Will'=> '$' . number_format($a->paid_bonus, 2),
                            'Value' => '$' . number_format($a->value, 2),
                            'Total' => '$' . number_format($a->total, 2),
                            'Description' => $a->notes,
                            'Account' => empty($a->account_id) ? '' : $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id) . '<span>' . Helper::get_hierarchy_img($a->account_type)  . '</span>' . $a->account_name . ' (' . $a->account_id . ')' ),
                            'Act.Tx.ID' => $a->trans_id,
                            'Act.Date' => $a->act_date,
                            'Upload.Date' => $a->cdate,
                            'Upload.By' => $a->created_by
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->orderBy('id', 'desc')->paginate();
        $row = $query->orderBy('id', 'desc')->first();
        $file_name = !empty($row->file_name) ? $row->file_name : '';
        return view('admin.reports.vendor.commission-preview', [
            'data' => $data,
            'file_name' => $file_name
        ]);
    }

    public function upload_final(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            ### Pay spiff, Sub-Agent
            $ret = DB::statement("
                insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, orig_spiff_amt, created_by, cdate)
                select 'S', c.trans_id, c.phone, c.account_id, c.product_id, c.denom, 'S', c.month, c.paid_spiff, 0, c.created_by, c.cdate
                  from commission_upload_temp c
                 where c.status = 'P'
                   and c.paid_spiff <> 0
                   and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type = 'S')");

            ### Pay spiff, Distributor
            $ret = DB::statement("
                insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, orig_spiff_amt, created_by, cdate)
                select 'S', c.trans_id, c.phone, c.d_account_id, c.product_id, c.denom, 'D', c.month, c.paid_d_spiff, 0, c.created_by, c.cdate
                  from commission_upload_temp c
                 where c.status = 'P'
                   and c.paid_d_spiff <> 0
                   and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type = 'D')");

            ### Pay spiff, Master
            $ret = DB::statement("
                insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, orig_spiff_amt, created_by, cdate)
                select 'S', c.trans_id, c.phone, c.m_account_id, c.product_id, c.denom, 'M', c.month, c.paid_m_spiff, 0, c.created_by, c.cdate
                  from commission_upload_temp c
                 where c.status = 'P'
                   and c.paid_m_spiff <> 0
                   and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type = 'M')");

            ### Copy commission_upload_temp to commission_upload ###
            $ret = DB::statement("
                insert into commission_upload (file_name,phone,sim,date_added,value,total,spiff,residual,bonus,bonus_d,bonus_m,month,notes,product_id,product_name,denom,account_id,account_name,account_type,d_account_id,m_account_id,trans_id,act_date,spiff_month,status,paid_spiff,paid_d_spiff,paid_m_spiff,paid_residual,paid_bonus,paid_bonus_d,paid_bonus_m,created_by,cdate)
                select file_name,phone,sim,date_added,value,total,spiff,residual,bonus,bonus_d,bonus_m,month,notes,product_id,product_name,denom,account_id,account_name,account_type,d_account_id,m_account_id,trans_id,act_date,spiff_month,status,paid_spiff,paid_d_spiff,paid_m_spiff,paid_residual,paid_bonus,paid_bonus_d,paid_bonus_m,created_by,cdate
                from commission_upload_temp");

            ### Delete commission_upload_temp ###
            $ret = DB::statement("truncate table commission_upload_temp");

            ### Update commission_upload status ###
            $ret = DB::statement("
                update commission_upload c
                   set c.status = 'S'
                 where c.status = 'P'
            ");

            return redirect('/admin/reports/vendor/commission')->with('status', 'Spiff Pay Completed!');

        } catch (\Exception $ex) {
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function upload_bonus(Request $request) {
        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

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

                $products = Product::where('carrier', $request->carrier)->where('activation', 'Y')->select('id')->get();
                $product = [];
                foreach ($products as $p){
                    array_push($product, $p->id);
                }

                $line_no = 0;
                foreach ($results as $row) {

                    $line_no++;

                    $phone = trim($row->phone);
                    $sim = trim($row->sim);
                    $date_added = trim($row->time_stamp);
                    $value = trim($row->value);
                    $month = trim($row->month);
                    $bonus = trim($row->bonus);
                    $notes = trim($row->notes);

                    if (in_array($month, [2,3])) {
                        $rec = CommissionUpload::where('file_name', $file_name)
                            ->where('phone', $phone)
                            ->where('month', $month)
                            ->first();

                        if (!empty($rec)) {
                            throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
                        }
                    }

                    $rec = new CommissionUpload;
                    $rec->file_name = $file_name;
                    $rec->phone = $phone;
                    $rec->sim = $sim;
                    $rec->date_added = $date_added;
                    $rec->value = $value;
                    $rec->month = $month;
                    $rec->bonus = $bonus;
                    $rec->notes = $notes;
                    $rec->status = 'N';

                    $trans = Transaction::join('accounts', 'transaction.account_id', '=', 'accounts.id')
                        ->join('product', 'transaction.product_id', '=', 'product.id')
                        ->where('transaction.phone', $phone)
                        ->whereIn('transaction.product_id', $product)
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

                ### Pay target to 'P'
                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'P'
                     where c.file_name = '$file_name'
                       and c.bonus != '0'
                ");

                ### Don't know where to pay 'Q'
                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'Q'
                     where c.file_name = '$file_name'
                       and c.status <> 'P'
                ");

                # Paid Bonus Update
                $ret = DB::statement("
                    update commission_upload c
                       set c.paid_bonus = c.bonus
                     where c.file_name = '$file_name'
                       and c.status = 'P'
                ");

                ### Pay Bonus to spiff_trans
                $ret = DB::statement("
                    insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, commission_upload_id,orig_spiff_amt, created_by, cdate, note)
                    select 'S', c.trans_id, c.phone, c.account_id, c.product_id, c.denom, 'S', c.month, c.paid_bonus, c.id, 0, c.created_by, c.cdate, 'Extra Bonus Spiff'
                      from commission_upload c
                     where c.status = 'P'");

                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'S'
                     where c.status = 'P'
                ");

            } else {
                $this->output('Please select valid file');
            }

            $this->output('Your request has been processed successfully! Total ' . $line_no . ' records imported', true, false);

        } catch (\Exception $ex) {

            if($ex->getCode() == 23000){
                $this->output('Account ID is not exist!' . ' [' . $ex->getCode() . ']');
            }
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function upload_bonus_temp(Request $request) {
        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            $v = Validator::make($request->all(), [
                'file_b' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

                $this->output($msg);
            }

            $key = 'file_b';

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

                $products = Product::where('carrier', $request->carrier_b)->where('activation', 'Y')->select('id')->get();
                $product = [];
                foreach ($products as $p){
                    array_push($product, $p->id);
                }

                ### Delete commission_upload_temp ###
                DB::statement("truncate table commission_upload_temp");

                $line_no = 0;
                foreach ($results as $row) {

                    $line_no++;

                    $phone = trim($row->phone);
                    if(strlen($phone) != 10){
                        continue;
                    }

                    $sim = trim($row->sim);
                    $date_added = trim($row->time_stamp);
                    $value = trim($row->value);
                    $month = trim($row->month);
                    $bonus = trim($row->bonus);
                    $notes = trim($row->notes);

                    if (in_array($month, [2,3])) {
                        $rec = CommissionUpload::where('file_name', $file_name)
                            ->where('phone', $phone)
                            ->where('month', $month)
                            ->first();

                        if (!empty($rec)) {
                            return back()->withErrors([
                                'exception' => 'Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no
                            ])->withInput();
//                            throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
                        }
                    }

                    $rec = new CommissionUploadTemp;
                    $rec->file_name = $file_name;
                    $rec->phone = $phone;
                    $rec->sim = $sim;
                    $rec->date_added = $date_added;
                    $rec->value = $value;
                    $rec->month = $month;
                    $rec->bonus = $bonus;
                    $rec->notes = $notes;
                    $rec->status = 'N';

                    $trans = Transaction::join('accounts', 'transaction.account_id', '=', 'accounts.id')
                        ->join('product', 'transaction.product_id', '=', 'product.id')
                        ->where('transaction.phone', $phone)
                        ->whereIn('transaction.product_id', $product)
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

                ### Pay target to 'P'
                $ret = DB::statement("
                    update commission_upload_temp c
                       set c.status = 'P'
                     where c.file_name = '$file_name'
                       and c.bonus != '0'
                       and c.month not in (select spiff_month from spiff_trans where trans_id = c.trans_id and phone = c.phone and account_type in ('S','D','M') )
                ");

                ### Don't know where to pay 'Q'
                $ret = DB::statement("
                    update commission_upload_temp c
                       set c.status = 'Q'
                     where c.file_name = '$file_name'
                       and c.status <> 'P'
                ");

                # Paid Bonus Update
                $ret = DB::statement("
                    update commission_upload_temp c
                       set c.paid_bonus = c.bonus
                     where c.file_name = '$file_name'
                       and c.status = 'P'
                ");

//                ### Pay Bonus to spiff_trans
//                $ret = DB::statement("
//                    insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, commission_upload_id,orig_spiff_amt, created_by, cdate, note)
//                    select 'S', c.trans_id, c.phone, c.account_id, c.product_id, c.denom, 'S', c.month, c.paid_bonus, c.id, 0, c.created_by, c.cdate, 'Extra Bonus Spiff'
//                      from commission_upload c
//                     where c.status = 'P'");
//
//                $ret = DB::statement("
//                    update commission_upload c
//                       set c.status = 'S'
//                     where c.status = 'P'
//                ");

            } else {
                $this->output('Please select valid file');
            }

            return redirect('/admin/reports/vendor/commission_bonus_temp')->with('status', 'Upload Preview Completed!');

//            $this->output('Your request has been processed successfully! Total ' . $line_no . ' records imported', true, false);

        } catch (\Exception $ex) {
            if($ex->getCode() == 23000){
                $this->output('Account ID is not exist!' . ' [' . $ex->getCode() . ']');
            }
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function show_bonus_temp(Request $request) {

        $excel = Input::get('excel');

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        $query = CommissionUploadTemp::query();

        if ($excel == 'Y') {
            $data = $query->orderBy('id', 'desc')->get();
            Excel::create('commissions', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'File.Name' => $a->file_name,
                            'Product' => $a->product_id,
                            'Product.Name' => $a->product_name,
                            'Phone' => $a->phone,
                            'SIM' => $a->sim,
                            'Month' => $a->month,
                            'Spiff.Month' => $a->spiff_month,
                            'Status' => $a->status == 'S' ? 'Paid' : 'Unpaid',
                            'Denom' => empty($a->denom) ? '' : '$' . number_format($a->denom, 2),
                            'Spiff' => '$' . number_format($a->spiff, 2),
                            'Spiff.Will' => '$' . number_format($a->paid_spiff, 2),
                            'Residual' => '$' . number_format($a->residual, 2),
                            'Residual.Will' => '$' . number_format($a->paid_residual, 2),
                            'Bonus' => '$' . number_format($a->bouns, 2),
                            'Bonus.Will'=> '$' . number_format($a->paid_bonus, 2),
                            'Value' => '$' . number_format($a->value, 2),
                            'Total' => '$' . number_format($a->total, 2),
                            'Description' => $a->notes,
                            'Account' => empty($a->account_id) ? '' : $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id) . '<span>' . Helper::get_hierarchy_img($a->account_type)  . '</span>' . $a->account_name . ' (' . $a->account_id . ')' ),
                            'Act.Tx.ID' => $a->trans_id,
                            'Act.Date' => $a->act_date,
                            'Upload.Date' => $a->cdate,
                            'Upload.By' => $a->created_by
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->orderBy('id', 'desc')->paginate();
        $row = $query->orderBy('id', 'desc')->first();
        $file_name = !empty($row->file_name) ? $row->file_name : '';
        return view('admin.reports.vendor.commission-bonus-preview', [
            'data' => $data,
            'file_name' => $file_name
        ]);
    }

    public function upload_bonus_final(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            ### Copy commission_upload_temp to commission_upload ###
            $ret = DB::statement("
                insert into commission_upload (file_name,phone,sim,date_added,value,total,spiff,residual,bonus,bonus_d,bonus_m,month,notes,product_id,product_name,denom,account_id,account_name,account_type,d_account_id,m_account_id,trans_id,act_date,spiff_month,status,paid_spiff,paid_d_spiff,paid_m_spiff,paid_residual,paid_bonus,paid_bonus_d,paid_bonus_m,created_by,cdate)
                select file_name,phone,sim,date_added,value,total,spiff,residual,bonus,bonus_d,bonus_m,month,notes,product_id,product_name,denom,account_id,account_name,account_type,d_account_id,m_account_id,trans_id,act_date,spiff_month,status,paid_spiff,paid_d_spiff,paid_m_spiff,paid_residual,paid_bonus,paid_bonus_d,paid_bonus_m,created_by,cdate
                from commission_upload_temp");

            ### Pay Bonus to spiff_trans
            $ret = DB::statement("
                insert into spiff_trans (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, commission_upload_id,orig_spiff_amt, created_by, cdate, note)
                select 'S', c.trans_id, c.phone, c.account_id, c.product_id, c.denom, 'S', c.month, c.paid_bonus, c.id, 0, c.created_by, c.cdate, 'Extra Bonus Spiff'
                  from commission_upload c
                 where c.status = 'P'");

            ### Delete commission_upload_temp ###
            $ret = DB::statement("truncate table commission_upload_temp");

            ### Update commission_upload status ###
            $ret = DB::statement("
                update commission_upload c
                   set c.status = 'S'
                 where c.status = 'P'
            ");

            return redirect('/admin/reports/vendor/commission')->with('status', 'Bonus Pay Completed!');

        } catch (\Exception $ex) {
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function upload_bonus_by_acct(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            $v = Validator::make($request->all(), [
                'file' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

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

                ### Delete commission_upload_temp ###
                $ret = DB::statement("truncate table commission_upload_temp");

                $line_no = 0;
                foreach ($results as $row) {
                    $line_no++;
                    $account = trim($row->account);
                    $bonus = trim($row->bonus);
                    $notes = trim($row->notes);
                    $product = $request->product;

                    $rec = CommissionUpload::where('file_name', $file_name)
                        ->where('account_id', $account)
                        ->first();

                    if (!empty($rec)) {
                        throw new \Exception('Duplicated record found with same file name and account. Possible re-upload. Line : ' . $line_no);
                    }

                    $rec = new CommissionUpload;
                    $rec->file_name = $file_name;
                    $rec->phone = '';
                    $rec->sim = '';
                    $rec->account_id = $account;
                    $rec->value = $bonus;
                    $rec->month = '0';
                    $rec->bonus = $bonus;
                    $rec->notes = $notes;
                    $rec->product_id = $product;
                    $rec->status = 'N';

                    $rec->cdate = Carbon::now();
                    $rec->created_by = Auth::user()->user_id;
                    $rec->save();
                }

                ### Pay target to 'P'
                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'P'
                     where c.file_name = '$file_name'
                       and c.bonus != '0'
                ");

                ### Don't know where to pay 'Q'
                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'Q'
                     where c.file_name = '$file_name'
                       and c.status <> 'P'
                ");

                # Paid Bonus Update
                $ret = DB::statement("
                    update commission_upload c
                       set c.paid_bonus = c.bonus
                     where c.file_name = '$file_name'
                       and c.status = 'P'
                ");

                ### Pay Bonus to spiff_trans
                $ret = DB::statement("
                    insert into spiff_trans 
                          (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, commission_upload_id, orig_spiff_amt, created_by, cdate, note)
                    select  'S', 0, '0000000000', c.account_id, c.product_id, 0, 'S', 0, c.paid_bonus, c.id, 0, c.created_by, c.cdate, 'Bonus By Account'
                      from commission_upload c
                     where c.status = 'P'");

                $ret = DB::statement("
                    update commission_upload c
                       set c.status = 'S'
                     where c.status = 'P'
                ");

            } else {
                $this->output('Please select valid file');
            }

            $this->output('Your request has been processed successfully! Total ' . $line_no . ' records imported', true, false);

        } catch (\Exception $ex) {
            DB::rollback();
            if($ex->getCode() == 23000){
                $this->output('Account ID is not exist!' . ' [' . $ex->getCode() . ']');
            }
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function upload_bonus_by_acct_temp(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            $v = Validator::make($request->all(), [
                'file_a' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v as $m) {
                    $msg .= $m . "\n";
                }

                $this->output($msg);
            }

            $key = 'file_a';

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

                    $account = trim($row->account);
                    if(strlen($account) != 6){
                        continue;
                    }
                    $bonus = trim($row->bonus);
                    $notes = trim($row->notes);
                    $product = $request->product_a;

                    $rec = CommissionUpload::where('file_name', $file_name)
                        ->where('account_id', $account)
                        ->first();

                    if (!empty($rec)) {
                        return back()->withErrors([
                            'exception' => 'Duplicated record found with same file name and account. Possible re-upload. Line : ' . $line_no
                        ])->withInput();
//                        throw new \Exception('Duplicated record found with same file name and account. Possible re-upload. Line : ' . $line_no);
                    }

                    $rec = new CommissionUploadTemp();
                    $rec->file_name = $file_name;
                    $rec->phone = '';
                    $rec->sim = '';
                    $rec->account_id = $account;
                    $rec->value = $bonus;
                    $rec->month = '0';
                    $rec->bonus = $bonus;
                    $rec->notes = $notes;
                    $rec->product_id = $product;
                    $rec->status = 'N';

                    $rec->cdate = Carbon::now();
                    $rec->created_by = Auth::user()->user_id;
                    $rec->save();
                }

                ### Pay target to 'P'
                $ret = DB::statement("
                    update commission_upload_temp c
                       set c.status = 'P'
                     where c.file_name = '$file_name'
                       and c.bonus != '0'
                ");

                ### Don't know where to pay 'Q'
                $ret = DB::statement("
                    update commission_upload_temp c
                       set c.status = 'Q'
                     where c.file_name = '$file_name'
                       and c.status <> 'P'
                ");

                # Paid Bonus Update
                $ret = DB::statement("
                    update commission_upload_temp c
                       set c.paid_bonus = c.bonus
                     where c.file_name = '$file_name'
                       and c.status = 'P'
                ");

//                ### Pay Bonus to spiff_trans
//                $ret = DB::statement("
//                    insert into spiff_trans
//                          (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, commission_upload_id, orig_spiff_amt, created_by, cdate, note)
//                    select  'S', 0, '0000000000', c.account_id, c.product_id, 0, 'S', 0, c.paid_bonus, c.id, 0, c.created_by, c.cdate, 'Bonus By Account'
//                      from commission_upload c
//                     where c.status = 'P'");
//
//                $ret = DB::statement("
//                    update commission_upload c
//                       set c.status = 'S'
//                     where c.status = 'P'
//                ");

            } else {
                $this->output('Please select valid file');
            }

            return redirect('/admin/reports/vendor/commission_bonus_by_acct_temp')->with('status', 'Upload Preview Completed!');
//            $this->output('Your request has been processed successfully! Total ' . $line_no . ' records imported', true, false);

        } catch (\Exception $ex) {
            DB::rollback();
            if($ex->getCode() == 23000){
                $this->output('Account ID is not exist!' . ' [' . $ex->getCode() . ']');
            }
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function show_bonus_by_acct_temp(Request $request) {

        $excel = Input::get('excel');

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        $query = CommissionUploadTemp::query();

        if ($excel == 'Y') {
            $data = $query->orderBy('id', 'desc')->get();
            Excel::create('commissions', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'File.Name' => $a->file_name,
                            'Product' => $a->product_id,
                            'Product.Name' => $a->product_name,
                            'Phone' => $a->phone,
                            'SIM' => $a->sim,
                            'Month' => $a->month,
                            'Spiff.Month' => $a->spiff_month,
                            'Status' => $a->status == 'S' ? 'Paid' : 'Unpaid',
                            'Denom' => empty($a->denom) ? '' : '$' . number_format($a->denom, 2),
                            'Spiff' => '$' . number_format($a->spiff, 2),
                            'Spiff.Will' => '$' . number_format($a->paid_spiff, 2),
                            'Residual' => '$' . number_format($a->residual, 2),
                            'Residual.Will' => '$' . number_format($a->paid_residual, 2),
                            'Bonus' => '$' . number_format($a->bouns, 2),
                            'Bonus.Will'=> '$' . number_format($a->paid_bonus, 2),
                            'Value' => '$' . number_format($a->value, 2),
                            'Total' => '$' . number_format($a->total, 2),
                            'Description' => $a->notes,
                            'Account' => empty($a->account_id) ? '' : $wizard->toRichTextObject(Helper::get_parent_name_html($a->account_id) . '<span>' . Helper::get_hierarchy_img($a->account_type)  . '</span>' . $a->account_name . ' (' . $a->account_id . ')' ),
                            'Act.Tx.ID' => $a->trans_id,
                            'Act.Date' => $a->act_date,
                            'Upload.Date' => $a->cdate,
                            'Upload.By' => $a->created_by
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->orderBy('id', 'desc')->paginate();
        $row = $query->orderBy('id', 'desc')->first();
        $file_name = !empty($row->file_name) ? $row->file_name : '';
        return view('admin.reports.vendor.commission-bonus-by-acct-preview', [
            'data' => $data,
            'file_name' => $file_name
        ]);
    }

    public function upload_bonus_by_acct_final(Request $request) {

        if (!in_array(Auth::user()->user_id, ['thomas', 'admin', 'system']) && getenv('APP_ENV') != 'local') {
            return redirect('/admin');
        }

        try {

            ### Pay Bonus to spiff_trans
            $ret = DB::statement("
                insert into spiff_trans
                      (type, trans_id, phone, account_id, product_id, denom, account_type, spiff_month, spiff_amt, commission_upload_id, orig_spiff_amt, created_by, cdate, note)
                select  'S', 0, '0000000000', c.account_id, c.product_id, 0, 'S', 0, c.paid_bonus, c.id, 0, c.created_by, c.cdate, 'Bonus By Account'
                  from commission_upload_temp c
                 where c.status = 'P'");

            ### Copy commission_upload_temp to commission_upload ###
            $ret = DB::statement("
                insert into commission_upload (file_name,phone,sim,date_added,value,total,spiff,residual,bonus,bonus_d,bonus_m,month,notes,product_id,product_name,denom,account_id,account_name,account_type,d_account_id,m_account_id,trans_id,act_date,spiff_month,status,paid_spiff,paid_d_spiff,paid_m_spiff,paid_residual,paid_bonus,paid_bonus_d,paid_bonus_m,created_by,cdate)
                select file_name,phone,sim,date_added,value,total,spiff,residual,bonus,bonus_d,bonus_m,month,notes,product_id,product_name,denom,account_id,account_name,account_type,d_account_id,m_account_id,trans_id,act_date,spiff_month,status,paid_spiff,paid_d_spiff,paid_m_spiff,paid_residual,paid_bonus,paid_bonus_d,paid_bonus_m,created_by,cdate
                from commission_upload_temp");

            ### Delete commission_upload_temp ###
            $ret = DB::statement("truncate table commission_upload_temp");

            ### Update commission_upload status ###
            $ret = DB::statement("
                update commission_upload c
                   set c.status = 'S'
                 where c.status = 'P'
            ");

            return redirect('/admin/reports/vendor/commission')->with('status', 'Bonus Pay By Account Completed!');

        } catch (\Exception $ex) {
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    private function output($msg, $close_modal = false, $is_error = true) {
        echo "<script>";

        if ($close_modal) {
            echo "parent.close_modal('div_upload');";
            echo "parent.close_modal('div_upload_bonus');";
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

//    private function output_temp($msg, $close_modal = false, $is_error = true) {
//        echo "<script>";
//
//        if ($close_modal) {
//            echo "parent.close_modal('div_upload');";
//            echo "parent.close_modal('div_upload_bonus');";
//        }
//
//        $msg = addslashes($msg);
//        $msg = str_replace("\r\n", "\t", $msg);
//        $msg = str_replace("\n", "\t", $msg);
//        $msg = str_replace("\r", "\t", $msg);
//
//        if ($is_error) {
//            echo "parent.myApp.hideLoading();";
//            echo "parent.myApp.showError('$msg');";
//        } else {
//            echo "parent.myApp.hideLoading();";
//            echo "parent.myApp.showSuccess('$msg');";
//        }
//        echo "</script>";
//        exit;
//    }

    public function batchLookup(Request $request) {
        try {

            $type = $request->batch_res_type;
            $ress = trim($request->batch_ress);
            if (empty($ress)) {
                $this->output_error('Please enter RESs to lookup');
            }

            $res_array = explode(PHP_EOL, $ress);

            $data = [];
            $data_found = [];
            $data_not_found = [];

            foreach ($res_array as $res) {
                $res = trim($res);

                switch ($type) {
                    case 'M':
                        $res_objs = ReUPCommission::where('mdn', $res)->get();
                        break;
                    case 'D':
                        $res_objs = ReUPCommission::where('device_id', $res)->get();
                        break;
                    case 'I':
                        $res_objs = ReUPCommission::where('iccid', $res)->get();
                        break;
                    
                }

                if (!empty($res_objs) && count($res_objs) > 0) {
                    foreach ($res_objs as $res_obj) {

                        $a = new \stdClass();
                        $a->res = $res;

                        $a->id              = $res_obj->id;
                        $a->file_name       = $res_obj->file_name;
                        $a->mdn             = $res_obj->mdn;
                        $a->device_id       = $res_obj->device_id;
                        $a->iccid           = $res_obj->iccid;
                        $a->plan_value      = $res_obj->plan_value;
                        $a->dealer_month2_payout    = $res_obj->dealer_month2_payout;
                        $a->dealer_month3_payout    = $res_obj->dealer_month3_payout;
                        $a->dealer_residual_payout  = $res_obj->dealer_residual_payout;
                        $a->total_dealer_payout     = $res_obj->total_dealer_payout;
                        $a->dealer_m2_paid          = $res_obj->dealer_m2_paid;
                        $a->dealer_m3_paid          = $res_obj->dealer_m3_paid;
                        $a->dealer_residual_paid    = $res_obj->dealer_residual_paid;
                        $a->total_dealer_paid       = $res_obj->total_dealer_paid;
                        $a->master_month2_payout    = $res_obj->master_month2_payout;
                        $a->master_month3_payout    = $res_obj->master_month3_payout;
                        $a->master_residual_payout  = $res_obj->master_residual_payout;
                        $a->total_master_payout     = $res_obj->total_master_payout;
                        $a->description     = $res_obj->description;
                        $a->account_id      = $res_obj->account_id;
                        $a->account_type    = $res_obj->account_type;
                        $a->account_name    = $res_obj->account_name;
                        $a->act_trans_id    = $res_obj->act_trans_id;
                        $a->upload_date     = $res_obj->upload_date;
                        $a->upload_by       = $res_obj->upload_by;

                        $data_found[] = $a;
                    }
                } else {

                    $a = new \stdClass();
                    $a->res = $res;

                    $a->id              = 'Not Found';
                    $a->file_name       = '';
                    $a->mdn             = $type == 'M' ? $res : '';
                    $a->device_id       = $type == 'D' ? $res : '';
                    $a->iccid           = $type == 'I' ? $res : '';
                    $a->plan_value      = 0;
                    $a->dealer_month2_payout    = 0;
                    $a->dealer_month3_payout    = 0;
                    $a->dealer_residual_payout  = 0;
                    $a->total_dealer_payout     = 0;
                    $a->dealer_m2_paid          = 0;
                    $a->dealer_m3_paid          = 0;
                    $a->dealer_residual_paid    = 0;
                    $a->total_dealer_paid       = 0;
                    $a->master_month2_payout    = 0;
                    $a->master_month3_payout    = 0;
                    $a->master_residual_payout  = 0;
                    $a->total_master_payout     = 0;
                    $a->description     = '';
                    $a->account_id      = '';
                    $a->account_type    = '';
                    $a->account_name    = '';
                    $a->act_trans_id    = '';
                    $a->upload_date     = '';
                    $a->upload_by       = '';

                    $data_not_found[] = $a;
                }

                //$data[] = $o;
            }

            $data = array_merge($data_found, $data_not_found);
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

        } catch (\Exception $ex) {
            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . ']');
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