<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/12/17
 * Time: 2:51 PM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\Permission;
use App\Lib\SimValueBinder;
use App\Model\AccountVRAuth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Model\VRProduct;
use Illuminate\Support\Facades\Input;


class VRUploadController extends Controller
{

    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        //dd($request->all());

        $sdate = null;
        $edate = null;

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $data = VRProduct::where('status', '<>', 'D');

        if (!empty($sdate)) {
            $data = $data->whereRaw('upload_date >= ?', [$sdate]);
        }

        if (!empty($edate)) {
            $data = $data->whereRaw('upload_date <= ?', [$edate]);
        }

        if (!empty($request->min)) {
            $data = $data->whereRaw('subagent_price >= ?', [$request->min]);
        }

        if (!empty($request->max)) {
            $data = $data->whereRaw('subagent_price <= ?', [$request->max]);
        }

        if (!empty($request->sku)) {
            $data = $data->whereRaw('upper(sku) like ?', ['%' . strtoupper($request->sku). '%']);
        }

        if (!empty($request->carrier)) {
            $data = $data->whereRaw('upper(carrier) like ?', ['%' . strtoupper($request->carrier). '%']);
        }

        if (!empty($request->sub_carrier)) {
            $data = $data->whereRaw('upper(sub_carrier) like ?', ['%' . strtoupper($request->sub_carrier). '%']);
        }

        if (!empty($request->category)) {
            $data = $data->whereRaw('upper(category) like ?', ['%' . strtoupper($request->category). '%']);
        }

        if (!empty($request->sub_category)) {
            $data = $data->whereRaw('upper(sub_category) like ?', ['%' . strtoupper($request->sub_category). '%']);
        }

        if (!empty($request->service_month)) {
            $data = $data->whereRaw('upper(service_month) like ?', ['%' . strtoupper($request->service_month). '%']);
        }

        if (!empty($request->plan)) {
            $data = $data->whereRaw('upper(plan) like ?', ['%' . strtoupper($request->plan). '%']);
        }

        if (!empty($request->rebate_marketing)) {
            $data = $data->whereRaw('upper(rebate_marketing) like ?', ['%' . strtoupper($request->rebate_marketing). '%']);
        }

        if (!empty($request->make)) {
            $data = $data->whereRaw('upper(make) like ?', ['%' . strtoupper($request->make). '%']);
        }

        if (!empty($request->model)) {
            $data = $data->whereRaw('upper(model) like ?', ['%' . strtoupper($request->model). '%']);
        }

        if (!empty($request->type)) {
            $data = $data->whereRaw('upper(type) like ?', ['%' . strtoupper($request->type). '%']);
        }

        if (!empty($request->desc)) {
            $data = $data->whereRaw('upper(`desc`) like ?', ['%' . strtoupper($request->desc). '%']);
        }

        if (!empty($request->grade)) {
            $data = $data->whereRaw('upper(grade) like ?', ['%' . strtoupper($request->grade). '%']);
        }

        if (!empty($request->promotion)) {
            $data = $data->whereRaw('upper(promotion) like ?', ['%' . strtoupper($request->promotion). '%']);
        }

        if (!empty($request->marketing)) {
            $data = $data->whereRaw('upper(marketing) like ?', ['%' . strtoupper($request->marketing). '%']);
        }

        if (!empty($request->month)) {
            $data = $data->where('service_month', strtoupper($request->month));
        }

        if (!empty($request->status)) {
            $data = $data->where('status', strtoupper($request->status));
        }

        if (!empty($request->is_external) && $request->is_external == 'Y') {
            $data = $data->where('is_external', 'Y');
        }

        if (!empty($request->is_dropship) && $request->is_dropship == 'Y') {
            $data = $data->where('is_dropship', 'Y');
        }

        if (!empty($request->is_free_shipping) && $request->is_free_shipping == 'Y') {
            $data = $data->where('is_free_shipping', 'Y');
        }

