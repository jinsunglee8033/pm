<?php
/**
 * Created by Royce.
 * Date: 6/22/18
 * Time: 3:25 PM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

class MappingController extends Controller
{
    public function show(Request $request) {


        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }


        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $data = StockMapping::query();

        if (!empty($request->sim)) {
            $data = $data->where('sim', 'like', '%' . $request->sim . '%');
        }

        if (!empty($request->esn)) {
            $data = $data->where('esn', 'like', '%' . $request->esn . '%');
        }

        if (!empty($request->product)) {
            $data = $data->where('product', $request->product);
        }

        if ($request->excel == 'Y') {
            $data = $data->orderBy('sim', 'asc')->get();
            Excel::create('mapping', function($excel) use($data) {

                $excel->sheet('mapping', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {
                        $reports[] = [
                            'SIM #' => $a->sim,
                            'Device.ID' => $a->esn,
                            'Product' => $a->product_name,
                            'Status' => $a->status,
                            'Upload.Date' => $a->upload_date,
                            'Download.Date' => date("m/d/Y h:i:s A")
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $data->orderBy('sim', 'asc')->paginate(20);
        $products = Product::where('activation', 'Y')->get();

        foreach ($data as $d) {
            $d->sim_obj = StockSim::where('sim_serial', $d->sim)->where('product', $d->product)->first();
            $d->esn_obj = StockESN::where('esn', $d->esn)->where('product', $d->product)->first();
        }
        
        return view('admin.settings.mapping', [
            'sim' => $request->sim,
            'esn' => $request->esn,
            'product' => $request->product,
            'data' => $data,
            'products' => $products
        ]);
    }

    public function bind(Request $request) {
        try {

            $clear = trim($request->clear);
            $binds = trim($request->binds);
            if (empty($binds)) {
                return response()->json([
                    'msg' => 'Please enter mapping information (SIM #, ESN #, Status).'
                ]);
            }

            DB::beginTransaction();

            $binds_array = explode(PHP_EOL, $binds);
            $line_no = 1;
            foreach ($binds_array as $bind) {
                $ref_arr = explode(',', $bind);

                $count = count($ref_arr);

                if (empty($ref_arr) || $count < 2) {
                    throw new \Exception('Mapping : ' . $bind . ' is not correct format at line : ' . $line_no);
                }

                $sim = trim($ref_arr[0]);
                $esn = trim($ref_arr[1]);

                if ($clear == 'Y') {
                    $mapping = StockMapping::where('sim', $sim)->where('esn', $esn)->where('status', '<>', 'U')->first();
                    if (empty($mapping)) {
                        throw new \Exception('The mapping is not in our DB at line : ' . $line_no);
                    }

                    $mapping->delete();
                } else {

                    $sim_obj = StockSim::where('sim_serial', $sim)->where('product', $request->product)->first();
                    if (empty($sim_obj)) {
                        throw new \Exception('SIM : ' . $sim . ' not in our DB at line : ' . $line_no);
                    }

                    $esn_obj = StockESN::where('esn', $esn)->where('product', $request->product)->first();
                    if (empty($esn_obj)) {
                        throw new \Exception('ESN : ' . $esn . ' not in our DB at line : ' . $line_no);
                    }

                    $sim_mapping = StockMapping::where('sim', $sim)->where('product', $request->product)->where('status', 'A')->first();

                    if (empty($sim_mapping)) {
                        $mapping = new StockMapping();
                        $mapping->product   = $sim_obj->product;
                        $mapping->sim       = $sim;
                        $mapping->esn       = $esn;
                        $mapping->status    = 'A';
                        $mapping->upload_date = Carbon::now();
                        $mapping->save();
                    } else {
                        throw new \Exception('SIM or ESN is in our mapping DB at line : ' . $line_no);
                    }
                }

                $line_no++;
            }

            DB::commit();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {

            DB::rollback();
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
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
                    case 'S':
                        $res_objs = StockMapping::where('sim', $res)->where('product', $request->product)->get();
                        break;
                    case 'D':
                        $res_objs = StockMapping::where('esn', $res)->where('product', $request->product)->get();
                        break;
                    
                }

                if (!empty($res_objs) && count($res_objs) > 0) {
                    foreach ($res_objs as $res_obj) {

                        $a = new \stdClass();
                        $a->sim             = $res_obj->sim;
                        $a->esn             = $res_obj->esn;
                        $a->product_name    = $res_obj->product_name;
                        $a->status          = $res_obj->status;
                        $a->upload_date     = $res_obj->upload_date;

                        $data_found[] = $a;
                    }
                } else {

                    $a = new \stdClass();
                    $a->sim             = $type == 'S' ? $res : '';
                    $a->esn             = $type == 'D' ? $res : '';
                    $a->product_name    = 'Not Found';
                    $a->status          = '';
                    $a->upload_date     = '';

                    $data_not_found[] = $a;
                }

            }

            $data = array_merge($data_found, $data_not_found);
            Excel::create('mapping', function($excel) use($data) {

                $excel->sheet('mappings', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {
                        
                        $wizard = new PHPExcel_Helper_HTML;

                        $reports[] = [
                            'SIM' => $a->sim,
                            'Device.ID' => $a->esn,
                            'Product' => $a->product_name,
                            'Status' => $a->status,
                            'Upload.Date' => $a->upload_date
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

    private function output_success() {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.close_modal();";
        echo "</script>";
        exit;
    }
}