<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/27/17
 * Time: 3:55 PM
 */

namespace App\Http\Controllers\Admin\Settings;


use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\Product;
use App\Model\Vendor;
use App\Model\VendorDenom;
use App\Model\RateDetail;
use App\Model\VendorFeeSetup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use PHPExcel_Helper_HTML;

use DB;

class ProductSetupController extends Controller
{

    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        if($request->excel == 'Y') {
            $query = Product::join('vendor_denom',function($join) {
                $join->on('vendor_denom.product_id', 'product.id');
                $join->on('vendor_denom.vendor_code', 'product.vendor_code');
            })
            ->join("denomination", function($join) {
                $join->on('denomination.product_id', 'vendor_denom.product_id');
                $join->on('denomination.id', 'vendor_denom.denom_id');
            })
            ;
        }else {
            $query = Product::query();
        }

        if (!empty($request->carrier)) {
            $query = $query->where('product.carrier', $request->carrier);
        }

        if (!empty($request->type)) {
            $query = $query->where('product.type', $request->type);
        }

        if (!empty($request->vendor_code)) {
            $query = $query->where('product.vendor_code', $request->vendor_code);
        }

        if (!empty($request->name)) {
            $query = $query->whereRaw("lower(product.name) like ?", [ '%' . strtolower($request->name) . '%']);
        }

        if (!empty($request->status)) {
            $query = $query->where('product.status', $request->status);
        }

        if (!empty($request->sku)) {
            $query = $query->whereRaw("id in (
                        select distinct product_id
                        from vendor_denom
                        where lower(act_pid) like '%". strtolower($request->sku) . "%' 
                        or lower(rtr_pid) like '%". strtolower($request->sku) . "%' 
                        or lower(pin_pid) like '%". strtolower($request->sku) . "%' )");
        }

