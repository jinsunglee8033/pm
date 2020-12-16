<?php
/**
 * Created by Royce
 * Date: 6/21/18
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Model\Account;
use App\Model\Product;
use App\Model\Denom;
use App\Model\StockPin;
use App\Model\StockPinTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\StockSim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class StockPinController extends Controller
{
    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $sdate = null;
        $edate = null;
        $used_sdate = null;
        $used_edate = null;

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        if (!empty($request->used_sdate)) {
            $used_sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->used_sdate . ' 00:00:00');
        }

        if (!empty($request->used_edate)) {
            $used_edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->used_edate . ' 23:59:59');
        }

        $data = StockPin::query();

        if (!empty($sdate)) {
            $data = $data->whereRaw('upload_date >= ?', [$sdate]);
        }

        if (!empty($edate)) {
            $data = $data->whereRaw('upload_date <= ?', [$edate]);
        }

        if (!empty($used_sdate)) {
            $data = $data->whereRaw('used_date >= ?', [$used_sdate]);
        }

        if (!empty($used_edate)) {
            $data = $data->whereRaw('used_date <= ?', [$used_edate]);
        }

        if (!empty($request->pin)) {
            $data = $data->where('pin', $request->pin);
        }

        if (!empty($request->serial)) {
            $data = $data->where('serial', $request->serial);
        }

        if (!empty($request->status)) {
            $data = $data->where('status', $request->status);
        }

        if (!empty($request->product)) {
            $data = $data->where('product', $request->product);
        }

        if ($request->excel == 'Y') {
            $data = $data->orderBy('id', 'desc')->get();

            Excel::create('pin', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'PIN' => $a->pin,
                            'Serial' => $a->serial,
                            'Product' => $a->product,
                            'Amount'   => $a->amount,
                            'Status' => $a->status,
                            'Supplier' => $a->supplier,
                            'Comments' => $a->comments,
                            'Used.Tx.ID' => $a->used_trans_id,
                            'Upload.Date' => $a->upload_date,
                            'Download.Date' => date("m/d/Y h:i:s A")
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $data->orderBy('id', 'desc')->paginate(20);

        $sub_carriers = StockPin::select('sub_carrier')->whereRaw("sub_carrier <> ''")->groupBy('sub_carrier')->orderBy('sub_carrier')->get();
        $carriers = Product::where('type', 'Wireless')->where('status', 'A')->where('activation', 'Y')->distinct()->get(['carrier']);

        $products = Product::where('carrier', 'ROKiT')->get();

        return view('admin.settings.pin', [
            'data' => $data,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'used_sdate' => $used_sdate ? $used_sdate->format('Y-m-d') : null,
            'used_edate' => $used_edate ? $used_edate->format('Y-m-d') : null,
            'pin' => $request->pin,
            'serial' => $request->serial,
            'status' => $request->status,
            'product' => $request->product,
            'carrier' => $request->carrier,
            'sub_carriers' => $sub_carriers,
            'carriers' => $carriers,
            'products' => $products
        ]);
    }

    public function batchLookup(Request $request) {

        DB::beginTransaction();
        try {
            $pins = preg_split('/[\ \r\n\,]+/', $request->batch_pins);

            foreach ($pins as $pin){

                $pin_obj = StockPin::where('pin', $pin)->where('status', $request->before)->first();

                if(empty($pin_obj)){
                    $this->output_error('Please check current status of PIN : ' . $pin );
                }

                $pin_obj->status = $request->after;
                $pin_obj->save();

                $spt = New StockPinTransaction();
                $spt->pin = $pin;
                $spt->prod_id = $pin_obj->product;
                $spt->before_status = $request->before;
                $spt->after_status = $request->after;
                $spt->modified_by = Auth::user()->user_id;
                $spt->modified_date = Carbon::now();
                $spt->save();
            }

            DB::commit();
            $this->output_success();

        } catch (\Exception $ex) {
            DB::rollback();
            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString());
        }
    }

    public function upload(Request $request) {

        Helper::log('### PIN Upload Exception ###', [
            'res' => $request->all()
        ]);

        ini_set('max_execution_time', 600);

        DB::beginTransaction();

        $line = '';

        try {

            $key = 'pin_csv_file';

            if (!Input::hasFile($key) || !Input::file($key)->isValid()) {
                $this->output_error('Please select PIN CSV file to upload');
            }

            if (empty($request->product)) {
                $this->output_error('Please select product');
            }

            $path = Input::file($key)->getRealPath();

            $binder = new SimValueBinder();
            $results = Excel::setValueBinder($binder)->load($path)->setSeparator('_')->get();
            $line_no = 0;

            foreach ($results as $row) {

                $line_no++;

                $pin = $row->pin;
                $serial = $row->serial;
                $product = $request->product;
                $amount = $row->amount;
                $status = $row->status;
                $supplier = $row->supplier;
                $comments = $row->comments;

                if (trim($pin) == '') {
                    continue;
                }

                if (trim($serial) == '') {
                    $this->output_error('Empty Serial found at line : ' . $line_no);
                }

                if (trim($amount) == '') {
                    $this->output_error('Empty Amount found at line : ' . $line_no);
                }

                if (empty(trim($status))) {
                    $status = 'A';
                }

                if (!in_array($status, ['A'])) {
                    $this->output_error('Invalid status value found at line : ' . $line_no . ' (Only A available) ');
                }

                $pin_obj = StockPin::where('pin', $pin)->where('product', $product)->first();


                if (!empty($pin_obj)) {
                    throw new \Exception('Duplicated record found with same file name and MDN. Possible re-upload. Line : ' . $line_no);
                }

                $pin_used_already = false;
                if (!empty($pin_obj)) {
                    $this->output_error('Duplicated PIN found at line : ' . $line_no );
                } else {
                    $pin_obj = new StockPin();
                }

                if (!$pin_used_already) {
                    $pin_obj->pin = $pin;
                    $pin_obj->serial = $serial;
                    $pin_obj->product = $product;
                    $pin_obj->amount = $amount;
                    $pin_obj->status = $status;
                    $pin_obj->supplier = $supplier;
                }

                $pin_obj->sub_carrier = 'Perfect Mobile';
                $pin_obj->comments = $comments;
                $pin_obj->upload_date = Carbon::now();

                $pin_obj->save();
            }


            DB::commit();

            $this->output_success();

        } catch (\Exception $ex) {
            DB::rollback();

            Helper::log('### PIN Upload Exception ###', [
                'line' => $line,
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);

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

    private function output_success() {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.close_modal();";
        echo "</script>";
        exit;
    }
}