        if (!empty($request->stock)) {
            $data = $data->where('stock', '<=' , $request->stock);
        }

        if (!empty($request->vr_prod_id)) {
            $data = $data->where('id', $request->vr_prod_id);
        }

        if (!empty($request->exclude_all_sub)) {
            $data = $data->where('exclude_all_sub', 'Y');
        }

        if (!empty($request->exclude_all_dis)) {
            $data = $data->where('exclude_all_dis', 'Y');
        }

        if (!empty($request->exclude_all_mas)) {
            $data = $data->where('exclude_all_mas', 'Y');
        }

        if ($request->excel == 'Y') {

            if(!empty($request->sorting_filter)){
                if($request->sorting_filter == '1'){
                    $data = $data->orderBy('id', 'asc')
                        ->get();
                }elseif($request->sorting_filter == '2'){
                    $data = $data->orderBy('id', 'desc')
                        ->get();
                }elseif($request->sorting_filter == '3'){
                    $data = $data->orderBy('sorting', 'asc')
                        ->get();
                }elseif($request->sorting_filter == '4'){
                    $data = $data->orderBy('sorting', 'desc')
                        ->get();
                }
            }else {
                $data = $data->orderBy('id', 'desc')
                    ->get();
            }

            Excel::create('VR_product_' . date("mdY_h:i:s_A"), function($excel) use($data) {

                $excel->sheet('reports', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'VR.prod.ID' => $a->id,
                            'SKU' => $a->sku,
                            'Carrier' => $a->carrier,
                            'Sub.Carrier' => $a->sub_carrier,
                            'Category' => $a->category,
                            'Sub.Category' => $a->sub_category,
                            'Month.Service' => $a->service_month,
                            'Plan' => $a->plan,
                            'Make' => $a->make,
                            'Type' => $a->type,
                            'Model' => $a->{'model'},
                            'Promotion' => $a->promotion,
                            'Marketing' => $a->marketing,
                            'Rebate.Marketing' => $a->rebate_marketing,
                            'Url' => $a->url,
                            'Desc' => $a->desc,
                            'Grade' => $a->grade,
                            'Stock' => $a->stock,
                            'Supplier' => $a->supplier,
                            'Memo' => $a->memo,
                            'Master.Commission ($)' =>  number_format($a->master_commission, 2),
                            'Distributor.Commission ($)' => number_format($a->distributor_commission, 2),
                            'Master.Price ($)' => number_format($a->master_price, 2),
                            'Distributor.Price ($)' => number_format($a->distributor_price, 2),
                            'SubAgent.Price ($)' => number_format($a->subagent_price, 2),
                            'Rebate ($)' => number_format($a->rebate, 2),
                            'Status' => $a->status,
                            'IsExternal' => $a->is_external,
                            'IsDropship' => $a->is_dropship,
                            'ISFreeShipping' => $a->is_free_shipping,
                            'For Consignment' => $a->for_consignment,
                            'Allocation' => $a->max_quantity,
                            'Forever.Quantity' => $a->forever_quantity,
                            'Sorting'  => $a->sorting,
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        if(!empty($request->sorting_filter)){
            if($request->sorting_filter == '1'){
                $data = $data->orderBy('id', 'asc')
                    ->paginate(20);
            }elseif($request->sorting_filter == '2'){
                $data = $data->orderBy('id', 'desc')
                    ->paginate(20);
            }elseif($request->sorting_filter == '3'){
                $data = $data->orderBy('sorting', 'asc')
                    ->paginate(20);
            }elseif($request->sorting_filter == '4'){
                $data = $data->orderBy('sorting', 'desc')
                    ->paginate(20);
            }
        }else {
            $data = $data->orderBy('id', 'desc')
                ->paginate(20);
        }

        $carriers = VRProduct::select('carrier')->whereNotNull('carrier')->groupBy('carrier')->get();
        $sub_carriers = VRProduct::select('sub_carrier')->whereNotNull('sub_carrier')->groupBy('sub_carrier')->get();
        $categories = VRProduct::select('category')->whereNotNull('category')->groupBy('category')->get();
        $sub_categories = VRProduct::select('sub_category')->whereNotNull('sub_category')->where('status','A')->groupBy('sub_category')->get();
        $service_months = VRProduct::select('service_month')->whereNotNull('service_month')->groupBy('service_month')->get();
        $plans = VRProduct::select('plan')->whereNotNull('plan')->groupBy('plan')->get();
        $rebate_marketings = VRProduct::select('rebate_marketing')->whereNotNull('rebate_marketing')->groupBy('rebate_marketing')->get();
        $makes = VRProduct::select('make')->whereNotNull('make')->groupBy('make')->get();
        $models = VRProduct::select('model')->whereNotNull('model')->groupBy('model')->get();
        $types = VRProduct::select('type')->whereNotNull('type')->groupBy('type')->get();
        $grades = VRProduct::select('grade')->whereNotNull('grade')->groupBy('grade')->get();
        $promotions = VRProduct::select('promotion')->whereNotNull('promotion')->groupBy('promotion')->get();
        $statuss = VRProduct::select('status')->where('status', '<>', 'D')->whereNotNull('status')->groupBy('status')->get();

        return view('admin.settings.vr-upload', [
            'data' => $data,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'min'   => $request->min,
            'max'   => $request->max,
            'promotion' => $request->promotion,
            'sku' => $request->sku,
            'carrier' => $request->carrier,
            'sub_carrier' => $request->sub_carrier,
            'category' => $request->category,
            'sub_category' => $request->sub_category,
            'service_month' => $request->service_month,
            'month' => $request->month,
            'plan' => $request->plan,
            'rebate_marketing' => $request->rebate_marketing,
            'make' => $request->make,
            'model' => $request->model,
            'type' => $request->type,
            'desc' => $request->desc,
            'grade' => $request->grade,
            'status' => $request->status,
            'is_external' => $request->is_external,
            'is_dropship' => $request->is_dropship,
            'is_free_shipping' => $request->is_free_shipping,
            'stock' => $request->stock,
            'carriers' => $carriers,
            'sub_carriers' => $sub_carriers,
            'categories' => $categories,
            'sub_categories' => $sub_categories,
            'service_months' => $service_months,
            'plans' => $plans,
            'marketing' => $request->marketing,
            'rebate_marketings' => $rebate_marketings,
            'makes' => $makes,
            'models' => $models,
            'types' => $types,
            'grades' => $grades,
            'promotions' => $promotions,
            'vr_prod_id' => $request->vr_prod_id,
            'sorting_filter' => $request->sorting_filter,
            'exclude_all_sub' => $request->exclude_all_sub,
            'exclude_all_dis' => $request->exclude_all_dis,
            'exclude_all_mas' => $request->exclude_all_mas,
            'statuss'   => $statuss
        ]);
    }

    public function upload(Request $request) {

        if (!Permission::can($request->path(), 'vr-upload')) {
            $this->output_error('You are not authorized to do VR product upload!');
        }

        ini_set('max_execution_time', 600);

        DB::beginTransaction();

        try {

            $key = 'vr_csv_file';

            if (!Input::hasFile($key) || !Input::file($key)->isValid()) {
                $this->output_error('Please select VR product CSV file to upload');
            }

            $path = Input::file($key)->getRealPath();

            $binder = new SimValueBinder();
            $results = Excel::setValueBinder($binder)->load($path)->setSeparator('_')->get();
            $line_no = 0;

            foreach ($results as $row) {
                $sku = $row->sku;
                $carrier = $row->carrier;
                $sub_carrier = $row->subcarrier;
                $category = $row->category;
                $sub_category = $row->subcategory;
                $service_month = $row->monthservice;
                $plan = $row->plan;
                $make = $row->make;
                $type = $row->type;
                $model = $row->model;
                $promotion = $row->promotion;
                $marketing = $row->marketing;
                $rebate_marketing = $row->rebatemarketing;
                // $url = $row->url;
                $desc = $row->desc;
                $grade = $row->grade;
                $stock = $row->stock;
                $supplier = $row->supplier;
                $memo = $row->memo;
                $master_commission = $row->mastercommission;
                $distributor_commission = $row->distributorcommission;
                $master_price = $row->masterprice;
                $distributor_price = $row->distributorprice;
                $subagent_price = $row->subagentprice;
                $rebate = $row->rebate;
                $status = $row->status;
                $is_external = $row->isexternal;
                $is_dropship = $row->isdropship;
                $is_free_shipping = $row->isfreeshipping;
                $for_consignment = $row->forconsignment;
                $max_quantity = $row->max_quantity;
                $forever_quantity = $row->forever_quantity;
                $sorting = $row->sorting;

                $sku = trim($sku);
                // $url = trim($url);
                $stock = trim($stock);

                if (empty(trim($sku))) {
                    $this->output_error('Empty SKU found on line : ' . $line_no);
                }

                if (empty(trim($stock))) {
                    $stock = 0;
                }

                if (!is_numeric($stock)) {
                    $this->output_error('Invalid Stock data found : ' . $line_no);
                }

                if (empty(trim($master_price))) {
                    $master_price = 0;
                }

                if (empty(trim($distributor_price))) {
                    $distributor_price = 0;
                }

                if (empty(trim($master_commission))) {
                    $master_commission = 0;
                }

                if (empty(trim($distributor_commission))) {
                    $distributor_commission = 0;
                }

                if (empty(trim($subagent_price))) {
                    $subagent_price = 0;
                }

                if (empty(trim($rebate))) {
                    $rebate = 0;
                }

                if (empty(trim($status))) {
                    $status = 'A';
                }

                $vr_product = VRProduct::where('sku', strtoupper($sku))->first();

                if (empty($vr_product)) {
                    // add new vr product
                    $vr_product = new VRProduct;
                }

                $vr_product->sku = strtoupper($sku);
                $vr_product->carrier = strtoupper($carrier);
                $vr_product->sub_carrier = strtoupper($sub_carrier);
                $vr_product->category = strtoupper($category);
                $vr_product->sub_category = strtoupper($sub_category);
                $vr_product->service_month = $service_month;
                $vr_product->plan = $plan;
                $vr_product->make = strtoupper($make);
                $vr_product->type = strtoupper($type);
                $vr_product->model = $model;
                $vr_product->marketing = $marketing;
                $vr_product->rebate_marketing = $rebate_marketing;
                $vr_product->promotion = $promotion;
                // $vr_product->url = $url;
                $vr_product->desc = $desc;
                $vr_product->grade = strtoupper($grade);
                $vr_product->stock = $stock;
                $vr_product->supplier = strtoupper($supplier);
                $vr_product->memo = $memo;
                $vr_product->master_price = str_replace(",", "", $master_price);
                $vr_product->distributor_price = str_replace(",", "", $distributor_price);
                $vr_product->master_commission = str_replace(",", "", $master_commission);
                $vr_product->distributor_commission = str_replace(",", "", $distributor_commission);
                $vr_product->subagent_price = str_replace(",", "", $subagent_price);
                $vr_product->rebate = $rebate;
                $vr_product->status = $status;
                $vr_product->is_external = $is_external;
                $vr_product->is_dropship = $is_dropship;
                $vr_product->is_free_shipping = $is_free_shipping;
                $vr_product->for_consignment = $for_consignment;
                $vr_product->max_quantity = $max_quantity;
                $vr_product->forever_quantity = $forever_quantity;
                $vr_product->sorting = empty($sorting) ? 999999 : $sorting;
                $vr_product->upload_date = Carbon::now();
                $vr_product->save();

                $line_no++;
            }

            DB::commit();

            $this->output_success();

        } catch (\Exception $ex) {
            DB::rollback();
            $this->output_error($ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString());
        }
    }

    public function update(Request $request) {

        try {

            $v = Validator::make($request->all(),
                [
                'status' => 'required'
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


            $vr = VRProduct::where('id', $request->prod_id)->first();
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid product'
                ]);
            }

            $vr->status = $request->status;
            $vr->save();


            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

    public function upload_image(Request $request) {
        try{
            $product = VRProduct::find($request->prod_id);

            $file = $request->image;

            if (empty($file)) {
                return response()->json([
                    'msg' => 'No image chosen'
                ]);
            }

            if (!$file->isValid()) {
                return response()->json([
                    'msg' => 'Not valid image'
                ]);
            }

            Helper::log('XXX UPLOAD IMAGE FILE NAME XXX', [
                'ID'    => $request->prod_id,
                'FILE NAME' => $file->path()
            ]);

            if (!empty($product->url)) {
                unlink(public_path() . $product->url);
            }

            $photoName = time() . '.' . $file->getClientOriginalExtension();
            $path = $file->move(public_path('img/vr'), $photoName);
            $product->url = '/img/vr/' . $photoName;
            $product->update();

            return response()->json([
                'msg' => ''
            ]);

            
        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_stock(Request $request) {
        try{
            $product = VRProduct::find($request->prod_id);

            if (empty($product)) {
                return response()->json([
                  'msg' => 'Product not available !!'
                ]);
            }

            $product->stock = $request->stock;
            $product->update();

            return response()->json([
              'msg' => ''
            ]);


        } catch (\Exception $ex) {
            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_sorting(Request $request) {
        try{
            $product = VRProduct::find($request->prod_id);

            if (empty($product)) {
                return response()->json([
                    'msg' => 'Product not available !!'
                ]);
            }

            $product->sorting = $request->sorting;
            $product->update();

            return response()->json([
                'msg' => ''
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function show_detail(Request $request) {
        try{
            $product = VRProduct::find($request->prod_id);


            $in_accts = AccountVRAuth::where('vr_product_id', $request->prod_id)
                ->where('type', 'I')
                ->where('status', 'A')
                ->get();

            $ex_accts = AccountVRAuth::where('vr_product_id', $request->prod_id)
                ->where('type', 'E')
                ->where('status', 'A')
                ->get();

            $product->in_accts = !empty($in_accts) ? $in_accts : null;
            $product->ex_accts = !empty($ex_accts) ? $ex_accts : null;

            if (empty($product)) {
                return response()->json([
                    'msg' => 'Product not available !!'
                ]);
            }

            return response()->json([
                'msg'   => '',
                'data'  => $product
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_detail(Request $request) {
        try{
            $product = VRProduct::find($request->u_id);

            if (empty($product)) {
                return response()->json([
                    'msg' => 'Product not available !!'
                ]);
            }

            $product->carrier = strtoupper($request->carrier);
            $product->sub_carrier = strtoupper($request->sub_carrier);
            $product->category = strtoupper($request->category);
            $product->sub_category = strtoupper($request->sub_category);
            $product->service_month = $request->service_month;

            $product->plan = $request->plan;
            $product->make = strtoupper($request->make);
            $product->type = strtoupper($request->type);
            $product->model = $request->model;
            $product->marketing = $request->marketing;
            $product->rebate_marketing = $request->rebate_marketing;
            $product->promotion = $request->promotion;
            $product->url = $request->url;
            $product->desc = $request->desc;
            $product->grade = strtoupper($request->grade);
            $product->stock = $request->stock;
            $product->supplier = strtoupper($request->supplier);
            $product->memo = $request->memo;
            $product->master_price = str_replace(",", "", $request->master_price);
            $product->distributor_price = str_replace(",", "", $request->distributor_price);
            $product->master_commission = str_replace(",", "", $request->master_commission);
            $product->distributor_commission = str_replace(",", "", $request->distributor_commission);
            $product->subagent_price = str_replace(",", "", $request->subagent_price);
            $product->rebate = $request->rebate;
//            $product->status = $request->status;
            $product->is_external = strtoupper($request->is_external);
            $product->is_dropship = strtoupper($request->is_dropship);
            $product->is_free_shipping = strtoupper($request->is_free_shipping);
            $product->for_consignment = $request->for_consignment;
            $product->max_quantity = $request->max_quantity;
            $product->forever_quantity = $request->forever_quantity;
            $product->sorting = empty($request->sorting) ? 999999 : $request->sorting;

            $product->exclude_all_sub = $request->exclude_all_sub;
            $product->exclude_all_dis = $request->exclude_all_dis;
            $product->exclude_all_mas = $request->exclude_all_mas;

            if (!empty($request->include_account_ids)) {

                AccountVRAuth::where('vr_product_id', $request->u_id)->where('type', 'I')->delete();
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->include_account_ids) as $line){
                    $ava = new AccountVRAuth();
                    $ava->vr_product_id = $request->u_id;
                    $ava->type          = 'I';
                    $ava->account_id    = $line;
                    $ava->cdate         = Carbon::now();
                    $ava->save();
                }
            }else{
                AccountVRAuth::where('vr_product_id', $request->u_id)->where('type', 'I')->delete();
            }

            if (!empty($request->exclude_account_ids)) {

                AccountVRAuth::where('vr_product_id', $request->u_id)->where('type', 'E')->delete();
                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->exclude_account_ids) as $line){
                    $ava = new AccountVRAuth();
                    $ava->vr_product_id = $request->u_id;
                    $ava->type          = 'E';
                    $ava->account_id    = $line;
                    $ava->cdate         = Carbon::now();
                    $ava->save();
                }
            }else{
                AccountVRAuth::where('vr_product_id', $request->u_id)->where('type', 'E')->delete();
            }

            $product->upload_date = Carbon::now();

            $product->update();

            return response()->json([
                'msg' => ''
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function clone_detail(Request $request) {
        try{

            $product = new VRProduct;

            $product->carrier = strtoupper($request->carrier);
            $product->sub_carrier = strtoupper($request->sub_carrier);
            $product->category = strtoupper($request->category);
            $product->sub_category = strtoupper($request->sub_category);
            $product->service_month = $request->service_month;

            $product->plan = $request->plan;
            $product->make = strtoupper($request->make);
            $product->type = strtoupper($request->type);
            $product->model = $request->model;
            $product->marketing = $request->marketing;
            $product->rebate_marketing = $request->rebate_marketing;
            $product->promotion = $request->promotion;
            $product->url = $request->url;
            $product->desc = $request->desc;
            $product->grade = strtoupper($request->grade);
            $product->stock = $request->stock;
            $product->supplier = strtoupper($request->supplier);
            $product->memo = $request->memo;
            $product->master_price = str_replace(",", "", $request->master_price);
            $product->distributor_price = str_replace(",", "", $request->distributor_price);
            $product->master_commission = str_replace(",", "", $request->master_commission);
            $product->distributor_commission = str_replace(",", "", $request->distributor_commission);
            $product->subagent_price = str_replace(",", "", $request->subagent_price);
            $product->rebate = $request->rebate;
//            $product->status = $request->status;
            $product->is_external = strtoupper($request->is_external);
            $product->is_dropship = strtoupper($request->is_dropship);
            $product->is_free_shipping = strtoupper($request->is_free_shipping);
            $product->for_consignment = $request->for_consignment;
            $product->max_quantity = $request->max_quantity;
            $product->forever_quantity = $request->forever_quantity;
            $product->sorting = empty($request->sorting) ? 999999 : $request->sorting;

            $product->exclude_all_sub = $request->exclude_all_sub;
            $product->exclude_all_dis = $request->exclude_all_dis;
            $product->exclude_all_mas = $request->exclude_all_mas;

            $product->upload_date = Carbon::now();

            $product->save();

            if (!empty($request->include_account_ids)) {

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->include_account_ids) as $line){
                    $ava = new AccountVRAuth();
                    $ava->vr_product_id = $product->id;
                    $ava->type          = 'I';
                    $ava->account_id    = $line;
                    $ava->cdate         = Carbon::now();
                    $ava->save();
                }
            }

            if (!empty($request->exclude_account_ids)) {

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->exclude_account_ids) as $line){
                    $ava = new AccountVRAuth();
                    $ava->vr_product_id = $product->id;
                    $ava->type          = 'E';
                    $ava->account_id    = $line;
                    $ava->cdate         = Carbon::now();
                    $ava->save();
                }
            }

            return response()->json([
                'msg' => ''
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function add_detail(Request $request) {

        if (!Permission::can($request->path(), 'vr-upload')) {
            $this->output_error('You are not authorized to do VR product upload!');
        }

        try {

            $product = new VRProduct();

            $product->carrier = strtoupper($request->carrier);
            $product->sub_carrier = strtoupper($request->sub_carrier);
            $product->category = strtoupper($request->category);
            $product->sub_category = strtoupper($request->sub_category);
            $product->service_month = $request->service_month;

            $product->plan = $request->plan;
            $product->make = strtoupper($request->make);
            $product->type = strtoupper($request->type);
            $product->model = $request->model;
            $product->marketing = $request->marketing;
            $product->rebate_marketing = $request->rebate_marketing;
            $product->promotion = $request->promotion;
            $product->url = $request->url;
            $product->desc = $request->desc;
            $product->grade = strtoupper($request->grade);

            $stock = trim($request->stock);
            if (empty(trim($stock))) {
                $stock = 0;
            }
            $product->stock = $stock;

            $product->supplier = strtoupper($request->supplier);
            $product->memo = $request->memo;

            $master_price = $request->master_price;
            if (empty(trim($master_price))) {
                $master_price = 0;
            }
            $product->master_price = str_replace(",", "", $master_price);

            $master_commission = $request->master_commission;
            if (empty(trim($master_commission))) {
                $master_commission = 0;
            }
            $product->master_commission = str_replace(",", "", $master_commission);

            $distributor_price = $request->distributor_price;
            if (empty(trim($distributor_price))) {
                $distributor_price = 0;
            }
            $product->distributor_price = str_replace(",", "", $distributor_price);

            $distributor_commission = $request->distributor_commission;
            if (empty(trim($distributor_commission))) {
                $distributor_commission = 0;
            }
            $product->distributor_commission = str_replace(",", "", $distributor_commission);

            $subagent_price = $request->subagent_price;
            if (empty(trim($subagent_price))) {
                $subagent_price = 0;
            }
            $product->subagent_price = str_replace(",", "", $subagent_price);

            $rebate = $request->rebate;
            if (empty(trim($rebate))) {
                $rebate = 0;
            }
            $product->rebate = $rebate;

//            $product->status = $request->status;
            $product->is_external = strtoupper($request->is_external);
            $product->is_dropship = strtoupper($request->is_dropship);
            $product->is_free_shipping = strtoupper($request->is_free_shipping);
            $product->for_consignment = $request->for_consignment;
            $product->max_quantity = $request->max_quantity;
            $product->forever_quantity = $request->forever_quantity;
            $product->sorting = empty($request->sorting) ? 999999 : $request->sorting;

            $product->upload_date = Carbon::now();

            $product->save();

            if (!empty($request->include_account_ids)) {

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->include_account_ids) as $line){
                    $ava = new AccountVRAuth();
                    $ava->vr_product_id = $product->id;
                    $ava->type          = 'I';
                    $ava->account_id    = $line;
                    $ava->cdate         = Carbon::now();
                    $ava->save();
                }
            }

            if (!empty($request->exclude_account_ids)) {

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->exclude_account_ids) as $line){
                    $ava = new AccountVRAuth();
                    $ava->vr_product_id = $product->id;
                    $ava->type          = 'E';
                    $ava->account_id    = $line;
                    $ava->cdate         = Carbon::now();
                    $ava->save();
                }
            }

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
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