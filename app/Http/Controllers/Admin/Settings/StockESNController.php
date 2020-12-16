<?php
/**
 * Created by Royce.
 * Date: 6/22/18
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Mail\EsnOrder;
use App\Model\Account;
use App\Model\Product;
use App\Model\Denom;
use App\Model\StockESN;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Mail;

class StockESNController extends Controller
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

        $data = StockESN::query();

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

        if (!empty($request->esns)) {
            $esns = preg_split('/[\ \r\n\,]+/', $request->esns);
            $esns = array_map('strtolower', $esns);
            $data = $data->whereIn( DB::raw("lower(esn)") , $esns);
            //$data = $data->whereRaw('lower(esn) like ?', ['%' . strtolower($request->esn). '%']);
        }

        if (!empty($request->sims)) {
            $sims = preg_split('/[\ \r\n\,]+/', $request->sims);
            $data = $data->whereIn('sim', $sims );
        }

        if (!empty($request->phones)) {
            //$data = $data->where('phone', 'like', '%' . $request->phone . '%');
            $phones = preg_split('/[\ \r\n\,]+/', $request->phones);
            $data = $data->whereIn('phone', $phones );
        }

        if (!empty($request->msid)) {
            $data = $data->where('msid', 'like', '%' . $request->msid . '%');
        }

        if (!empty($request->device_type)) {
            $data = $data->whereRaw('lower(device_type) like ?', ['%' . strtolower($request->device_type). '%']);
        }

        if (!empty($request->model)) {
            $data = $data->whereRaw('lower(model) like ?', ['%' . strtolower($request->model). '%']);
        }

        if (!empty($request->vendor)) {
            $data = $data->whereRaw('lower(vendor) like ?', ['%' . strtolower($request->vendor). '%']);
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

        if (!empty($request->product)) {
            $data = $data->where('product', $request->product);
        }

        if (!empty($request->sub_carrier)) {
            $data = $data->whereRaw('lower(sub_carrier) like ?', ['%' . strtolower($request->sub_carrier). '%']);
        }

        if (!empty($request->carrier)) {
            $result = Product::where('carrier', $request->carrier)->where('status', 'A')->select('id')->get();
            $prods = [];
            foreach ($result as $p) {
                array_push($prods, $p->id);
            }
            $condition = '"' . implode('", "', $prods) . '"';
            $data = $data->whereRaw("product in ($condition) ");
        }

        if (!empty($request->c_store_id)) {
            $data = $data->where('c_store_id', $request->c_store_id);
        }

        if (!empty($request->owner_id)) {
            $data = $data->where('owner_id', $request->owner_id);
        }

        if (!empty($request->esn_charge)) {
            $data = $data->where(DB::raw("ifnull(esn_charge, '')"), '!=', '');
        }

        if (!empty($request->esn_rebate)) {
            $data = $data->where(DB::raw("ifnull(esn_rebate, '')"), '!=', '');
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

        if (!empty($request->shipped_date)) {
            $data = $data->whereRaw("lower(trim(shipped_date)) like ?", ['%' . strtolower(trim($request->shipped_date)) . '%']);
        }

        if (!empty($request->is_byod)) {
            $data = $data->where("is_byod", 'Y');
        }

        if ($request->excel == 'Y') {
            $data = $data->orderBy('upload_date', 'desc')->get();
            Excel::create('esn', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'ESN' => $a->esn,
                            'Sim' => $a->sim,
                            'Phone' => $a->phone,
                            'Msl' => $a->msl,
                            'Msid' => $a->msid,
                            'MEID' => $a->meid,
                            'Model' => $a->model,
                            'Vendor' => $a->vendor,
                            'Product' => $a->product_name,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Amount' => $a->amount,
                            'Charge.Amount.R' => $a->charge_amount_r,
                            'Charge.Amount.D' => $a->charge_amount_d,
                            'Charge.Amount.M' => $a->charge_amount_m,
                            'ESN.Charge' => $a->esn_charge,
                            'ESN.Rebate' => $a->esn_rebate,
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
                            'R.NonBYOD.Spiff' => $a->nonbyod_spiff_r,
                            'D.NonBYOD.Spiff' => $a->nonbyod_spiff_d,
                            'M.NonBYOD.Spiff' => $a->nonbyod_spiff_m,
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
                            'Is.BYOD' => $a->is_byod,
                            'Upload.Date' => $a->upload_date,
                            'Download.Date' => date("m/d/Y h:i:s A")
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $data->orderBy('upload_date', 'desc')->paginate(20);

        $sub_carriers = DB::select("
            select distinct(s.sub_carrier) as sub_carrier from stock_esn s left join product p on p.id = s.product
            where p.activation ='Y' and p.status ='A' order by s.sub_carrier asc
        ");

        $carriers = Product::where('type', 'Wireless')->where('status', 'A')->where('activation', 'Y')->distinct()->get(['carrier']);
        $products = Product::where('activation', 'Y')->get();

        return view('admin.settings.esn', [
            'data' => $data,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'used_sdate' => $used_sdate ? $used_sdate->format('Y-m-d') : null,
            'used_edate' => $used_edate ? $used_edate->format('Y-m-d') : null,
            'esns' => $request->esns,
            'sims' => $request->sims,
            'phones' => $request->phones,
            'msid' => $request->msid,
            'device_type' => $request->device_type,
            'model' => $request->model,
            'vendor' => $request->vendor,
            'status' => $request->status,
            'is_byod' => $request->is_byod,
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
            'product' => $request->product,
            'sub_carrier' => $request->sub_carrier,
            'carrier' => $request->carrier,
            'products' => $products,
            'sub_carriers' => $sub_carriers,
            'carriers' => $carriers,
            'show_all_c_store' => $request->show_all_c_store,
            'show_all_owner' => $request->show_all_owner,
            'subsidy' => $request->subsidy,
            'supplier_make' => $request->supplier_make,
            'supplier_model' => $request->supplier_model,
            'supplier_cost' => $request->supplier_cost,
            'buyer_price' => $request->buyer_price,
            'shipped_date' => $request->shipped_date,
            'esn_charge' => $request->esn_charge,
            'esn_rebate' => $request->esn_rebate
        ]);
    }

    public function upload(Request $request) {

        ini_set('max_execution_time', 600);

        $line = '';


        DB::beginTransaction();

        try {

            $key = 'esn_csv_file';

            if (!Input::hasFile($key) || !Input::file($key)->isValid()) {
                $this->output_error('Please select ESN CSV file to upload');
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

                $esn = $row->esn;
                $sim = $row->sim;
                $phone = $row->phone;
                $msl = $row->msl;
                $msid = $row->msid;
                $meid = $row->meid;
                $type = $row->type;
                $status = $row->status;
                $rtr_month = $row->rtrmonth;
                $spiff_month  = $row->spiffmonth;
                $rebate_month = $row->rebatemonth;
                $charge_amount_r = $row->chargeamountr;
                $charge_amount_d = $row->chargeamountd;
                $charge_amount_m = $row->chargeamountm;
                $esn_charge = $row->esncharge;
                $esn_rebate = $row->esnrebate;
                $owner_id = $row->ownerid;
                $shipped_date = $row->shippeddate;
                $amt = $row->amount;
                $product = $request->product;
                $sub_carrier = $row->subcarrier;
                $spiff_override_r = $row->rspiffoverride;
                $spiff_override_d = $row->dspiffoverride;
                $spiff_override_m = $row->mspiffoverride;
                $rebate_override_r = $row->rrebateoverride;
                $rebate_override_d = $row->drebateoverride;
                $rebate_override_m = $row->mrebateoverride;
                $nonbyod_spiff_r = $row->rnonbyodspiff;
                $nonbyod_spiff_d = $row->dnonbyodspiff;
                $nonbyod_spiff_m = $row->mnonbyodspiff;
                $residual_r = $row->rresidual;
                $device_type = $row->devicetype;
                $model = $row->model;
                $vendor = $row->vendor;
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
                $is_byod = $row->isbyod;

                if (trim($esn) == '') {
                    $this->output_error('Empty ESN found at line : ' . $line_no);
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

                if ($type != 'R') {
                    $amt_array = explode("|", $amt);

                    $denoms = Denom::where('product_id', $product)
                        ->whereIn('denom', $amt_array)
                        ->get();

                    if (count($amt_array) != count($denoms)) {
                        $this->output_error($amt . ' is not in ' . $sub_carrier . ' denominations at line ' . $line_no);
                    }
                }

                $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();
                $esn_used_already = false;
                if (!empty($esn_obj)) {

                    ### ignore already used SIM
                    if (!empty($esn_obj->used_trans_id)) {
                        $esn_used_already = true;
                    }
                } else {
                    $esn_obj = new StockESN;
                }

                if (!$esn_used_already) {
                    $esn_obj->esn = $esn;
                    $esn_obj->sim = $sim;
                    $esn_obj->phone = $phone;
                    $esn_obj->msl = $msl;
                    $esn_obj->msid = $msid;
                    $esn_obj->meid = $meid;
                    $esn_obj->product = $product;
                    $esn_obj->sub_carrier = $sub_carrier;

                    $esn_obj->charge_amount_r = $charge_amount_r;
                    $esn_obj->charge_amount_d = $charge_amount_d;
                    $esn_obj->charge_amount_m = $charge_amount_m;
                    $esn_obj->esn_charge = $esn_charge;
                    $esn_obj->esn_rebate = $esn_rebate;
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

                    $esn_obj->nonbyod_spiff_r = trim($nonbyod_spiff_r) == '' ? null : $nonbyod_spiff_r;
                    $esn_obj->nonbyod_spiff_d = trim($nonbyod_spiff_d) == '' ? null : $nonbyod_spiff_d;
                    $esn_obj->nonbyod_spiff_m = trim($nonbyod_spiff_m) == '' ? null : $nonbyod_spiff_m;

                    $esn_obj->residual_r = trim($residual_r) == '' ? null : $residual_r;

                    $esn_obj->status = $status;
                    $esn_obj->device_type = $device_type;
                    $esn_obj->model = $model;
                    $esn_obj->vendor = $vendor;
                    $esn_obj->is_byod = $is_byod;
                } else {
                    $esn_obj->status = 'U';
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

            Helper::log('### ESN Upload Exception ###', [
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

                if ($request->type == 'E') {
                    $esn_objs = StockESN::where('esn', $esn)->get();
                } else {
                    $esn_objs = StockESN::where('MEID', $esn)->get();
                }

                if (!empty($esn_objs) && count($esn_objs) > 0) {

                    foreach ($esn_objs as $esn_obj) {
                        $o = new \stdClass();

                        $o->esn = $esn_obj->esn;
                        $o->sim = $esn_obj->sim;
                        $o->phone = $esn_obj->phone;
                        $o->msl = $esn_obj->msl;
                        $o->msid = $esn_obj->msid;
                        $o->meid = $esn_obj->meid;
                        $o->product = $esn_obj->product_name;
                        $o->sub_carrier = $esn_obj->sub_carrier;
                        $o->amount = $esn_obj->amount;
                        $o->charge_amount_r = $esn_obj->charge_amount_r;
                        $o->charge_amount_d = $esn_obj->charge_amount_d;
                        $o->charge_amount_m = $esn_obj->charge_amount_m;
                        $o->esn_charge = $esn_obj->esn_charge;
                        $o->esn_rebate = $esn_obj->esn_rebate;
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
                        $o->nonbyod_spiff_r = $esn_obj->nonbyod_spiff_r;
                        $o->nonbyod_spiff_d = $esn_obj->nonbyod_spiff_d;
                        $o->nonbyod_spiff_m = $esn_obj->nonbyod_spiff_m;
                        $o->residual_r = $esn_obj->residual_r;
                        $o->status = $esn_obj->status;
                        $o->device_type = $esn_obj->device_type;
                        $o->model = $esn_obj->model;
                        $o->vendor = $esn_obj->vendor;
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
                        $o->is_byod = $esn_obj->is_byod;
                        $o->upload_date = $esn_obj->upload_date;

                        $data_found[] = $o;
                    }
                } else {
                    $o = new \stdClass();

                    $o->esn     = $request->type == 'E' ? $esn : '';
                    $o->sim     = $request->sim;
                    $o->phone   = '';
                    $o->msl     = '';
                    $o->msid    = '';
                    $o->meid    = $request->type == 'M' ? $esn : '';
                    $o->product = 'Not Found';
                    $o->sub_carrier = '';
                    $o->amount  = '';
                    $o->charge_amount_r = '';
                    $o->charge_amount_d = '';
                    $o->charge_amount_m = '';
                    $o->esn_charge = '';
                    $o->esn_rebate = '';
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
                    $o->nonbyod_spiff_r = '';
                    $o->nonbyod_spiff_d = '';
                    $o->nonbyod_spiff_m = '';
                    $o->residual_r = '';
                    $o->status = '';
                    $o->device_type = '';
                    $o->model = '';
                    $o->vendor = '';
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
                    $o->is_byod = '';
                    $o->upload_date = '';

                    $data_not_found[] = $o;
                }
            }

            $data = array_merge($data_found, $data_not_found);
            Excel::create('batch_lookup_esns', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'ESN' => $a->esn,
                            'SIM' => $a->sim,
                            'Phone' => $a->phone,
                            'Msl' => $a->msl,
                            'Msid' => $a->msid,
                            'MEID' => $a->meid,
                            'Product' => $a->product,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Amount' => $a->amount,
                            'Charge.Amount.R' => $a->charge_amount_r,
                            'Charge.Amount.D' => $a->charge_amount_d,
                            'Charge.Amount.M' => $a->charge_amount_m,
                            'ESN.Recharge' => $a->esn_charge,
                            'ESN.Rebate' => $a->esn_rebate,
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
                            'R.NonBYOD.Spiff' => $a->nonbyod_spiff_r,
                            'D.NonBYOD.Spiff' => $a->nonbyod_spiff_d,
                            'M.NonBYOD.Spiff' => $a->nonbyod_spiff_m,
                            'R.Residual' => $a->residual_r,
                            'Status' => $a->status,
                            'Device.Type' => $a->device_type,
                            'Model' => $a->model,
                            'Vendor' => $a->vendor,
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
                            'Is.Byod' => $a->is_byod,
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

    public function assign(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'c_store_id' => 'required_if:clear,N',
                'esns' => 'required',
                'product' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $c_store_id = null;
            if ($request->clear == 'N') {
                $account = Account::find($request->c_store_id);
                if (empty($account)) {
                    return response()->json([
                        'msg' => 'Account does not exists.'
                    ]);
                }

                if ($account->c_store != 'Y') {
                    return response()->json([
                        'msg' => 'The account is not marked as C-Store'
                    ]);
                }

                $c_store_id = $account->id;
            }

            $esns = trim($request->esns);
            if (empty($esns)) {
                return response()->json([
                    'msg' => 'Please enter ESNs'
                ]);
            }

            DB::beginTransaction();

            $esn_array = explode(PHP_EOL, $esns);
            $line_no = 1;
            foreach ($esn_array as $esn) {
                $esn_obj = StockESN::where('esn', $esn)->where('product', $request->product)->first();
                if (empty($esn_obj)) {
                    throw new \Exception('ESN : ' . $esn . ' not in our DB at line : ' . $line_no);
                }

                $esn_obj->c_store_id = $c_store_id;
                $esn_obj->save();

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

    public function bulk_update(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'esns' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }
                return response()->json([
                    'msg' => $msg
                ]);
            }

            $esns = trim($request->esns);
            if (empty($esns)) {
                return response()->json([
                    'msg' => 'Please enter ESNs'
                ]);
            }

            DB::beginTransaction();

            $esn_array = explode(PHP_EOL, $esns);
            $line_no = 1;
            $esns = array();
            foreach ($esn_array as $esn) {

                $cur_esn = StockESN::where('esn', $esn)
                    ->where('sub_carrier', $request->esn_sub_carrier)
                    ->where('status', 'A')
                    ->first();

                if(empty($cur_esn)){
                    throw new \Exception('ESN : ' . $esn . ' at line : ' . $line_no . ' is not in our DB or using already');
                }

                $esn_objs = StockESN::where('esn', $esn)
                    ->where('sub_carrier', $request->esn_sub_carrier)
                    ->where('status', 'A')
                    ->get();

                foreach ($esn_objs as $esn_obj) {

                    if (empty($esn_obj)) {
                        throw new \Exception('ESN : ' . $esn . ' at line : ' . $line_no . ' is not in our DB or using already');
                    }

                    $esns[] = $esn;

                    if (isset($request->amount)) {
                        $esn_obj->amount = $request->amount;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->amount = null;
                    }
                    if (isset($request->type)) {
                        $esn_obj->type = strtoupper($request->type);
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->type = 'R';
                    }
                    if (isset($request->charge_amount_r)) {
                        $esn_obj->charge_amount_r = $request->charge_amount_r;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->charge_amount_r = null;
                    }
                    if (isset($request->charge_amount_d)) {
                        $esn_obj->charge_amount_d = $request->charge_amount_d;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->charge_amount_d = null;
                    }
                    if (isset($request->charge_amount_m)) {
                        $esn_obj->charge_amount_m = $request->charge_amount_m;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->charge_amount_m = null;
                    }
                    if (isset($request->rtr_month)) {
                        $esn_obj->rtr_month = $request->rtr_month;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->rtr_month = null;
                    }
                    if (isset($request->rebate_month)) {
                        $esn_obj->rebate_month = $request->rebate_month;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->rebate_month = null;
                    }
                    if (isset($request->rebate_override_r)) {
                        $esn_obj->rebate_override_r = $request->rebate_override_r;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->rebate_override_r = null;
                    }
                    if (isset($request->rebate_override_d)) {
                        $esn_obj->rebate_override_d = $request->rebate_override_d;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->rebate_override_d = null;
                    }
                    if (isset($request->rebate_override_m)) {
                        $esn_obj->rebate_override_m = $request->rebate_override_m;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->rebate_override_m = null;
                    }
                    if (isset($request->buyer_name)) {
                        $esn_obj->buyer_name = $request->buyer_name;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->buyer_name = null;
                    }
                    if (isset($request->buyer_price)) {
                        $esn_obj->buyer_price = $request->buyer_price;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->buyer_price = null;
                    }
                    if (isset($request->buyer_date)) {
                        $esn_obj->buyer_date = $request->buyer_date;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->buyer_date = null;
                    }
                    if (isset($request->buyer_memo)) {
                        $esn_obj->buyer_memo = $request->buyer_memo;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->buyer_memo = null;
                    }
                    if (isset($request->supplier_memo)) {
                        $esn_obj->supplier_memo = $request->supplier_memo;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->supplier_memo = null;
                    }
                    if (isset($request->comments)) {
                        $esn_obj->comments = $request->comments;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->comments = null;
                    }
                    if (isset($request->esn_charge)) {
                        $esn_obj->esn_charge = $request->esn_charge;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->esn_charge = null;
                    }
                    if (isset($request->esn_rebate)) {
                        $esn_obj->esn_rebate = $request->esn_rebate;
                    } elseif ($request->reset == 'Y') {
                        $esn_obj->esn_rebate = null;
                    }

                    $esn_obj->upload_date = Carbon::now();

                    $esn_obj->save();

                    $line_no++;
                }
            }

            DB::commit();

            // Send email to Tom & Buyer When reset = 'N'
            if($request->reset == 'N') {

                if (getenv('APP_ENV') == 'production') {
                    $email = ['ops@softpayplus.com'];
                } else {
                    $email = ['ops@softpayplus.com'];
                }

                $request->esns = $esns;

                $product_obj = Product::where('id', $esn_obj->product)->first();
                $request->carrier = $product_obj->carrier;

                if (!empty($request->buyer_email)) {
                    Mail::to($request->buyer_email)
                        ->bcc($email)
                        ->send(new EsnOrder($request));
                } else {
                    Mail::to($email)
                        ->send(new EsnOrder($request));
                }
            }

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