        if ($request->excel == 'Y') {

            $data = $query->select(
                    'product.id as p_id',
                    'product.type as p_type',
                    'product.carrier as p_carrier',
                    'product.sim_group as p_sim_group',
                    'product.name as p_name',
                    'denomination.name as d_name',
                    'product.status as p_status',
                    'product.vendor_code as p_vendor_code',
                    'product.activation as p_activation',
                    'product.acct_fee as p_acct_fee',
                    'product.country_code as country_code',
                    'vendor_denom.denom as v_denom',
                    'vendor_denom.cost as v_cost',
                    'vendor_denom.fee as v_fee',
                    'vendor_denom.pm_fee as v_pm_fee',
                    'vendor_denom.act_pid as v_act_pid',
                    'vendor_denom.rtr_pid as v_rtr_pid',
                    'vendor_denom.pin_pid as v_pin_pid',
                    'vendor_denom.status as v_status'
                )
                ->orderBy('product.id', 'asc')
                ->get();

            Excel::create('product_setup', function($excel) use($data) {
                $excel->sheet('reports', function($sheet) use($data) {
                    $reports = [];
                    foreach ($data as $a) {
                        $wizard = new PHPExcel_Helper_HTML;
                        $reports[] = [
                            'Product.ID' => $a->p_id,
                            'Product.Type' => $a->p_type,
                            'Carrier' => $a->p_carrier,
                            'Product.Sim.Group' => $a->p_sim_group,
                            'Vendor.Code' => $a->p_vendor_code,
                            'Product.Name' => $a->p_name,
                            'Denom.Name' => $a->d_name,
                            'Product.Status' => $a->p_status,
                            'For.Activation?' => $a->p_activation,
                            'Activation.Fee?' => $a->p_acct_fee,
                            'Country.Code' => $a->country_code,
                            'Denom' => $a->v_denom,
                            'Cost' => $a->v_cost,
                            'Fee' => $a->v_fee,
                            'PM.Fee' => $a->v_pm_fee,
                            'Act.pid' => $a->v_act_pid,
                            'RTR.pid' => $a->v_rtr_pid,
                            'Pin.pid' => $a->v_pin_pid,
                            'Denom.Status' => $a->v_status
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->paginate(20);

        $vendors = Vendor::all();
        $carriers = Carrier::all();
        $s_user = !in_array(Auth::user()->user_id, ['thomas','admin', 'system']) ? 'N' : 'Y';

        return view('admin.settings.product-setup', [
            'data'      => $data,
            'carrier'   => $request->carrier,
            'type'      => $request->type,
            'vendor_code' => $request->vendor_code,
            'name'      => $request->name,
            'status'    => $request->status,
            'vendors'   => $vendors,
            'denoms'    => null,
            'carriers'  => $carriers,
            'sku'       => $request->sku,
            's_user'    => $s_user
        ]);
    }

    public function loadDetail(Request $request) {
        try {

            $s_user = !in_array(Auth::user()->user_id, ['thomas','admin', 'system']) ? 'N' : 'Y';

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $product = Product::find($request->id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product provided'
                ]);
            }

            $vendor_code = $product->vendor_code;

            $init_denoms_rtr = VendorDenom::where('product_id', $request->id)->where('vendor_code', $vendor_code)
                ->whereNotNull('rtr_pid')->orderBy('denom', 'asc')->get();

            $init_denoms_pin = VendorDenom::where('product_id', $request->id)->where('vendor_code', $vendor_code)
                ->whereNotNull('pin_pid')->orderBy('denom', 'asc')->get();

            $denoms = Denom::where('product_id', $product->id)->orderBy('denom', 'asc')->get();

            $init_denoms = Denom::where('product_id', $product->id)->orderBy('denom', 'asc')->where('status', 'A')->get();

            $vendor_denoms = VendorDenom::Leftjoin('denomination', function($join) {
                $join->on('denomination.id', 'vendor_denom.denom_id');
            })->where('vendor_denom.product_id', $product->id);

            if (!empty($request->vendor)) {
                $vendor_denoms = $vendor_denoms->where('vendor_denom.vendor_code', $request->vendor);
            }
            if (!empty($request->denom)) {
                $vendor_denoms = $vendor_denoms->where('vendor_denom.denom', $request->denom);
            }

            if(!empty($request->sku)) {
                $vendor_denoms = $vendor_denoms->whereRaw('upper(vendor_denom.act_pid) like ?',  ['%' . strtoupper($request->sku) . '%'])
                                                ->orWhereRaw('upper(vendor_denom.rtr_pid) like ?',  ['%' . strtoupper($request->sku) . '%'])
                                                ->orWhereRaw('upper(vendor_denom.pin_pid) like ?',  ['%' . strtoupper($request->sku) . '%']);
            }

            $vendor_denoms = $vendor_denoms->select('vendor_denom.*', 'denomination.name as name')
                ->orderBy('vendor_denom.vendor_code', 'asc')
                ->orderBy('vendor_denom.denom', 'asc')->get();

            $vendors = Vendor::all();

            $vendor_fee_setup = VendorFeeSetup::where('product_id', $product->id)
                ->where('amt_and_fee', 'Y')
                ->get();

            return response()->json([
                'msg' => '',
                'product' => $product,
                'denoms' => $denoms,
                'init_denoms' => $init_denoms,
                'vendor_denoms' => $vendor_denoms,
                'vendors' => $vendors,
                'init_denoms_rtr' => $init_denoms_rtr,
                'init_denoms_pin' => $init_denoms_pin,
                'vendor_fee_setup' => $vendor_fee_setup,
                's_user' => $s_user
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_init_denoms(Request $request) {
        try {

            $product = Product::find($request->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product provided'
                ]);
            }
            $vendor_code = $product->vendor_code;

            if($request->action == 'RTR') {
                $init_denoms = VendorDenom::where('product_id', $request->product_id)->where('vendor_code', $vendor_code)
                    ->whereNotNull('rtr_pid')->orderBy('denom', 'asc')->get();
            }else {
                $init_denoms = VendorDenom::where('product_id', $request->product_id)->where('vendor_code', $vendor_code)
                    ->whereNotNull('pin_pid')->orderBy('denom', 'asc')->get();
            }
            return response()->json([
                'msg' => '',
                'init_denoms' => $init_denoms
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function add(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'type' => 'required',
                'carrier' => 'required',
                'vendor_code' => 'required',
                'activation' => 'required',
                'name' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $product = Product::find($request->id);
            if (!empty($product)) {
                return response()->json([
                    'msg' => 'Duplicated product ID provided'
                ]);
            }

            $product = new Product;
            $product->id = $request->id;
            $product->type = $request->type;
            $product->carrier = $request->carrier;
            $product->sim_group = $request->sim_group;
            $product->vendor_code = $request->vendor_code;
            $product->name = $request->name;
            $product->activation = $request->activation;
            $product->status = $request->status;
            $product->acct_fee = $request->acct_fee;
            $product->country_code = $request->country_code;
            $product->cdate = Carbon::now();
            $product->created_by = Auth::user()->user_id;
            $product->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);


        }
    }

    public function update(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'type' => 'required',
                'carrier' => 'required',
                'vendor_code' => 'required',
                'activation' => 'required',
                'name' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $product = Product::find($request->id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product ID provided'
                ]);
            }

            $product->type = $request->type;
            $product->carrier = $request->carrier;
            $product->sim_group = $request->sim_group;
            $product->vendor_code = $request->vendor_code;
            $product->name = $request->name;
            $product->activation = $request->activation;
            $product->status = $request->status;
            $product->acct_fee = $request->acct_fee;
            $product->country_code = $request->country_code;
            $product->cdate = Carbon::now();
            $product->created_by = Auth::user()->user_id;
            $product->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {

            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);


        }
    }

    public function addDenom(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                'denom' => 'required|numeric',
                'denom_name' => 'required',
                'min_denom' => 'required|numeric',
                'max_denom' => 'required|numeric',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $product = Product::find($request->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product ID provided'
                ]);
            }

            /*** delete for denom_id use
            $denom = Denom::where('product_id', $product->id)
                ->where('denom', $request->denom)
                ->first();

            if (!empty($denom)) {
                return response()->json([
                    'msg' => 'Duplicated record found'
                ]);
            }
             ***/

            if ($request->denom != 0 &&
                ($request->min_denom > $request->max_denom ||
                $request->min_denom > $request->denom ||
                $request->denom > $request->max_denom)) {
                return response()->json([
                    'msg' => 'Min & Max denom range is invalid'
                ]);
            }

            $denom = new Denom;
            $denom->product_id = $product->id;
            $denom->denom = $request->denom;
            $denom->name = $request->denom_name;
            $denom->min_denom = $request->min_denom;
            $denom->max_denom = $request->max_denom;
            $denom->status = $request->status;
            $denom->cdate = Carbon::now();
            $denom->created_by = Auth::user()->user_id;
            $denom->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updateDenom(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                'denom_id' => 'required',
                'denom' => 'required|numeric',
                'denom_name' => 'required',
                'min_denom' => 'required|numeric',
                'max_denom' => 'required|numeric',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $product = Product::find($request->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product ID provided'
                ]);
            }

            /*** remove for denom_id use
            $denom = Denom::where('product_id', $product->id)
                ->where('denom', $request->denom)
                ->where('id', '!=', $request->denom_id)
                ->first();

            if (!empty($denom)) {
                return response()->json([
                    'msg' => 'Duplicated record found'
                ]);
            }
            ***/

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid denomination ID provided'
                ]);
            }

