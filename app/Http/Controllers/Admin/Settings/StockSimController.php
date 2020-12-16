<?php
/**
 * Created by Royce
 * Date: 6/21/18
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\SimValueBinder;
use App\Mail\SimOrder;
use App\Model\Account;
use App\Model\Product;
use App\Model\Denom;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\StockSim;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Mail;

class StockSimController extends Controller
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

//        $data = StockSim::whereRaw('1=1');
        $data = StockSim::leftJoin('product' ,'stock_sim.product', '=', 'product.id')->whereRaw('1=1');

        if (!empty($sdate)) {
            $data = $data->whereRaw('stock_sim.upload_date >= ?', [$sdate]);
        }

        if (!empty($edate)) {
            $data = $data->whereRaw('stock_sim.upload_date <= ?', [$edate]);
        }

        if (!empty($used_sdate)) {
            $data = $data->whereRaw('used_date >= ?', [$used_sdate]);
        }

        if (!empty($used_edate)) {
            $data = $data->whereRaw('used_date <= ?', [$used_edate]);
        }

//        if (!empty($request->sim)) {
//            $data = $data->where('stock_sim.sim_serial', 'like', '%' . $request->sim . '%');
//        }

        if (!empty($request->sims)) {
            $sims = preg_split('/[\ \r\n\,]+/', $request->sims);
            $data = $data->whereIn('stock_sim.sim_serial', $sims);
        }

//        if (!empty($request->phone)) {
//            $data = $data->where('stock_sim.phone', 'like', '%' . $request->phone . '%');
//        }

        if (!empty($request->phones)) {
            $phones = preg_split('/[\ \r\n\,]+/', $request->phones);
            $data = $data->whereIn('stock_sim.phone', $phones);
        }

//        if (!empty($request->esn)) {
//            $data = $data->whereRaw('lower(stock_sim.esn) like ?', ['%' . strtolower($request->esn). '%']);
//        }

        if (!empty($request->esns)) {
            $esns = preg_split('/[\ \r\n\,]+/', $request->esns);
            $esns = array_map('strtolower', $esns);
            $data = $data->whereIn( DB::raw("lower(stock_sim.esn)") , $esns);
            //$data = $data->whereRaw('lower(esn) like ?', ['%' . strtolower($request->esn). '%']);
        }


        if (!empty($request->device_type)) {
            $data = $data->whereRaw('lower(stock_sim.device_type) like ?', ['%' . strtolower($request->device_type). '%']);
        }

        if (!empty($request->model)) {
            $data = $data->whereRaw('lower(stock_sim.model) like ?', ['%' . strtolower($request->model). '%']);
        }

        if (!empty($request->vendor)) {
            $data = $data->whereRaw('lower(stock_sim.vendor) like ?', ['%' . strtolower($request->vendor). '%']);
        }

        if (!empty($request->status)) {
            $data = $data->where('stock_sim.status', $request->status);
        }

        if (!empty($request->type)) {
            $data = $data->where('stock_sim.type', $request->type);
        }

        if (!empty($request->rtr_month)) {
            $data = $data->where('stock_sim.rtr_month', $request->rtr_month);
        }

        if (!empty($request->supplier)) {
            $data = $data->whereRaw('lower(stock_sim.supplier) like ?', ['%' . strtolower($request->supplier). '%']);
        }

        if (!empty($request->supplier_date)) {
            $data = $data->whereRaw('lower(stock_sim.supplier_date) like ?', ['%' . strtolower($request->supplier_date). '%']);
        }

        if (!empty($request->supplier_memo)) {
            $data = $data->whereRaw('lower(stock_sim.supplier_memo) like ?', ['%' . strtolower($request->supplier_memo). '%']);
        }

        if (!empty($request->buyer_name)) {
            $data = $data->whereRaw('lower(stock_sim.buyer_name) like ?', ['%' . strtolower($request->buyer_name). '%']);
        }

        if (!empty($request->buyer_date)) {
            $data = $data->whereRaw('lower(stock_sim.buyer_date) like ?', ['%' . strtolower($request->buyer_date). '%']);
        }

        if (!empty($request->buyer_memo)) {
            $data = $data->whereRaw('lower(stock_sim.buyer_memo) like ?', ['%' . strtolower($request->buyer_memo). '%']);
        }

        if (!empty($request->comments)) {
            $data = $data->whereRaw('lower(stock_sim.comments) like ?', ['%' . strtolower($request->comments). '%']);
        }

        if (!empty($request->c_store_id)) {
            $data = $data->where('stock_sim.c_store_id',  $request->c_store_id);
        }

        if (!empty($request->owner_id)) {
            $data = $data->where('stock_sim.owner_id', $request->owner_id);
        }

        if (!empty($request->product)) {
            $data = $data->where('stock_sim.product', $request->product);
        }

        if (!empty($request->sim_charge)) {
            $data = $data->where(DB::raw("ifnull(stock_sim.sim_charge, '')"), '!=', '');
        }

        if (!empty($request->sim_rebate)) {
            $data = $data->where(DB::raw("ifnull(stock_sim.sim_rebate, '')"), '!=', '');
        }

        if (!empty($request->sub_carrier)) {
            $data = $data->whereRaw('lower(stock_sim.sub_carrier) like ?', ['%' . strtolower($request->sub_carrier). '%']);
        }

        if (!empty($request->carrier)) {
            $result = Product::where('carrier', $request->carrier)->where('status', 'A')->select('id')->get();
            $prods = [];
            foreach ($result as $p) {
                array_push($prods, $p->id);
            }
            $condition = '"' . implode('", "', $prods) . '"';
            $data = $data->whereRaw("stock_sim.product in ($condition) ");
        }

        if (!empty($request->show_all_c_store)) {
            $data = $data->where(DB::raw("ifnull(stock_sim.c_store_id, '')"), '!=', '');
        }

        if (!empty($request->show_all_owner)) {
            $data = $data->where(DB::raw("ifnull(stock_sim.owner_id, '')"), '!=', '');
        }

        if (!empty($request->supplier_cost)) {
            $data = $data->where(DB::raw("lower(stock_sim.supplier_cost)"), 'like', '%' . strtolower($request->supplier_cost) . '%');
        }

        if (!empty($request->buyer_price)) {
            $data = $data->where(DB::raw("lower(stock_sim.buyer_price)"), 'like', '%' . strtolower($request->buyer_price) . '%');
        }

        if (!empty($request->shipped_date)) {
            $data = $data->whereRaw("lower(trim(stock_sim.shipped_date)) like ?", ['%' . strtolower(trim($request->shipped_date))]);
        }

        if (!empty($request->is_byos)) {
            $data = $data->where("stock_sim.is_byos", 'Y');
        }

        if ($request->excel == 'Y') {

            ini_set('memory_limit', '2048M');
            ini_set('max_execution_time', 600);

            $data = $data->orderBy('stock_sim.upload_date', 'desc')
                    ->select(
                    'stock_sim.sim_serial',
                    'stock_sim.phone',
                    'stock_sim.afcode',
                    'stock_sim.esn',
                    'stock_sim.device_type',
                    'stock_sim.model',
                    'stock_sim.vendor',
                    'product.name',
                    'stock_sim.sub_carrier',
                    'stock_sim.sim_charge',
                    'stock_sim.sim_rebate',
                    'stock_sim.amount',
                    'stock_sim.charge_amount_r',
                    'stock_sim.charge_amount_d',
                    'stock_sim.charge_amount_m',
                    'stock_sim.owner_id',
                    'stock_sim.shipped_date',
                    'stock_sim.type',
                    'stock_sim.is_byos',
                    'stock_sim.rtr_month',
                    'stock_sim.spiff_month',
                    'stock_sim.spiff_override_r',
                    'stock_sim.spiff_override_d',
                    'stock_sim.spiff_override_m',
                    'stock_sim.nonbyos_spiff_r',
                    'stock_sim.nonbyos_spiff_d',
                    'stock_sim.nonbyos_spiff_m',
                    'stock_sim.special_spiff_ids',
                    'stock_sim.residual_r',
                    'stock_sim.cb_override_r',
                    'stock_sim.cb_override_d',
                    'stock_sim.cb_override_m',
                    'stock_sim.spiff_2_r',
                    'stock_sim.spiff_2_d',
                    'stock_sim.spiff_2_m',
                    'stock_sim.status',
                    'stock_sim.supplier',
                    'stock_sim.supplier_cost',
                    'stock_sim.supplier_date',
                    'stock_sim.supplier_memo',
                    'stock_sim.buyer_name',
                    'stock_sim.buyer_price',
                    'stock_sim.buyer_date',
                    'stock_sim.buyer_memo',
                    'stock_sim.comments',
                    'stock_sim.c_store_id',
                    'stock_sim.special_spiff',
                    'stock_sim.hide_plan_amount',
                    'stock_sim.plan_description',
                    'stock_sim.used_trans_id',
                    'stock_sim.is_byos',
                    'stock_sim.used_date',
                    'stock_sim.upload_date'
                )->get();


//            Helper::log('### export to log ###', [
//                'array_count' => count($data)
//            ]);

            Excel::create('sims', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {
                    //$inx = 0;
                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'SIM #' => $a->sim_serial,
                            'Phone' => $a->phone,
                            'ESN'   => $a->esn,
                            'Act.Code' => $a->afcode,
                            'Device.Type' => $a->device_type,
                            'Model' => $a->model,
                            'Vendor' => $a->vendor,
                            'Product' => $a->name,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Amount' => $a->amount,
                            'Charge.Amount.R' => $a->charge_amount_r,
                            'Charge.Amount.D' => $a->charge_amount_d,
                            'Charge.Amount.M' => $a->charge_amount_m,
                            'SIM.Charge' => $a->sim_charge,
                            'SIM.Rebate' => $a->sim_rebate,
                            'Owner.ID' => $a->owner_id,
                            'Shipped.Date' => $a->shipped_date,
                            'Type' => $a->type,
                            'RTR.Month' => $a->rtr_month,
                            'Spiff.Month' => $a->spiff_month,
                            'R.Spiff.Override' => $a->spiff_override_r,
                            'D.Spiff.Override' => $a->spiff_override_d,
                            'M.Spiff.Override' => $a->spiff_override_r,
                            'R.NonBYOS.Spiff' => $a->nonbyos_spiff_r,
                            'D.NonBYOS.Spiff' => $a->nonbyos_spiff_d,
                            'M.NonBYOS.Spiff' => $a->nonbyos_spiff_m,
                            'Special.Spiff.IDs' => $a->special_spiff_ids,
                            'R.Residual' => $a->residual_r,
                            'R.Spiff 2' => $a->spiff_2_r,
                            'D.Spiff 2' => $a->spiff_2_d,
                            'M.Spiff 2' => $a->spiff_2_m,
                            'Status' => $a->status,
                            'Supplier' => $a->supplier,
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
                            'Special.Spiff' => $a->special_spiff,
                            'Hide.Plan.Amount' => $a->hide_plan_amount,
                            'Plan.Description' => $a->plan_description,
                            'Is.Byos' => $a->is_byos,
                            'Download.Date' => date("m/d/Y h:i:s A")

                        ];

//                        $inx++;
//                        if( $inx%1000 == 0){
//                            Helper::log('### export to log ###', [
//                                'inx' => $inx
//                            ]);
//                        }

                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $data->orderBy('stock_sim.upload_date', 'desc')
            ->select(
                'stock_sim.sim_serial',
                'stock_sim.phone',
                'stock_sim.esn',
                'stock_sim.device_type',
                'stock_sim.model',
                'stock_sim.vendor',
                'stock_sim.afcode',
                'product.name',
                'stock_sim.sub_carrier',
                'stock_sim.sim_charge',
                'stock_sim.sim_rebate',
                'stock_sim.amount',
                'stock_sim.charge_amount_r',
                'stock_sim.charge_amount_d',
                'stock_sim.charge_amount_m',
                'stock_sim.owner_id',
                'stock_sim.shipped_date',
                'stock_sim.type',
                'stock_sim.is_byos',
                'stock_sim.rtr_month',
                'stock_sim.spiff_month',
                'stock_sim.spiff_override_r',
                'stock_sim.spiff_override_d',
                'stock_sim.spiff_override_m',
                'stock_sim.status',
                'stock_sim.supplier',
                'stock_sim.supplier_cost',
                'stock_sim.supplier_date',
                'stock_sim.supplier_memo',
                'stock_sim.buyer_name',
                'stock_sim.buyer_price',
                'stock_sim.buyer_date',
                'stock_sim.buyer_memo',
                'stock_sim.comments',
                'stock_sim.c_store_id',
                'stock_sim.used_trans_id',
                'stock_sim.used_date',
                'stock_sim.upload_date'
            )
            ->paginate(100);
        $sub_carriers = StockSim::select('sub_carrier')->whereRaw("sub_carrier <> ''")->groupBy('sub_carrier')->orderBy('sub_carrier')->get();
        $carriers = Product::where('type', 'Wireless')->where('status', 'A')->where('activation', 'Y')->distinct()->get(['carrier']);
        $products = Product::where('activation', 'Y')->get();
        $sim_groups = StockSim::select('sim_group')->whereRaw('ifnull(sim_group, "") != "" ')->distinct()->orderBy('sim_group', 'asc')->get();

        return view('admin.settings.sim', [
            'data' => $data,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'used_sdate' => $used_sdate ? $used_sdate->format('Y-m-d') : null,
            'used_edate' => $used_edate ? $used_edate->format('Y-m-d') : null,
            'sims' => $request->sims,
            'phones' => $request->phones,
            'esns' => $request->esns,
            'device_type' => $request->device_type,
            'model' => $request->model,
            'vendor' => $request->vendor,
            'afcode' => $request->afcode,
            'status' => $request->status,
            'type' => $request->type,
            'is_byos' => $request->is_byos,
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
            'products'  => $products,
            'sub_carriers' => $sub_carriers,
            'carriers' => $carriers,
            'sim_groups' => $sim_groups,
            'show_all_c_store' => $request->show_all_c_store,
            'show_all_owner' => $request->show_all_owner,
            'supplier_cost' => $request->supplier_cost,
            'buyer_price' => $request->buyer_price,
            'shipped_date' => $request->shipped_date,
            'sim_charge'    => $request->sim_charge,
            'sim_rebate'   => $request->sim_rebate
        ]);
    }

    public function batchLookup(Request $request) {
        try {

            $sims = trim($request->batch_sims);
            if (empty($sims)) {
                $this->output_error('Please enter SIMs to lookup');
            }

            $sim_array = explode(PHP_EOL, $sims);

            $data_found = [];
            $data_not_found = [];

            foreach ($sim_array as $sim) {
                $sim = trim($sim);

                $sim_objs = StockSim::leftJoin('product' ,'stock_sim.product', '=', 'product.id')
                            ->where('stock_sim.sim_serial', $sim)
                            ->select(
                        'stock_sim.sim_serial',
                        'stock_sim.phone',
                        'stock_sim.afcode',
                        'stock_sim.esn',
                        'stock_sim.device_type',
                        'stock_sim.model',
                        'stock_sim.vendor',
                        'product.name',
                        'stock_sim.sub_carrier',
                        'stock_sim.sim_charge',
                        'stock_sim.sim_rebate',
                        'stock_sim.amount',
                        'stock_sim.charge_amount_r',
                        'stock_sim.charge_amount_d',
                        'stock_sim.charge_amount_m',
                        'stock_sim.owner_id',
                        'stock_sim.shipped_date',
                        'stock_sim.type',
                        'stock_sim.rtr_month',
                        'stock_sim.spiff_month',
                        'stock_sim.spiff_override_r',
                        'stock_sim.spiff_override_d',
                        'stock_sim.spiff_override_m',
                        'stock_sim.status',
                        'stock_sim.supplier',
                        'stock_sim.supplier_cost',
                        'stock_sim.supplier_date',
                        'stock_sim.supplier_memo',
                        'stock_sim.buyer_name',
                        'stock_sim.buyer_price',
                        'stock_sim.buyer_date',
                        'stock_sim.buyer_memo',
                        'stock_sim.comments',
                        'stock_sim.c_store_id',
                        'stock_sim.used_trans_id',
                        'stock_sim.used_date',
                        'stock_sim.upload_date')
                            ->get();

                if (!empty($sim_objs) && count($sim_objs) > 0) {
                    foreach ($sim_objs as $sim_obj) {
                        $o = new \stdClass();
                        $o->sim_serial = $sim;
                        $o->phone = $sim_obj->phone;
                        $o->esn = $sim_obj->esn;
                        $o->afcode = $sim_obj->afcode;
                        $o->device_type = $sim_obj->device_type;
                        $o->model = $sim_obj->model;
                        $o->vendor = $sim_obj->vendor;
                        $o->product = $sim_obj->name;
                        $o->sub_carrier = $sim_obj->sub_carrier;
                        $o->amount = $sim_obj->amount;
                        $o->charge_amount_r = $sim_obj->charge_amount_r;
                        $o->charge_amount_d = $sim_obj->charge_amount_d;
                        $o->charge_amount_m = $sim_obj->charge_amount_m;
                        $o->sim_charge = $sim_obj->sim_charge;
                        $o->sim_rebate = $sim_obj->sim_rebate;
                        $o->owner_id = $sim_obj->owner_id;
                        $o->shipped_date = $sim_obj->shipped_date;
                        $o->type = $sim_obj->type;
                        $o->rtr_month = $sim_obj->rtr_month;
                        $o->spiff_month = $sim_obj->spiff_month;
                        $o->spiff_override_r = $sim_obj->spiff_override_r;
                        $o->spiff_override_d = $sim_obj->spiff_override_d;
                        $o->spiff_override_m = $sim_obj->spiff_override_m;
                        $o->nonbyos_spiff_r = $sim_obj->nonbyos_spiff_r;
                        $o->nonbyos_spiff_d = $sim_obj->nonbyos_spiff_d;
                        $o->nonbyos_spiff_m = $sim_obj->nonbyos_spiff_m;
                        $o->special_spiff_ids = $sim_obj->special_spiff_ids;
                        $o->residual_r = $sim_obj->residual_r;
                        $o->spiff_2_r = $sim_obj->spiff_2_r;
                        $o->spiff_2_d = $sim_obj->spiff_2_d;
                        $o->spiff_2_m = $sim_obj->spiff_2_m;
                        $o->status = $sim_obj->status;
                        $o->supplier = $sim_obj->supplier;
                        $o->supplier_cost = $sim_obj->supplier_cost;
                        $o->supplier_date = $sim_obj->supplier_date;
                        $o->supplier_memo = $sim_obj->supplier_memo;
                        $o->buyer_name = $sim_obj->buyer_name;
                        $o->buyer_price = $sim_obj->buyer_price;
                        $o->buyer_date = $sim_obj->buyer_date;
                        $o->buyer_memo = $sim_obj->buyer_memo;
                        $o->comments = $sim_obj->comments;
                        $o->c_store_id = $sim_obj->c_store_id;
                        $o->used_trans_id = $sim_obj->used_trans_id;
                        $o->used_date = $sim_obj->used_date;
                        $o->upload_date = $sim_obj->upload_date;
                        $o->special_spiff = $sim_obj->special_spiff;
                        $o->hide_plan_amount = $sim_obj->hide_plan_amount;
                        $o->plan_description = $sim_obj->plan_description;
                        $o->is_byos = $sim_obj->is_byos;

                        $data_found[] = $o;
                    }
                } else {
                    $o = new \stdClass();
                    $o->sim_serial = $sim;
                    $o->phone = '';
                    $o->esn = '';
                    $o->afcode = 'Not Found';
                    $o->device_type = '';
                    $o->model = '';
                    $o->vendor = '';
                    $o->product = '';
                    $o->sub_carrier = '';
                    $o->amount = '';
                    $o->charge_amount_r = '';
                    $o->charge_amount_d = '';
                    $o->charge_amount_m = '';
                    $o->sim_charge = '';
                    $o->sim_rebate = '';
                    $o->owner_id = '';
                    $o->shipped_date = '';
                    $o->type = '';
                    $o->rtr_month = '';
                    $o->spiff_month = '';
                    $o->spiff_override_r = '';
                    $o->spiff_override_d = '';
                    $o->spiff_override_m = '';
                    $o->nonbyos_spiff_r = '';
                    $o->nonbyos_spiff_d = '';
                    $o->nonbyos_spiff_m = '';
                    $o->special_spiff_ids = '';
                    $o->residual_r = '';
                    $o->spiff_2_r = '';
                    $o->spiff_2_d = '';
                    $o->spiff_2_m = '';
                    $o->status = '';
                    $o->supplier = '';
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
                    $o->special_spiff = '';
                    $o->hide_plan_amount = '';
                    $o->plan_description = '';
                    $o->is_byos = '';

                    $data_not_found[] = $o;
                }

            }

            $data = array_merge($data_found, $data_not_found);

            Excel::create('batch_lookup_sims', function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'SIM #' => $a->sim_serial,
                            'Phone' => $a->phone,
                            'ESN' => $a->esn,
                            'Act.Code' => $a->afcode,
                            'Device.Type' => $a->device_type,
                            'Model' => $a->model,
                            'Vendor' => $a->vendor,
                            'Product' => $a->product,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Amount' => $a->amount,
                            'Charge.Amount.R' => $a->charge_amount_r,
                            'Charge.Amount.D' => $a->charge_amount_d,
                            'Charge.Amount.M' => $a->charge_amount_m,
                            'SIM.Charge' => $a->sim_charge,
                            'SIM.Rebate' => $a->sim_rebate,
                            'Owner.ID' => $a->owner_id,
                            'Shipped.Date' => $a->shipped_date,
                            'Type' => $a->type,
                            'RTR.Month' => $a->rtr_month,
                            'Spiff.Month' => $a->spiff_month,
                            'R.Spiff.Override' => $a->spiff_override_r,
                            'D.Spiff.Override' => $a->spiff_override_d,
                            'M.Spiff.Override' => $a->spiff_override_m,
                            'R.NonBYOS.Spiff' => $a->nonbyos_spiff_r,
                            'D.NonBYOS.Spiff' => $a->nonbyos_spiff_d,
                            'M.NonBYOS.Spiff' => $a->nonbyos_spiff_m,
                            'Special.Spiff.IDs' => $a->special_spiff_ids,
                            'R.Residual' => $a->residual_r,
                            'R.Spiff.2' => $a->spiff_2_r,
                            'D.Spiff.2' => $a->spiff_2_d,
                            'M.Spiff.2' => $a->spiff_2_m,
                            'Status' => $a->status,
                            'Supplier' => $a->supplier,
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
                            'Special.Spiff' => $a->special_spiff,
                            'Hide.Plan.Amount' => $a->hide_plan_amount,
                            'Plan.Description' => $a->plan_description,
                            'Is.Byos'   => $a->is_byos,
                            'Download.Date' => date("m/d/Y h:i:s A")
                        ];

                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');

        } catch (\Exception $ex) {
            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString());
        }
    }

    public function upload(Request $request) {

        Helper::log('### SIM Upload Exception ###', [
            'res' => $request->all()
        ]);

        ini_set('max_execution_time', 600);

        DB::beginTransaction();

        $line = '';


        try {

            $key = 'sim_csv_file';

            if (!Input::hasFile($key) || !Input::file($key)->isValid()) {
                $this->output_error('Please select SIM CSV file to upload');
            }

            if (empty($request->product)) {
                $this->output_error('Please select product');
            }

            $path = Input::file($key)->getRealPath();

            $binder = new SimValueBinder();
            $results = Excel::setValueBinder($binder)->load($path)->setSeparator('_')->get();
            //$results = $reader->setSeparator('_')->get();

            //dd($results);

            $line_no = 0;

            foreach ($results as $row) {

                $line_no++ ;

                $sim_serial = trim($row->sim);
                $phone = trim($row->phone);
                $esn = trim($row->esn);
                $afcode = trim($row->actcode);
                $device_type = trim($row->devicetype);
                $model = trim($row->model);
                $vendor = trim($row->vendor);
                $type = trim($row->type);
                $status = trim($row->status);
                $rtr_month = trim($row->rtrmonth);
                $spiff_month = trim($row->spiffmonth);
                $spiff_override_r = $row->rspiffoverride;
                $spiff_override_d = $row->dspiffoverride;
                $spiff_override_m = $row->mspiffoverride;
                $sim_charge = $row->simcharge;
                $sim_rebate = $row->simrebate;
                $nonbyos_spiff_r = $row->rnonbyosspiff;
                $nonbyos_spiff_d = $row->dnonbyosspiff;
                $nonbyos_spiff_m = $row->mnonbyosspiff;
                $special_spiff_ids = $row->specialspiffids;
                $residual_r = $row->rresidual;
                $spiff_2_r = $row->rspiff2;
                $spiff_2_d = $row->dspiff2;
                $spiff_2_m = $row->mspiff2;
                $charge_amount_r = $row->chargeamountr;
                $charge_amount_d = $row->chargeamountd;
                $charge_amount_m = $row->chargeamountm;
                $owner_id = $row->ownerid;
                $shipped_date = $row->shippeddate;
                $amt = trim($row->amount);
                $product = trim($request->product);
                $sub_carrier = trim($row->subcarrier);
                $supplier = trim($row->supplier);
                $supplier_cost = trim($row->suppliercost);
                $supplier_date = trim($row->supplierdate);
                $supplier_memo = trim($row->suppliermemo);
                $buyer_name = trim($row->buyername);
                $buyer_price = trim($row->buyerprice);
                $buyer_date = trim($row->buyerdate);
                $buyer_memo = trim($row->buyermemo);
                $comments = trim($row->comments);
                $special_spiff = $row->specialspiff;
                $hide_plan_amount = $row->hideplanamount;
                $plan_description = $row->plandescription;
                $is_byos = $row->isbyos;

                if (trim($sim_serial) == '') {
                    $this->output_error('Empty SIM found on line at line : ' . $line_no);
                }

                $sim_serial = trim($sim_serial);

                if (empty(trim($type))) {
                    $type = 'R';
                }

                if (!in_array($type, ['P', 'B', 'R', 'C'])) {
                    $this->output_error('Invalid type value found at line : ' . $line_no);
                }

                if ($type == 'C') {
                    if (empty($charge_amount_r) && empty($charge_amount_d) && empty($charge_amount_m)) {
                        $this->output_error('Charge amount is needed for consignment SIM : ' . $line_no);
                    }

                    if (empty($owner_id)) {
                        $this->output_error('Owner ID is needed for consignment SIM : ' . $line_no);
                    }
                }

                if (empty(trim($status))) {
                    $status = 'A';
                }

                if (!in_array($status, ['A', 'H', 'S', 'U'])) {
                    $this->output_error('Invalid status value found at line : ' . $line_no);
                }

                /*if ($type == 'R' && !empty($rtr_month) && $rtr_month != '1') {
                    $this->output_error('Regular SIM only supports 1 RTR.Month at line : ' . $line_no);
                }*/

                if (!empty($rtr_month)) {
                    $split = explode("|", $rtr_month);

                    foreach ($split as $spl) {
                        if (!in_array($spl, ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'])) {
                            $this->output_error('Invalid RTR.Month value found. Only 1~12 allowed at line : ' . $line_no);
                        }
                    }
                }

                if (!in_array($spiff_month, ['0','1','2','3','1|2','1|3','2|3','1|2|3'])) {
                    $this->output_error('Invalid Spiff.Month value found. Only 0, 1, 2, 3, 1|2, 1|3, 2|3, 1|2|3 allowed at line: ' . $line_no);
                }

                if ($type == 'P') {
                    $amt_array = explode("|", $amt);

                    $denoms = Denom::where('product_id', $request->product)
                        ->whereIn('denom', $amt_array)
                        ->get();

                    if (count($denoms) < 1) {
                        $this->output_error($amt . ' is not in ' . $sub_carrier . ' denominations at line ' . $line_no);
                    }
                }

                $product_obj = Product::where('id', $product)->first();
                $sim_group = $product_obj->sim_group;

//                switch ($product) {
//                    case 'WATTA':
//                        $sim_group = 'ATT';
//                        break;
//                    case 'WATTPVA':
//                        $sim_group = 'ATT';
//                        break;
//                    case 'WATTDO':
//                        $sim_group = 'ATT';
//                        break;
//                    case 'WFRUPA':
//                        $sim_group = 'FreeUp';
//                        break;
//                    case 'WGENA':
//                        $sim_group = 'Gen';
//                        break;
//                    case 'WGENOA':
//                        $sim_group = 'Gen';
//                        break;
//                    case 'WH2OM':
//                        $sim_group = 'H2O';
//                        break;
//                    case 'WH2OP':
//                        $sim_group = 'H2O';
//                        break;
//                    case 'WH2OB':
//                        $sim_group = 'Bolt';
//                        break;
//                    case 'WEZM':
//                        $sim_group = 'EasyGo';
//                        break;
//                    case 'WEZP':
//                        $sim_group = 'EasyGo';
//                        break;
//                    case 'WLYCA':
//                        $sim_group = 'Lyca';
//                        break;
//                    case 'WVZB':
//                        $sim_group = 'Verizon';
//                        break;
//                    case 'WVZS':
//                        $sim_group = 'Verizon';
//                        break;
//                }

                $sim = StockSim::where('sim_serial', $sim_serial)->where('product', $product)->first();
                $sim_used_already = false;
                if (!empty($sim)) {

                    ### ignore already used SIM
//                    if (!empty($sim->used_trans_id)) {
//                        $sim_used_already = true;
//                    }
                } else {
                    $sim = new StockSim;
                }

                if (!$sim_used_already) {
                    $sim->sim_serial = $sim_serial;
                    $sim->phone = $phone;
                    $sim->esn = $esn;
                    $sim->afcode = $afcode;
                    $sim->device_type = $device_type;
                    $sim->model = $model;
                    $sim->vendor = $vendor;
                    $sim->product = $product;
                    $sim->sub_carrier = $sub_carrier;
                    $sim->sim_group = $sim_group;

                    $sim->charge_amount_r = $charge_amount_r;
                    $sim->charge_amount_d = $charge_amount_d;
                    $sim->charge_amount_m = $charge_amount_m;
                    $sim->sim_charge = $sim_charge;
                    $sim->sim_rebate = $sim_rebate;
                    $sim->owner_id = $owner_id;
                    $sim->shipped_date = $shipped_date;

                    $sim->amount = trim($amt) == '' ? null : $amt;
                    $sim->type = $type;
                    $sim->rtr_month = $rtr_month;
                    $sim->spiff_month = $spiff_month;
                    $sim->spiff_override_r = trim($spiff_override_r) == '' ? null : $spiff_override_r;
                    $sim->spiff_override_d = trim($spiff_override_d) == '' ? null : $spiff_override_d;
                    $sim->spiff_override_m = trim($spiff_override_m) == '' ? null : $spiff_override_m;
                    $sim->nonbyos_spiff_r = trim($nonbyos_spiff_r) == '' ? null : $nonbyos_spiff_r;
                    $sim->nonbyos_spiff_d = trim($nonbyos_spiff_d) == '' ? null : $nonbyos_spiff_d;
                    $sim->nonbyos_spiff_m = trim($nonbyos_spiff_m) == '' ? null : $nonbyos_spiff_m;
                    $sim->special_spiff_ids = trim($special_spiff_ids) == '' ? null : $special_spiff_ids;

                    $sim->residual_r = trim($residual_r) == '' ? null : $residual_r;
                    $sim->spiff_2_r = trim($spiff_2_r) == '' ? null : $spiff_2_r;
                    $sim->spiff_2_d = trim($spiff_2_d) == '' ? null : $spiff_2_d;
                    $sim->spiff_2_m = trim($spiff_2_m) == '' ? null : $spiff_2_m;
                    $sim->status = $status;
                    $sim->special_spiff = $special_spiff;
                    $sim->hide_plan_amount = $hide_plan_amount;
                    $sim->plan_description = $plan_description;
                    $sim->is_byos = $is_byos;
                }

                $sim->supplier = $supplier;
                $sim->supplier_cost = $supplier_cost;
                $sim->supplier_date = $supplier_date;
                $sim->supplier_memo = $supplier_memo;
                $sim->buyer_name = $buyer_name;
                $sim->buyer_price = $buyer_price;
                $sim->buyer_date = $buyer_date;
                $sim->buyer_memo = $buyer_memo;
                $sim->comments = $comments;
                $sim->upload_date = Carbon::now();
                $sim->save();
            }


            DB::commit();

            $this->output_success();

        } catch (\Exception $ex) {
            DB::rollback();

            Helper::log('### SIM Upload Exception ###', [
                'line' => $line,
                'code' => $ex->getCode(),
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);

            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function assign(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'c_store_id' => 'required_if:clear,N',
                'sims' => 'required'
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

            $sims = trim($request->sims);
            if (empty($sims)) {
                return response()->json([
                    'msg' => 'Please enter SIMs'
                ]);
            }

            DB::beginTransaction();

            $sim_array = explode(PHP_EOL, $sims);
            $line_no = 1;
            foreach ($sim_array as $sim) {
                $sim_objs = StockSim::where('sim_serial', $sim)->get();
                if (empty($sim_objs)) {
                    throw new \Exception('SIM : ' . $sim . ' not in our DB at line : ' . $line_no);
                }

                foreach ($sim_objs as $sim_obj) {
                    $sim_obj->c_store_id = $c_store_id;
                    $sim_obj->save();
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

    public function bulk_update(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'sims' => 'required'
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

            $sims = trim($request->sims);
            if (empty($sims)) {
                return response()->json([
                    'msg' => 'Please enter SIMs'
                ]);
            }

            DB::beginTransaction();

            $sim_array = explode(PHP_EOL, $sims);
            $line_no = 1;
            $sims = array();
            foreach ($sim_array as $sim) {

                $cur_sim = StockSim::where('sim_serial', $sim)
                    ->where('sim_group', $request->sim_group)
                    ->where('status', 'A')
                    ->first();

                if(empty($cur_sim)){
                    throw new \Exception('SIM : ' . $sim . ' at line : ' . $line_no . ' is not in our DB or using already');
                }

                $sim_objs = StockSim::where('sim_serial', $sim)
                    ->where('sim_group', $request->sim_group)
                    ->where('status', 'A')
                    ->get();

                foreach ($sim_objs as $sim_obj) {

                    if (empty($sim_obj)) {
                        throw new \Exception('SIM : ' . $sim . ' at line : ' . $line_no . ' is not in our DB or using already');
                    }

                    $sims[] = $sim;

                    if (isset($request->amount)) {
                        $sim_obj->amount = $request->amount;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->amount = null;
                    }
                    if (isset($request->type)) {
                        $sim_obj->type = strtoupper($request->type);
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->type = 'R';
                    }
                    if (isset($request->charge_amount_r)) {
                        $sim_obj->charge_amount_r = $request->charge_amount_r;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->charge_amount_r = null;
                    }
                    if (isset($request->charge_amount_d)) {
                        $sim_obj->charge_amount_d = $request->charge_amount_d;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->charge_amount_d = null;
                    }
                    if (isset($request->charge_amount_m)) {
                        $sim_obj->charge_amount_m = $request->charge_amount_m;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->charge_amount_m = null;
                    }
                    if (isset($request->rtr_month)) {
                        $sim_obj->rtr_month = $request->rtr_month;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->rtr_month = null;
                    }
                    if (isset($request->spiff_month)) {
                        $sim_obj->spiff_month = $request->spiff_month;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->spiff_month = '1|2|3';
                    }
                    if (isset($request->spiff_override_r)) {
                        $sim_obj->spiff_override_r = $request->spiff_override_r;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->spiff_override_r = null;
                    }
                    if (isset($request->spiff_override_d)) {
                        $sim_obj->spiff_override_d = $request->spiff_override_d;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->spiff_override_d = null;
                    }
                    if (isset($request->spiff_override_m)) {
                        $sim_obj->spiff_override_m = $request->spiff_override_m;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->spiff_override_m = null;
                    }
                    if (isset($request->buyer_name)) {
                        $sim_obj->buyer_name = $request->buyer_name;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->buyer_name = null;
                    }
                    if (isset($request->buyer_price)) {
                        $sim_obj->buyer_price = $request->buyer_price;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->buyer_price = null;
                    }
                    if (isset($request->buyer_date)) {
                        $sim_obj->buyer_date = $request->buyer_date;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->buyer_date = null;
                    }
                    if (isset($request->buyer_memo)) {
                        $sim_obj->buyer_memo = $request->buyer_memo;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->buyer_memo = null;
                    }
                    if (isset($request->supplier_memo)) {
                        $sim_obj->supplier_memo = $request->supplier_memo;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->supplier_memo = null;
                    }
                    if (isset($request->comments)) {
                        $sim_obj->comments = $request->comments;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->comments = null;
                    }
                    if (isset($request->sim_charge)) {
                        $sim_obj->sim_charge = $request->sim_charge;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->sim_charge = null;
                    }
                    if (isset($request->sim_rebate)) {
                        $sim_obj->sim_rebate = $request->sim_rebate;
                    } elseif ($request->reset == 'Y') {
                        $sim_obj->sim_rebate = null;
                    }

                    $sim_obj->upload_date = Carbon::now();

                    $sim_obj->update();

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

                $request->sims = $sims;

                $product_obj = Product::where('sim_group', $request->sim_group)->first();
                $request->carrier = $product_obj->carrier;

                if (!empty($request->buyer_email)) {
                    Mail::to($request->buyer_email)
                        ->bcc($email)
                        ->send(new SimOrder($request));
                } else {
                    Mail::to($email)
                        ->send(new SimOrder($request));
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

    public function get_buyer_info(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'buyer_id' => 'required'
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

            $buyer_id = $request->buyer_id;

            $account_obj = Account::where('id', $buyer_id)->where('status', 'A')->first();

            if (empty($account_obj)) {
                throw new \Exception('Account ID : ' . $buyer_id . ' is not in our DB');
            }

            return response()->json([
                'msg' => '',
                'data' => [
                    'name' => $account_obj->name . ' (' . $account_obj->id . ')',
                    'email' => $account_obj->email
                ]
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