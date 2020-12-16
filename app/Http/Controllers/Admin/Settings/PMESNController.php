<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/22/17
 * Time: 11:35 AM
 */

namespace App\Http\Controllers\Admin\Settings;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Model\Account;
use App\Model\Denom;
use App\Model\PMESN;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class PMESNController extends Controller
{

    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $sdate = null;
        $edate = null;

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $data = PMESN::query();

        if (!empty($sdate)) {
            $data = $data->whereRaw('upload_date >= ?', [$sdate]);
        }

        if (!empty($edate)) {
            $data = $data->whereRaw('upload_date <= ?', [$edate]);
        }

        if (!empty($request->esn)) {
            $data = $data->where('esn', 'like', '%' . $request->esn . '%');
        }

        if (!empty($request->status)) {
            $data = $data->where('status', $request->status);
        }

        if (!empty($request->type)) {
            $data = $data->where('type', $request->type);
        }

        if (!empty($request->rtr_month)) {
            $data = $data->where('rtr_month', $request->rtr_month);
        }

        if (!empty($request->supplier)) {
            $data = $data->whereRaw('lower(supplier) like ?', ['%' . strtolower($request->supplier). '%']);
        }

        if (!empty($request->supplier_date)) {
            $data = $data->whereRaw('lower(supplier_date) like ?', ['%' . strtolower($request->supplier_date). '%']);
        }

        if (!empty($request->supplier_memo)) {
            $data = $data->whereRaw('lower(supplier_memo) like ?', ['%' . strtolower($request->supplier_memo). '%']);
        }

        if (!empty($request->buyer_name)) {
            $data = $data->whereRaw('lower(buyer_name) like ?', ['%' . strtolower($request->buyer_name). '%']);
        }

        if (!empty($request->buyer_date)) {
            $data = $data->whereRaw('lower(buyer_date) like ?', ['%' . strtolower($request->buyer_date). '%']);
        }

        if (!empty($request->buyer_memo)) {
            $data = $data->whereRaw('lower(buyer_memo) like ?', ['%' . strtolower($request->buyer_memo). '%']);
        }

        if (!empty($request->comments)) {
            $data = $data->whereRaw('lower(comments) like ?', ['%' . strtolower($request->comments). '%']);
        }

        if (!empty($request->sub_carrier)) {
            $data = $data->whereRaw('lower(sub_carrier) like ?', ['%' . strtolower($request->sub_carrier). '%']);
        }

        if (!empty($request->c_store_id)) {
            $data = $data->where('c_store_id', $request->c_store_id);
        }

        if (!empty($request->owner_id)) {
            $data = $data->where('owner_id', $request->owner_id);
        }

        if (!empty($request->show_all_c_store)) {
            $data = $data->where(DB::raw("ifnull(c_store_id, '')"), '!=', '');
        }

        if (!empty($request->show_all_owner)) {
            $data = $data->where(DB::raw("ifnull(owner_id, '')"), '!=', '');
        }

        if (!empty($request->subsidy)) {
            $data = $data->where(DB::raw("lower(supplier_subsidy)"), 'like', '%' . strtolower($request->subsidy) . '%');
        }

        if (!empty($request->supplier_make)) {
            $data = $data->where(DB::raw("lower(supplier_make)"), 'like', '%' . strtolower($request->supplier_make) . '%');
        }

        if (!empty($request->supplier_model)) {
            $data = $data->where(DB::raw("lower(supplier_model)"), 'like', '%' . strtolower($request->supplier_model) . '%');
        }

        if (!empty($request->supplier_cost)) {
            $data = $data->where(DB::raw("lower(supplier_cost)"), 'like', '%' . strtolower($request->supplier_cost) . '%');
        }

        if (!empty($request->buyer_price)) {
            $data = $data->where(DB::raw("lower(buyer_price)"), 'like', '%' . strtolower($request->buyer_price) . '%');
        }

        if ($request->excel == 'Y') {
            $data = $data->orderBy('esn', 'asc')->get();
            Excel::create('pm_esn', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'ESN' => $a->esn,
                            'MEID' => $a->meid,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Amount' => $a->amount,
                            'Charge.Amount.R' => $a->charge_amount_r,
                            'Charge.Amount.D' => $a->charge_amount_d,
                            'Charge.Amount.M' => $a->charge_amount_m,
                            'Owner.ID' => $a->owner_id,
                            'Shipped.Date' => $a->shipped_date,
                            'Type' => $a->type,
                            'RTR.Month' => $a->rtr_month,
                            'Spiff.Month' => $a->spiff_month,
                            'Rebate.Month' => $a->rebate_month,
                            'R.Spiff.Override' => $a->spiff_override_r,
                            'D.Spiff.Override' => $a->spiff_override_d,
                            'M.Spiff.Override' => $a->spiff_override_m,
                            'R.Rebate.Override' => $a->rebate_override_r,
                            'D.Rebate.Override' => $a->rebate_override_d,
                            'M.Rebate.Override' => $a->rebate_override_m,
                            'R.Residual' => $a->residual_r,
                            'Status' => $a->status,
                            'Device.Type' => $a->device_type,
                            'Supplier' => $a->supplier,
                            'Subsidy' => $a->supplier_subsidy,
                            'Supplier.Make' => $a->supplier_make,
                            'Supplier.Model' => $a->supplier_model,
                            'Supplier.Cost' => $a->supplier_cost,
                            'Supplier.Date' => $a->supplier_date,
                            'Supplier.Memo' => $a->supplier_memo,
                            'Buyer.Name' => $a->buyer_name,
                            'Buyer.Price' => $a->buyer_price,
                            'Buyer.Date' => $a->buyer_date,
                            'Buyer.Memo' => $a->buyer_memo,
                            'Comments' => $a->comments,
                            'C.Store.ID' => $a->c_store_id,
                            'Used.Tx.ID' => $a->used_trans_id,
                            'Used.Date' => $a->used_date,
                            'Upload.Date' => $a->upload_date,
                            'Download.Date' => date("m/d/Y h:i:s A")
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $data->orderBy('esn', 'asc')->paginate(20);
        $sub_carriers = PMESN::select('sub_carrier')->whereRaw("sub_carrier <> ''")->groupBy('sub_carrier')->orderBy('sub_carrier')->get();

        return view('admin.settings.esn.pm', [
            'data' => $data,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'esn' => $request->esn,
            'status' => $request->status,
            'type' => $request->type,
            'rtr_month' => $request->rtr_month,
            'supplier' => $request->supplier,
            'supplier_date' => $request->supplier_date,
            'supplier_memo' => $request->supplier_memo,
            'buyer_name' => $request->buyer_name,
            'buyer_date' => $request->buyer_date,
            'buyer_memo' => $request->buyer_memo,
            'comments' => $request->comments,
            'c_store_id' => $request->c_store_id,
            'owner_id' => $request->owner_id,
            'sub_carrier' => $request->sub_carrier,
            'sub_carriers' => $sub_carriers,
            'show_all_c_store' => $request->show_all_c_store,
            'show_all_owner' => $request->show_all_owner,
            'subsidy' => $request->subsidy,
            'supplier_make' => $request->supplier_make,
            'supplier_model' => $request->supplier_model,
            'supplier_cost' => $request->supplier_cost,
            'buyer_price' => $request->buyer_price
        ]);
    }

    public function upload(Request $request) {

        ini_set('max_execution_time', 600);

        $line = '';


        DB::beginTransaction();

        try {

            $key = 'sim_csv_file';

            if (!Input::hasFile($key) || !Input::file($key)->isValid()) {
                $this->output_error('Please select SIM CSV file to upload');
            }

            $path = Input::file($key)->getRealPath();

            $binder = new SimValueBinder();
            $results = Excel::setValueBinder($binder)->load($path)->setSeparator('_')->get();

            $line_no = 0;

            foreach ($results as $row) {

                $line_no++;

                $esn = $row->esn;
                $meid = $row->meid;
                $type = $row->type;
                $status = $row->status;
                $rtr_month = $row->rtrmonth;
                $spiff_month  = $row->spiffmonth;
                $rebate_month = $row->rebatemonth;
                $charge_amount_r = $row->chargeamountr;
                $charge_amount_d = $row->chargeamountd;
                $charge_amount_m = $row->chargeamountm;
                $owner_id = $row->ownerid;
                $shipped_date = $row->shippeddate;
                $amt = $row->amount;
                $sub_carrier = $row->subcarrier;
                $spiff_override_r = $row->rspiffoverride;
                $spiff_override_d = $row->dspiffoverride;
                $spiff_override_m = $row->mspiffoverride;
                $rebate_override_r = $row->rrebateoverride;
                $rebate_override_d = $row->drebateoverride;
                $rebate_override_m = $row->mrebateoverride;
                $residual_r = $row->rresidual;
                $device_type = $row->devicetype;
                $supplier = $row->supplier;
                $supplier_subsidy = $row->subsidy;
                $supplier_make = $row->suppliermake;
                $supplier_model = $row->suppliermodel;
                $supplier_cost = $row->suppliercost;
                $supplier_date = $row->supplierdate;
                $supplier_memo = $row->suppliermemo;
                $buyer_name = $row->buyername;
                $buyer_price = $row->buyerprice;
                $buyer_date = $row->buyerdate;
                $buyer_memo = $row->buyermemo;
                $comments = $row->comments;

                if (trim($esn) == '') {
                    $this->output_error('Empty SIM found at line : ' . $line_no);
                }

                $esn = trim($esn);

                if (empty(trim($type))) {
                    $type = 'R';
                }

                if (!in_array($type, ['P', 'B', 'R', 'C'])) {
                    $this->output_error('Invalid type value ' . $type . 'found at line: ' . $line_no);
                }

                if ($type == 'C') {
                    if (empty($charge_amount_r) && empty($charge_amount_d) && empty($charge_amount_m)) {
                        $this->output_error('Charge amount needed for consignment ESN: ' . $line_no);
                    }

                    if (empty($owner_id)) {
                        $this->output_error('Owner ID needed for consignment ESN: ' . $line_no);
                    }

                    $owner = Account::find($owner_id);
                    if (empty($owner)) {
                        $this->output_error('Invalid Owner ID provided for consignment ESN: ' . $line_no);
                    }
                }

                if (empty(trim($status))) {
                    $status = 'A';
                }

                if (!in_array($status, ['A', 'H', 'S', 'U'])) {
                    $this->output_error('Invalid status value found at line : ' . $line_no);
                }

                $rtr_month = trim($rtr_month);

                /*if ($type == 'R' && $rtr_month != '1') {
                    $this->output_error('Regular Device only support 1 RTR.Month at line : ' . $line_no);
                }*/

                if (!empty($rtr_month)) {
                    if (!in_array($rtr_month, ['1','2','3','1|2','1|3','2|3','1|2|3'])) {
                        $this->output_error('Invalid RTR.Month value found. Only 1, 2, 3, 1|2, 1|3, 2|3, 1|2|3 allowed at line : ' . $line_no);
                    }
                }

                if (!empty($spiff_month)) {
                    if (!in_array($spiff_month, ['0','1','2','3','1|2','1|3','2|3','1|2|3'])) {
                        $this->output_error('Invalid Spiff.Month value found. Only 0, 1, 2, 3, 1|2, 1|3, 2|3, 1|2|3 allowed at line : ' . $line_no);
                    }
                }

                if (!in_array($rebate_month, ['0','1','2','3','1|2','1|3','2|3','1|2|3'])) {
                    $this->output_error('Invalid Rebate.Month value found. Only 0, 1, 2, 3, 1|2, 1|3, 2|3, 1|2|3 allowed at line : ' . $line_no);
                }

                // if ($type != 'R') {
                //     $amt_array = explode("|", $amt);
                //     $product_id = '';
                //     switch ($sub_carrier) {
                //         case 'ATT':
                //             $product_id = 'WFRUPA';
                //             break;
                //         case 'SPRINT':
                //             $product_id = 'WFRUPS';
                //             break;
                //     }

                //     $denoms = Denom::where('product_id', $product_id)
                //         ->whereIn('denom', $amt_array)
                //         ->get();

                //     if (count($amt_array) != count($denoms)) {
                //         $this->output_error($amt . ' is not in ' . $sub_carrier . ' denominations at line ' . $line_no);
                //     }
                // }

                $esn_obj = PMESN::find($esn);
                $esn_used_already = false;
                if (!empty($esn_obj)) {

                    ### ignore already used SIM
                    if (!empty($esn->used_trans_id)) {
                        $esn_used_already = true;
                    }
                } else {
                    $esn_obj = new PMESN;
                }

                if (!$esn_used_already) {
                    $esn_obj->esn = $esn;
                    $esn_obj->meid = $meid;
                    $esn_obj->sub_carrier = $sub_carrier;

                    $esn_obj->charge_amount_r = $charge_amount_r;
                    $esn_obj->charge_amount_d = $charge_amount_d;
                    $esn_obj->charge_amount_m = $charge_amount_m;
                    $esn_obj->owner_id = $owner_id;
                    $esn_obj->shipped_date = $shipped_date;

                    $esn_obj->amount = trim($amt) == '' ? null : $amt;

                    $esn_obj->type = $type;
                    $esn_obj->rtr_month = $rtr_month;
                    $esn_obj->spiff_month = trim($spiff_month) == '' ? null : $spiff_month;
                    $esn_obj->rebate_month = trim($rebate_month) == '' ? null : $rebate_month;

                    $esn_obj->spiff_override_r = trim($spiff_override_r) == '' ? null : $spiff_override_r;
                    $esn_obj->spiff_override_d = trim($spiff_override_d) == '' ? null : $spiff_override_d;
                    $esn_obj->spiff_override_m = trim($spiff_override_m) == '' ? null : $spiff_override_m;

                    $esn_obj->rebate_override_r = trim($rebate_override_r) == '' ? null : $rebate_override_r;
                    $esn_obj->rebate_override_d = trim($rebate_override_d) == '' ? null : $rebate_override_d;
                    $esn_obj->rebate_override_m = trim($rebate_override_m) == '' ? null : $rebate_override_m;

                    $esn_obj->residual_r = trim($residual_r) == '' ? null : $residual_r;

                    $esn_obj->status = $status;
                    $esn_obj->device_type = $device_type;
                }

                $esn_obj->supplier = $supplier;
                $esn_obj->supplier_subsidy = $supplier_subsidy;
                $esn_obj->supplier_make = $supplier_make;
                $esn_obj->supplier_model = $supplier_model;
                $esn_obj->supplier_cost = $supplier_cost;
                $esn_obj->supplier_date = $supplier_date;
                $esn_obj->supplier_memo = $supplier_memo;
                $esn_obj->buyer_name = $buyer_name;
                $esn_obj->buyer_price = $buyer_price;
                $esn_obj->buyer_date = $buyer_date;
                $esn_obj->buyer_memo = $buyer_memo;
                $esn_obj->comments = $comments;
                $esn_obj->upload_date = Carbon::now();

                $esn_obj->save();
            }

            DB::commit();

            $this->output_success();

        } catch (\Exception $ex) {
            DB::rollback();

            Helper::log('### PM ESN Upload Exception ###', [
                'line' => $line,
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);

            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function batchLookup(Request $request) {
        try {

            $esns = trim($request->batch_esns);
            if (empty($esns)) {
                $this->output_error('Please enter ESNs to lookup');
            }

            $esn_array = explode(PHP_EOL, $esns);

            $data = [];
            $data_found = [];
            $data_not_found = [];

            foreach ($esn_array as $esn) {
                $esn = trim($esn);

                $o = new \stdClass();

                if ($request->type == 'E') {
                    $esn_obj = PMESN::find($esn);
                } else {
                    $esn_obj = PMESN::where('MEID', $esn)->first();
                }

                if (!empty($esn_obj)) {
                    $o->esn = $esn_obj->esn;
                    $o->meid = $esn_obj->meid;
                    $o->sub_carrier = $esn_obj->sub_carrier;
                    $o->amount = $esn_obj->amount;
                    $o->charge_amount_r = $esn_obj->charge_amount_r;
                    $o->charge_amount_d = $esn_obj->charge_amount_d;
                    $o->charge_amount_m = $esn_obj->charge_amount_m;
                    $o->owner_id = $esn_obj->owner_id;
                    $o->shipped_date = $esn_obj->shipped_date;
                    $o->type = $esn_obj->type;
                    $o->rtr_month = $esn_obj->rtr_month;
                    $o->spiff_month = $esn_obj->spiff_month;
                    $o->rebate_month = $esn_obj->rebate_month;
                    $o->spiff_override_r = $esn_obj->spiff_override_r;
                    $o->spiff_override_d = $esn_obj->spiff_override_d;
                    $o->spiff_override_m = $esn_obj->spiff_override_m;
                    $o->rebate_override_r = $esn_obj->rebate_override_r;
                    $o->rebate_override_d = $esn_obj->rebate_override_d;
                    $o->rebate_override_m = $esn_obj->rebate_override_m;
                    $o->residual_r = $esn_obj->residual_r;
                    $o->status = $esn_obj->status;
                    $o->device_type = $esn_obj->device_type;
                    $o->supplier = $esn_obj->supplier;
                    $o->supplier_subsidy = $esn_obj->supplier_subsidy;
                    $o->supplier_make = $esn_obj->supplier_make;
                    $o->supplier_model = $esn_obj->supplier_model;
                    $o->supplier_cost = $esn_obj->supplier_cost;
                    $o->supplier_date = $esn_obj->supplier_date;
                    $o->supplier_memo = $esn_obj->supplier_memo;
                    $o->buyer_name = $esn_obj->buyer_name;
                    $o->buyer_price = $esn_obj->buyer_price;
                    $o->buyer_date = $esn_obj->buyer_date;
                    $o->buyer_memo = $esn_obj->buyer_memo;
                    $o->comments = $esn_obj->comments;
                    $o->c_store_id = $esn_obj->c_store_id;
                    $o->used_trans_id = $esn_obj->used_trans_id;
                    $o->used_date = $esn_obj->used_date;
                    $o->upload_date = $esn_obj->upload_date;

                    $data_found[] = $o;
                } else {
                    $o->esn     = $request->type == 'E' ? $esn : '';
                    $o->meid    = $request->type == 'M' ? $esn : '';
                    $o->sub_carrier = '';
                    $o->amount = '';
                    $o->charge_amount_r = '';
                    $o->charge_amount_d = '';
                    $o->charge_amount_m = '';
                    $o->owner_id = '';
                    $o->shipped_date = '';
                    $o->type = '';
                    $o->rtr_month = '';
                    $o->spiff_month = '';
                    $o->rebate_month = '';
                    $o->spiff_override_r = '';
                    $o->spiff_override_d = '';
                    $o->spiff_override_m = '';
                    $o->rebate_override_r = '';
                    $o->rebate_override_d = '';
                    $o->rebate_override_m = '';
                    $o->residual_r = '';
                    $o->status = '';
                    $o->device_type = '';
                    $o->supplier = '';
                    $o->supplier_subsidy = '';
                    $o->supplier_make = '';
                    $o->supplier_model = '';
                    $o->supplier_cost = '';
                    $o->supplier_date = '';
                    $o->supplier_memo = '';
                    $o->buyer_name = '';
                    $o->buyer_price = '';
                    $o->buyer_date = '';
                    $o->buyer_memo = '';
                    $o->comments = '';
                    $o->c_store_id = '';
                    $o->used_trans_id = '';
                    $o->used_date = '';
                    $o->upload_date = '';

                    $data_not_found[] = $o;
                }

                //$data[] = $o;
            }

            $data = array_merge($data_found, $data_not_found);
            Excel::create('batch_lookup_esns', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'ESN' => $a->esn,
                            'MEID' => $a->meid,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Amount' => $a->amount,
                            'Charge.Amount.R' => $a->charge_amount_r,
                            'Charge.Amount.D' => $a->charge_amount_d,
                            'Charge.Amount.M' => $a->charge_amount_m,
                            'Owner.ID' => $a->owner_id,
                            'Shipped.Date' => $a->shipped_date,
                            'Type' => $a->type,
                            'RTR.Month' => $a->rtr_month,
                            'Spiff.Month' => $a->spiff_month,
                            'Rebate.Month' => $a->rebate_month,
                            'R.Spiff.Override' => $a->spiff_override_r,
                            'D.Spiff.Override' => $a->spiff_override_d,
                            'M.Spiff.Override' => $a->spiff_override_m,
                            'R.Rebate.Override' => $a->rebate_override_r,
                            'D.Rebate.Override' => $a->rebate_override_d,
                            'M.Rebate.Override' => $a->rebate_override_m,
                            'R.Residual' => $a->residual_r,
                            'Status' => $a->status,
                            'Device.Type' => $a->device_type,
                            'Supplier' => $a->supplier,
                            'Subsidy' => $a->supplier_subsidy,
                            'Supplier.Make' => $a->supplier_make,
                            'Supplier.Model' => $a->supplier_model,
                            'Supplier.Cost' => $a->supplier_cost,
                            'Supplier.Date' => $a->supplier_date,
                            'Supplier.Memo' => $a->supplier_memo,
                            'Buyer.Name' => $a->buyer_name,
                            'Buyer.Price' => $a->buyer_price,
                            'Buyer.Date' => $a->buyer_date,
                            'Buyer.Memo' => $a->buyer_memo,
                            'Comments' => $a->comments,
                            'C.Store.ID' => $a->c_store_id,
                            'Used.Tx.ID' => $a->used_trans_id,
                            'Used.Date' => $a->used_date,
                            'Upload.Date' => $a->upload_date,
                            'Download.Date' => date("m/d/Y h:i:s A")

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