            if ($request->denom != 0 &&
                ($request->min_denom > $request->max_denom ||
                    $request->min_denom > $request->denom ||
                    $request->denom > $request->max_denom)) {
                return response()->json([
                    'msg' => 'Min & Max denom range is invalid'
                ]);
            }

            $denom->product_id = $product->id;
            $denom->denom = $request->denom;
            $denom->name = $request->denom_name;
            $denom->min_denom = $request->min_denom;
            $denom->max_denom = $request->max_denom;
            $denom->status = $request->status;
            $denom->mdate = Carbon::now();
            $denom->modified_by = Auth::user()->user_id;
            $denom->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }


    public function addVendorDenom(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                'vendor_code' => 'required',
                'denom_id' => 'required|numeric',
                'fee' => 'required|numeric',
                'pm_fee' => 'required|numeric',
                'cost' => 'required|numeric',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            if (empty($request->act_pid) && empty($request->rtr_pid) && empty($request->pin_pid)) {
                return response()->json([
                    'msg' => 'At lease one SKU is required'
                ]);
            }

            $product = Product::find($request->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product ID provided'
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid Denomination'
                ]);
            }

            $vendor_denom = VendorDenom::where('product_id', $product->id)
                ->where('vendor_code', $request->vendor_code)
                ->where('denom_id', $request->denom_id)
                ->first();

            if (!empty($vendor_denom)) {
                return response()->json([
                    'msg' => 'Duplicated record found'
                ]);
            }

            $vendor_denom = new VendorDenom;
            $vendor_denom->product_id = $product->id;
            $vendor_denom->vendor_code = $request->vendor_code;
            $vendor_denom->denom = $denom->denom;
            $vendor_denom->denom_id = $denom->id;
            $vendor_denom->act_pid = $request->act_pid;
            $vendor_denom->rtr_pid = $request->rtr_pid;
            $vendor_denom->pin_pid = $request->pin_pid;
            $vendor_denom->fee = $request->fee;
            $vendor_denom->pm_fee = $request->pm_fee;
            $vendor_denom->cost = $request->cost;
            $vendor_denom->status = $request->status;
            $vendor_denom->cdate = Carbon::now();
            $vendor_denom->created_by = Auth::user()->user_id;
            $vendor_denom->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updateVendorDenom(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'vendor_denom_id' => 'required',
                'product_id' => 'required',
                'vendor_code' => 'required',
                'denom' => 'required|numeric',
                'fee' => 'required|numeric',
                'pm_fee' => 'required|numeric',
                'cost' => 'required|numeric',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            if (empty($request->act_pid) && empty($request->rtr_pid) && empty($request->pin_pid)) {
                return response()->json([
                    'msg' => 'At lease one SKU is required'
                ]);
            }

            $product = Product::find($request->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product ID provided'
                ]);
            }

            $denom = Denom::find($request->denom_id);
            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid Denomination'
                ]);
            }

            $vendor_denom = VendorDenom::where('product_id', $product->id)
                           ->where('id', '=', $request->vendor_denom_id)
                           ->first();
            if (empty($vendor_denom)) {
                return response()->json([
                    'msg' => 'Invalid vendor denom ID provided'
                ]);
            }


            $vendor_denom->product_id = $product->id;
            $vendor_denom->vendor_code = $request->vendor_code;
            $vendor_denom->denom = $request->denom;
            $vendor_denom->act_pid = $request->act_pid;
            $vendor_denom->rtr_pid = $request->rtr_pid;
            $vendor_denom->pin_pid = $request->pin_pid;
            $vendor_denom->status = $request->status;
            $vendor_denom->fee = $request->fee;
            $vendor_denom->pm_fee = $request->pm_fee;
            $vendor_denom->cost = $request->cost;
            $vendor_denom->cdate = Carbon::now();
            $vendor_denom->created_by = Auth::user()->user_id;
            $vendor_denom->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function init_rates(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                'm_rates' => 'required',
                'd_rates' => 'required',
                's_rates' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            if ($request->m_rates < $request->d_rates) {
                return response()->json([
                    'msg' => 'Distributor rates can not bigger then Master rates'
                ]);
            }

            if ($request->d_rates < $request->s_rates) {
                return response()->json([
                    'msg' => 'Subagent rates can not bigger then Master rates'
                ]);
            }

            $action = empty($request->action) ? 'RTR' : $request->action;

            if(!empty($request->denom)){

                $denomination = Denom::where('product_id', $request->product_id)
                    ->where('denom', $request->denom)
                    ->first();
                if (empty($denomination)) {
                    return response()->json([
                        'msg' => 'Denomination is not Exist'
                    ]);
                }

                RateDetail::where('action', $action)->whereRaw('denom_id in (select id from denomination where product_id = \'' . $request->product_id . '\' and denom = \'' . $request->denom . '\')')->delete();

                $ret = DB::statement("
                insert into rate_detail (
                    rate_plan_id, denom_id, action, rates, created_by, cdate
                )
                select rp.id, d.id, :action,
                    case rp.type
                      when 'M' then :m_rates
                      when 'D' then :d_rates
                      when 'S' then :s_rates
                      else 100
                     end as rates,
                    :created_by,
                    :cdate
                  from rate_plan rp
                  join denomination d
                 where d.id in (
                   select id from denomination where product_id = :product_id and denom = :denom
                   )
                ", [
                    'product_id' => $request->product_id,
                    'action' => $action,
                    'm_rates' => $request->m_rates,
                    'd_rates' => $request->d_rates,
                    's_rates' => $request->s_rates,
                    'denom' => $request->denom,
                    'created_by' => Auth::user()->user_id,
                    'cdate' => Carbon::now()
                ]);
            }else {

                RateDetail::where('action', $action)->whereRaw('denom_id in (select id from denomination where product_id = \'' . $request->product_id . '\')')->delete();

                $ret = DB::statement("
                insert into rate_detail (
                    rate_plan_id, denom_id, action, rates, created_by, cdate
                )
                select rp.id, d.id, :action,
                    case rp.type
                      when 'M' then :m_rates
                      when 'D' then :d_rates
                      when 'S' then :s_rates
                      else 100
                     end as rates,
                    :created_by,
                    :cdate
                  from rate_plan rp
                  join denomination d
                 where d.id in (
                   select id from denomination where product_id = :product_id
                   )
                ", [
                    'product_id' => $request->product_id,
                    'action' => $action,
                    'm_rates' => $request->m_rates,
                    'd_rates' => $request->d_rates,
                    's_rates' => $request->s_rates,
                    'created_by' => Auth::user()->user_id,
                    'cdate' => Carbon::now()
                ]);
            }

            if ($ret < 1) {
                return response()->json([
                    'msg' => 'Failed to init rates'
                ]);
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

    public function delete_rates(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $action = empty($request->action) ? 'RTR' : $request->action;

            if(!empty($request->denom)){

                $denomination = Denom::where('product_id', $request->product_id)
                    ->where('denom', $request->denom)
                    ->first();
                if (empty($denomination)) {
                    return response()->json([
                        'msg' => 'Denomination is not Exist'
                    ]);
                }

                RateDetail::where('action', $action)
                    ->whereRaw('denom_id in (select id from denomination where product_id = \'' . $request->product_id . '\' and denom = \'' . $request->denom . '\')')
                    ->delete();
            }else {
                RateDetail::where('action', $action)
                    ->whereRaw('denom_id in (select id from denomination where product_id = \'' . $request->product_id . '\')')
                    ->delete();
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

    public function vendorFeeSetup(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'product_id' => 'required',
                'vendor_code' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            $ret = VendorFeeSetup::where('vendor_code', $request->vendor_code)
                ->where('product_id', $request->product_id)
                ->where('amt_and_fee', 'Y')
                ->first();

            if (!empty($ret)) {
                return response()->json([
                    'msg' => 'Duplicated record found'
                ]);
            }

            $vfs = new VendorFeeSetup();
            $vfs->vendor_code = $request->vendor_code;
            $vfs->product_id = $request->product_id;
            $vfs->amt_and_fee = 'Y';
            $vfs->cdate = Carbon::now();
            $vfs->created_by = Auth::user()->user_id;
            $vfs->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function vendorFeeSetupDel(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'msg' => $msg
                ]);
            }

            VendorFeeSetup::where('id', $request->id)->delete();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}