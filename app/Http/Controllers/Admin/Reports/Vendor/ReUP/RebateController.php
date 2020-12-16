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
use App\Lib\SimValueBinder;
use App\Model\ReUPRebate;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class RebateController extends Controller
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

        $query = ReUPRebate::query();

        if (!empty($request->device_id)) {
            $query = $query->where('device_id', 'like', '%' . $request->device_id . '%');
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

        if ($request->excel == 'Y') {
            $data = $query->orderBy('id', 'desc')->get();
            Excel::create('rebate', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'File.Name' => $a->file_name,
                            'TSP' => $a->tsp,
                            'MA.ID' => $a->ma_id,
                            'MA.Name' => $a->ma_name,
                            'Sale.Date' => $a->sale_date,
                            'Retailer.ID' => $a->retailer_id,
                            'Store.Name' => $a->store_name,
                            'Device.ID' => $a->device_id,
                            'Payout.On.File' => '$' . number_format($a->payout, 2),
                            'Paid' => '$' . number_format($a->paid, 2),
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

        return view('admin.reports.vendor.reup.rebate', [
            'data' => $data,
            'sdate' => empty($sdate) ? '' : $sdate->format('Y-m-d'),
            'edate' => empty($edate) ? '' : $edate->format('Y-m-d'),
            'device_id' => $request->device_id,
            'file_name' => $request->file_name
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

                    $tsp = trim($row->tsp);
                    $ma_id = trim($row->ma_id);
                    if (empty($ma_id)) {
                        $ma_id = null;
                    }
                    $ma_name = trim($row->ma_name);
                    $sale_date = trim($row->sale_date);
                    $sale_date = str_replace("\"", "", $sale_date);
                    $retailer_id = trim($row->retailer_id);
                    if (empty($retailer_id)) {
                        $retailer_id = null;
                    }
                    $store_name = trim($row->store_name);
                    $device_id = trim($row->device_id);
                    $payout = trim($row->payout);

                    Helper::log('### LINE ###', [
                        'tsp' => $tsp,
                        'ma_id' => $ma_id,
                        'ma_name' => $ma_name,
                        'sales_date' => $sale_date,
                        'retailer_id' => $retailer_id,
                        'store_name' => $store_name,
                        'device_id' => $device_id,
                        'payout' => $payout
                    ]);

                    $rec = ReUPRebate::where('file_name', $file_name)
                        ->where('device_id', $device_id)
                        ->first();

                    if (!empty($rec)) {
                        throw new \Exception('Duplicated record found with same file name and device ID. Possible re-upload. Line : ' . $line_no);
                    }

                    $rec = new ReUPRebate;
                    $rec->file_name = $file_name;
                    $rec->tsp = $tsp;
                    $rec->ma_id = $ma_id;
                    $rec->ma_name = $ma_name;
                    $rec->sale_date = Carbon::createFromFormat('Y-m-d H:i:s', $sale_date);
                    $rec->retailer_id = $retailer_id;
                    $rec->store_name = $store_name;
                    $rec->device_id = $device_id;
                    $rec->payout = $payout;

                    $rec->upload_date = Carbon::now();
                    $user_id = Auth::user()->user_id;
                    $rec->upload_by = $user_id;

                    ### find activation account ###
                    $trans = Transaction::where('type', 'S')
                        ->where(function($query) use ($device_id) {
                            $query->where('sim', $device_id);
                            $query->orWhere('esn', $device_id);
                        })
                        ->whereIn('product_id', ['WROKC', 'WROKG', 'WROKS'])
                        ->whereIn('action', ['Activation', 'Port-In'])
                        ->where('status', 'C')
                        ->first();

                    if (!empty($trans)) {
                        $rec->account_id = $trans->account_id;
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
                $this->output_error('Please enter device IDs to lookup');
            }

            $res_array = explode(PHP_EOL, $ress);

            $data = [];
            $data_found = [];
            $data_not_found = [];

            foreach ($res_array as $res) {
                $res = trim($res);

                $res_objs = ReUPRebate::where('device_id', $res)->get();

                if (!empty($res_objs) && count($res_objs) > 0) {
                    foreach ($res_objs as $res_obj) {

                        $a = new \stdClass();
                        $a->res = $res;

                        $a->id              = $res_obj->id;
                        $a->file_name       = $res_obj->file_name;
                        $a->tsp             = $res_obj->tsp;
                        $a->ma_id           = $res_obj->ma_id;
                        $a->ma_name         = $res_obj->ma_name;
                        $a->sale_date       = $res_obj->sale_date;
                        $a->retailer_id     = $res_obj->retailer_id;
                        $a->store_name      = $res_obj->store_name;
                        $a->device_id       = $res_obj->device_id;
                        $a->payout          = $res_obj->payout;
                        $a->paid            = $res_obj->paid;
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
                    $a->tsp             = '';
                    $a->ma_id           = '';
                    $a->ma_name         = '';
                    $a->sale_date       = '';
                    $a->retailer_id     = '';
                    $a->store_name      = '';
                    $a->device_id       = $res;
                    $a->payout          = 0;
                    $a->paid            = 0;
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
            Excel::create('rebate', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {
                        
                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'ID' => $a->id,
                            'File.Name' => $a->file_name,
                            'TSP' => $a->tsp,
                            'MA.ID' => $a->ma_id,
                            'MA.Name' => $a->ma_name,
                            'Sale.Date' => $a->sale_date,
                            'Retailer.ID' => $a->retailer_id,
                            'Store.Name' => $a->store_name,
                            'Device.ID' => $a->device_id,
                            'Payout.On.File' => '$' . number_format($a->payout, 2),
                            'Paid' => '$' . number_format($a->paid, 2),
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