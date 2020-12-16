<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/8/18
 * Time: 2:58 PM
 */

namespace App\Http\Controllers\Admin\Reports\Vendor\ReUP;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\RebateProcessor;
use App\Lib\SimValueBinder;
use App\Lib\SpiffProcessor;
use App\Model\ReUPChargeBack;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class ChargeBackController extends Controller
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

        $query = ReUPChargeBack::query();

        if (!empty($request->phone)) {
            $query = $query->where('mdn', 'like', '%' . $request->phone . '%');
        }

        if (!empty($sdate)) {
            $query = $query->where('upload_date', '>=', $sdate);
        }

        if (!empty($edate)) {
            $query = $query->where('upload_date', '<=', $edate);
        }

        if (!empty($request->file_name)) {
            $query = $query->where(DB::raw('lower(file_name)'), 'like', '%' . strtolower($request->file_name) . '%');
        }

        if (!empty($request->sim)) {
            $query = $query->where('iccid', 'like', '%' . $request->sim . '%');
        }

        if ($request->excel == 'Y') {
            $data = $query->orderBy('id', 'desc')->get();
            Excel::create('charge_back', function($excel) use($data) {

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

        $data = $query->orderBy('id', 'desc')->paginate();

        return view('admin.reports.vendor.reup.charge-back', [
            'data' => $data,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'phone' => $request->phone,
            'file_name' => $request->file_name,
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
                $results = Excel::setValueBinder($binder)->setDelimiter(';')->load($path)->setSeparator('_')->get();

                $line_no = 0;
                foreach ($results as $row) {

                    $line_no++;

                    $ma_id = trim($row->ma_id);
                    if ($ma_id == 'NULL') {
                        $ma_id = null;
                    }

                    $ma_name = trim($row->ma_name);
                    if ($ma_name == 'NULL') {
                        $ma_name = null;
                    }

                    $retailer_id = trim($row->retailer_id);
                    if ($retailer_id == 'NULL') {
                        $retailer_id = null;
                    }

                    $dealer_name = trim($row->store_name);
                    if ($dealer_name == 'NULL') {
                        $dealer_name = null;
                    }

                    $mdn = trim($row->mdn);
                    $device_id = trim($row->device_id);
                    $iccid = trim($row->iccid);

                    $plan_value = trim($row->plan_value);
                    $dealer_month2_payout = trim($row->dealer_month2_payout);
                    $dealer_month3_payout = trim($row->dealer_month3_payout);
                    $dealer_residual_payout = trim($row->dealer_residual_payout);
                    $total_dealer_payout = trim($row->total_dealer_payout);

                    $master_month2_payout = trim($row->master_month2_payout);
                    $master_month3_payout = trim($row->master_month3_payout);
                    $master_residual_payout = trim($row->master_residual_payout);
                    $total_master_payout = trim($row->total_master_payout);

                    $description = trim($row->description);

                    $rec = ReUPChargeBack::where('file_name', $file_name)
                        ->where('mdn', $mdn)
                        ->whereRaw('trim(lower(description)) = \'' . strtolower($description) . '\'')
                        ->first();

                    if (!empty($rec)) {
                        throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
                    }

                    $rec = new ReUPChargeBack;
                    $rec->file_name = $file_name;
                    $rec->ma_id = $ma_id;
                    $rec->ma_name = $ma_name;
                    $rec->retailer_id = $retailer_id;
                    $rec->dealer_name = $dealer_name;
                    $rec->mdn = $mdn;
                    $rec->device_id = $device_id;
                    $rec->iccid = $iccid;
                    $rec->plan_value = $plan_value;
                    $rec->dealer_month2_payout = $dealer_month2_payout;
                    $rec->dealer_month3_payout = $dealer_month3_payout;
                    $rec->dealer_residual_payout = $dealer_residual_payout;
                    $rec->total_dealer_payout = $total_dealer_payout;
                    $rec->master_month2_payout = $master_month2_payout;
                    $rec->master_month3_payout = $master_month3_payout;
                    $rec->master_residual_payout = $master_residual_payout;
                    $rec->total_master_payout = $total_master_payout;
                    $rec->description = $description;

                    $rec->upload_date = Carbon::now();
                    $user_id = Auth::user()->user_id;
                    $rec->upload_by = $user_id;

                    ### find activation account ###
                    $trans = Transaction::where('type', 'S')
                        ->where('phone', $mdn)
                        ->whereIn('product_id', ['WROKC', 'WROKG', 'WROKS'])
                        ->whereIn('action', ['Activation', 'Port-In'])
                        ->where('status', 'C')
                        ->first();

                    $account_id = null;
                    if (!empty($trans)) {
                        $account_id = $trans->account_id;
                        $rec->account_id = $account_id;

                        $ret = SpiffProcessor::void_spiff($trans->id);
                        if (!empty($ret['error_code'])) {
                            throw new \Exception($ret['error_msg'], $ret['error_code']);
                        }

                        $ret = RebateProcessor::void_rebate($trans->id);
                        if (!empty($ret['error_code'])) {
                            throw new \Exception($ret['error_msg'], $ret['error_code']);
                        }
                    }

                    $rec->save();

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
                        $res_objs = ReUPChargeBack::where('mdn', $res)->get();
                        break;
                    case 'D':
                        $res_objs = ReUPChargeBack::where('device_id', $res)->get();
                        break;
                    case 'I':
                        $res_objs = ReUPChargeBack::where('iccid', $res)->get();
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
            Excel::create('charge_back', function($excel) use($data) {

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