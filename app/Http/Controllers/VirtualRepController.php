<?php
/**
 * Created by Royce.
 * Date: 06/19/18
 */

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Validator;
use App\Model\VirtualRep;
use App\Model\VRProduct;
use App\Model\VRRequest;
use App\Model\VRRequestProduct;

class VirtualRepController extends Controller
{
    public function show(Request $request) {
        //$vr_product = VRProduct::all();

        $sdate = null;
        $edate = null;

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $data = VRProduct::where('is_external', 'Y');

        if (!empty($sdate)) {
            $data = $data->whereRaw('upload_date >= ?', [$sdate]);
        }

        if (!empty($edate)) {
            $data = $data->whereRaw('upload_date <= ?', [$edate]);
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

        if (!empty($request->month)) {
            $data = $data->where('service_month', strtoupper($request->month));
        }

        $data = $data->where('status', 'A')
            ->orderBy('sorting', 'asc')
            ->orderBy('category', 'asc')
            ->orderBy('sub_category', 'asc')
            ->orderBy('carrier', 'asc')
            ->orderBy('sub_carrier', 'asc')
            ->orderBy('model', 'asc')
            ->paginate(40);


        $carriers = VRProduct::select('carrier')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('carrier')->groupBy('carrier')->get();
        $sub_carriers = VRProduct::select('sub_carrier')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('sub_carrier')->groupBy('sub_carrier')->get();
        $categories = VRProduct::select('category')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('category')->groupBy('category')->get();
        $sub_categories = VRProduct::select('sub_category')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('sub_category')->groupBy('sub_category')->get();
        $service_months = VRProduct::select('service_month')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('service_month')->groupBy('service_month')->get();
        $plans = VRProduct::select('plan')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('plan')->groupBy('plan')->get();
        $makes = VRProduct::select('make')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('make')->groupBy('make')->get();
        $models = VRProduct::select('model')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('model')->groupBy('model')->get();
        $types = VRProduct::select('type')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('type')->groupBy('type')->get();
        $grades = VRProduct::select('grade')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('grade')->groupBy('grade')->get();
        $promotions = VRProduct::select('promotion')->where('is_external', 'Y')->where('status', 'A')->whereNotNull('promotion')->groupBy('promotion')->get();

        return view('virtual-rep', [
            'vr_product' => $data,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'promotion' => $request->promotion,
            'sku' => $request->sku,
            'carrier' => $request->carrier,
            'sub_carrier' => $request->sub_carrier,
            'category' => $request->category,
            'sub_category' => $request->sub_category,
            'service_month' => $request->service_month,
            'month' => $request->month,
            'plan' => $request->plan,
            'make' => $request->make,
            'model' => $request->model,
            'type' => $request->type,
            'desc' => $request->desc,
            'grade' => $request->grade,
            'carriers' => $carriers,
            'sub_carriers' => $sub_carriers,
            'categories' => $categories,
            'sub_categories' => $sub_categories,
            'service_months' => $service_months,
            'plans' => $plans,
            'makes' => $makes,
            'models' => $models,
            'types' => $types,
            'grades' => $grades,
            'promotions' => $promotions
        ]);
    }

}