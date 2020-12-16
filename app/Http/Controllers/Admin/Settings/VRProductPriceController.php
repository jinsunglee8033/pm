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
use App\Model\Account;
use App\Model\VRProductPrice;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use App\Model\VRProduct;
use Illuminate\Support\Facades\Input;


class VRProductPriceController extends Controller
{

    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $account_id = $request->account_id;

        $vr_prod_id = $request->vr_prod_id;
        $carrier    = $request->carrier;
        $model      = $request->model;
        $desc       = $request->desc;
        $min        = $request->min;
        $max        = $request->max;
        $default    = $request->default;
        $acct_type  = $request->acct_type;


        $query1 = "
            select v.*, p.m_price, p.d_price, p.s_price, p.m_commission, p.d_commission, p.account_id, p.id as p_id, p.min_quan, p.max_quan, p.marketing, p.rebate_marketing, p.expired_date, p.is_free_ship, p.cdate, p.quick_note
            from vr_product v
            inner join vr_product_price p on p.vr_prod_id = v.id
            where v.status <> 'D'
        ";

        if (!empty($request->carrier)){
            $query1 .= " and v.carrier = '$carrier'";
        }

        if(!empty($vr_prod_id)){
            $query1 .= " and p.vr_prod_id = $vr_prod_id";
        }

        if(!empty($model)){
            $query1 .= " and upper(v.model) like '%".strtoupper($model)."%' ";
        }

        if(!empty($desc)){
            $query1 .= " and upper(v.desc) like '%".strtoupper($desc)."%' ";
        }

        if(!empty($min)){
            if($acct_type == 'M'){
                $query1 .= " and p.m_price >= '$min'";
            }elseif($acct_type == 'D'){
                $query1 .= " and p.d_price >= '$min'";
            }elseif($acct_type == 'S'){
                $query1 .= " and p.s_price >= '$min'";
            }
        }

        if(!empty($max)){
            if($acct_type == 'M'){
                $query1 .= " and p.m_price <= '$max'";
            }elseif($acct_type == 'D'){
                $query1 .= " and p.d_price <= '$max'";
            }elseif($acct_type == 'S'){
                $query1 .= " and p.s_price <= '$max'";
            }
        }

        if(!empty($account_id)){
            $query1 .= " and p.account_id in ( select id from accounts where path like '%$account_id%' ) ";
        }

        if(!empty($acct_type)){
            $query1 .= " and p.account_id in ( select id from accounts where type = '$acct_type' ) ";
        }

        $sdate_c = null;
        if (!empty($request->sdate_c)) {
            $sdate_c = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate_c . ' 00:00:00');
            $query1 .= " and p.cdate >= '$sdate_c' ";
        }
        $edate_c = null;
        if (!empty($request->edate_c)) {
            $edate_c = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate_c . ' 23:59:59');
            $query1 .= " and p.cdate < '$edate_c' ";
        }

        if(!empty($request->prod_status)){
            if($request->prod_status == 'A'){
                $query1 .= " and v.status = 'A' ";
            }elseif($request->prod_status == 'I'){
                $query1 .= " and v.status = 'I' ";
            }
        }

        $query2 = "
        select v.*, 'm_price', 'd_price', 's_price', 'm_commission','d_commission','000000', 'p_id', '-', '-', '-', '-', '-', '-', '-', '_'
        from vr_product v
        where v.status <> 'D'
        ";

        if (!empty($request->carrier)){
            $query2 .= " and v.carrier = '$carrier'";
        }

        if(!empty($vr_prod_id)){
            $query2 .= " and v.id = $vr_prod_id";
        }

        if(!empty($model)){
            $query2 .= " and upper(v.model) like '%".strtoupper($model)."%' ";
        }

        if(!empty($desc)){
            $query2 .= " and upper(v.desc) like '%".strtoupper($desc)."%' ";
        }

        if(!empty($min)){
            if($acct_type == 'M'){
                $query2 .= " and v.master_price >= '$min'";
            }elseif($acct_type == 'D'){
                $query2 .= " and v.distributor_price >= '$min'";
            }else{
                $query2 .= " and v.subagent_price >= '$min'";
            }
        }

        if(!empty($max)){
            if($acct_type == 'M'){
                $query2 .= " and v.master_price <= '$max'";
            }elseif($acct_type == 'D'){
                $query2 .= " and v.distributor_price <= '$max'";
            }else{
                $query2 .= " and v.subagent_price <= '$max'";
            }
        }

        if(!empty($request->prod_status)){
            if($request->prod_status == 'A'){
                $query2 .= " and v.status = 'A' ";
            }elseif($request->prod_status == 'I'){
                $query2 .= " and v.status = 'I' ";
            }
        }

        if ($request->excel == 'Y') {

            if(!empty($default)){
                if($default == 'Y'){
                    $query = $query2;
                    $data = DB::select($query);
                    Excel::create('VR_product_price_' . date("mdY_h:i:s_A"), function($excel) use($data) {
                        $excel->sheet('reports', function($sheet) use($data) {
                            $reports = [];
                            foreach ($data as $a) {
                                $reports[] = [
                                    'ID'            => $a->id,
                                    'Carrier'       => $a->carrier,
                                    'Model'         => $a->model,
                                    'Desc'          => $a->desc,
                                    'Default'       => 'Default',
                                    'Account.ID'    => '',
                                    'Mas.Price ($)' => number_format($a->master_price, 2),
                                    'Dis.Price ($)' => number_format($a->distributor_price, 2),
                                    'SubA.Price ($)' => number_format($a->subagent_price, 2) ,
                                    'Mas.Commission ($)' => number_format($a->master_commission, 2),
                                    'Dis.Commission ($)' => number_format($a->distributor_commission, 2),
                                    'Stock' => $a->stock,
                                ];
                            }
                            $sheet->fromArray($reports);
                        });
                    })->export('xlsx');
                }
            }elseif($account_id){
                $query = $query1;
                $data = DB::select($query);
                Excel::create('VR_product_price_' . date("mdY_h:i:s_A"), function($excel) use($data) {
                    $excel->sheet('reports', function($sheet) use($data) {
                        $reports = [];
                        foreach ($data as $a) {

                            $reports[] = [
                                'ID' => $a->id,
                                'Carrier' => $a->carrier,
                                'Model' => $a->model,
                                'Desc' => $a->desc,
                                'Default' => $a->account_id == '000000' ? 'Default' : '',
                                'Account.ID' => $a->account_id == '000000' ? '' : $a->account_id,
                                'Mas.Price ($)' => $a->account_id == '000000' ? number_format($a->master_price, 2) : number_format($a->m_price, 2),
                                'Dis.Price ($)' => $a->account_id == '000000' ? number_format($a->distributor_price, 2) : number_format($a->d_price, 2),
                                'SubA.Price ($)' => $a->account_id == '000000' ? number_format($a->subagent_price, 2) : number_format($a->s_price, 2),
                                'Mas.Commission ($)' => $a->account_id == '000000' ? number_format($a->master_commission, 2) : number_format($a->m_commission, 2),
                                'Dis.Commission ($)' => $a->account_id == '000000' ? number_format($a->distributor_commission, 2) : number_format($a->d_commission, 2),
                                'Stock' => $a->stock,
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');

            } else {
                $query = $query1 . " union all " . $query2;
                $query .= " order by desc id, account_id asc";
                $data = DB::select($query);
                Excel::create('VR_product_price_' . date("mdY_h:i:s_A"), function($excel) use($data) {
                    $excel->sheet('reports', function($sheet) use($data) {
                        $reports = [];
                        foreach ($data as $a) {
                            $reports[] = [
                                'ID' => $a->id,
                                'Carrier' => $a->carrier,
                                'Model' => $a->model,
                                'Desc' => $a->desc,
                                'Default' => $a->account_id == '000000' ? 'Default' : '',
                                'Account.ID' => $a->account_id == '000000' ? '' : $a->account_id,
                                'Mas.Price ($)' => $a->account_id == '000000' ? number_format($a->master_price, 2) : number_format($a->m_price, 2),
                                'Dis.Price ($)' => $a->account_id == '000000' ? number_format($a->distributor_price, 2) : number_format($a->d_price, 2),
                                'SubA.Price ($)' => $a->account_id == '000000' ? number_format($a->subagent_price, 2) : number_format($a->s_price, 2),
                                'Mas.Commission ($)' => $a->account_id == '000000' ? number_format($a->master_commission, 2) : number_format($a->m_commission, 2),
                                'Dis.Commission ($)' => $a->account_id == '000000' ? number_format($a->distributor_commission, 2) : number_format($a->d_commission, 2),
                                'Stock' => $a->stock,
                            ];
                        }
                        $sheet->fromArray($reports);
                    });
                })->export('xlsx');
            }
        }

        if(!empty($default) || $sdate_c != null ){
            if($default == 'Y'){
                $query = $query2;
            }elseif($default == 'N' || $sdate_c != null){
                $query = $query1;
            }

            if(!empty($request->sorting_filter)){
                if($request->sorting_filter == '1') {
                    $query .= " order by v.id asc ";
                }elseif($request->sorting_filter == '2'){
                    $query .= " order by v.id desc ";
                }
            }else{
                $query .= " order by v.id desc ";
            }

        }elseif(!empty($account_id) || !empty($acct_type)){
            $query = $query1;

            if(!empty($request->sorting_filter)){
                if($request->sorting_filter == '1') {
                    $query .= " order by v.id asc ";
                }elseif($request->sorting_filter == '2'){
                    $query .= " order by v.id desc ";
                }
            }else{
                $query .= " order by v.id desc ";
            }
        } else {
            $query = $query1 . " union all " . $query2;

            if(!empty($request->sorting_filter)){
                if($request->sorting_filter == '1') {
                    $query .= " order by id asc ";
                }elseif($request->sorting_filter == '2'){
                    $query .= " order by id desc ";
                }
            }else{
                $query .= " order by id desc, account_id asc";
            }
        }

        $data = DB::select($query);
        $data = Helper::arrayPaginator_20($data, $request);
        $carriers = VRProduct::select('carrier')->whereNotNull('carrier')->groupBy('carrier')->get();

        return view('admin.settings.vr-product-price', [
            'data'          => $data,
            'vr_prod_id'    => $request->vr_prod_id,
            'account_id'    => $request->account_id,
            'carrier'       => $request->carrier,
            'model'         => $request->model,
            'desc'          => $request->desc,
            'default'       => $request->default,
            'acct_type'     => $request->acct_type,
            'min'           => $request->min,
            'max'           => $request->max,
            'sdate_c'       => $request->sdate_c,
            'edate_c'       => $request->edate_c,
            'prod_status'   => $request->prod_status,
            'sorting_filter' => $request->sorting_filter,
            'carriers'      => $carriers
        ]);
    }

    public function show_detail(Request $request) {
        try{
            $product = VRProduct::find($request->prod_id);

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

    public function show_detail_price(Request $request) {
        try{
            $vpp = VRProductPrice::find($request->prod_price_id);

            if (empty($vpp)) {
                return response()->json([
                    'msg' => 'Product Price not available !!'
                ]);
            }

            return response()->json([
                'msg'   => '',
                'data'  => $vpp
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_product_price(Request $request) {
        try{

            $vrpp = VRProductPrice::where('id', $request->product_price_id)->first();

            if(!empty($request->expired_date)) {

                if(strlen($request->expired_date) > 10){
                    $vrpp->expired_date = $request->expired_date;
                }else {
                    $vrpp->expired_date = $request->expired_date . ' 23:59:59';
                }
            }

            if(!empty($request->quick_note)) {
                $vrpp->quick_note = $request->quick_note;
            }

            $vrpp->m_price = str_replace(",", "", $request->master_price);
            $vrpp->d_price = str_replace(",", "", $request->distributor_price);
            $vrpp->s_price = str_replace(",", "", $request->subagent_price);

            $vrpp->m_commission = str_replace(",", "", $request->master_commission);
            $vrpp->d_commission = str_replace(",", "", $request->distributor_commission);

            $vrpp->marketing = $request->marketing;
            $vrpp->rebate_marketing = $request->rebate_marketing;

            if($request->is_free_ship == 'Y'){
                $vrpp->is_free_ship = 'Y';
            }else{
                $vrpp->is_free_ship = null;
            }

            $vrpp->min_quan = $request->min_quan;
            $vrpp->max_quan = $request->max_quan;

            $vrpp->save();

            return response()->json([
                'msg' => ''
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function account_check(Request $request) {
        try{

            $account_info = Account::where('id', $request->account_id)->where('status', 'A')->first();

            if (empty($account_info)) {
                return response()->json([
                    'msg' => 'Account is not available !!'
                ]);
            }

            $acct_str = "[".$account_info->type. "] : " . $account_info->name;

            if($account_info->type == 'M'){
                $acct_str = "[L] Perfect Mobile | ". $acct_str;
            }else {
                $parent_info = Account::find($account_info->parent_id);
                if (!empty($parent_info)) {
                    $acct_str = "[" . $parent_info->type . "] : " . $parent_info->name . " | " . $acct_str;
                }

                $master_info = Account::find($account_info->master_id);
                if (!empty($master_info)) {
                    if ($master_info->id != $parent_info->id) {
                        $acct_str = "[" . $master_info->type . "] : " . $master_info->name . " | " . $acct_str;
                    }
                }
            }

            $a_type = Helper::get_hierarchy_img($account_info->type);



            return response()->json([
                'msg'   => '',
                'data'  => [
                    'account_info'  => $account_info,
                    'acct_str'     => $acct_str
                    ]
            ]);


        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function assign(Request $request) {
        try{

            if (!empty($request->account_ids)){

                foreach(preg_split("/((\r?\n)|(\r\n?))/", $request->account_ids) as $line){

                    $acct_obj = Account::where('id', $line)->first();

                    if(empty($acct_obj)){
                        return response()->json([
                            'msg' => $line . ' - Account is not available !!'
                        ]);
                    }

                    $ret = VRProductPrice::where('vr_prod_id', $request->product_id)->where('account_id', $line)->first();
                    if(!empty($ret)){
                        $ret->delete();
                    }

                    $vrpp = new VRProductPrice;

                    $vrpp->vr_prod_id   = $request->product_id;
                    $vrpp->account_id   = $line;

                    $vrpp->m_price = str_replace(",", "", $request->master_price);
                    $vrpp->d_price = str_replace(",", "", $request->distributor_price);
                    $vrpp->s_price = str_replace(",", "", $request->subagent_price);

                    $vrpp->m_commission = str_replace(",", "", $request->master_commission);
                    $vrpp->d_commission = str_replace(",", "", $request->distributor_commission);

                    $vrpp->marketing = $request->marketing;
                    $vrpp->rebate_marketing = $request->rebate_marketing;

                    $vrpp->min_quan = $request->min_quan;
                    $vrpp->max_quan = $request->max_quan;

                    if($request->is_free_ship == 'Y'){
                        $vrpp->is_free_ship = 'Y';
                    }

                    if(!empty($request->expired_date)) {
                        $vrpp->expired_date = $request->expired_date . ' 23:59:59';
                    }

                    if(!empty($request->quick_note)) {
                        $vrpp->quick_note = $request->quick_note;
                    }

                    $vrpp->cdate = Carbon::now();

                    $vrpp->save();
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

    public function delete(Request $request) {
        try{

            $vr_product_price_id = $request->vr_product_price_id;
            VRProductPrice::where('id', $vr_product_price_id)->delete();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function delete_all(Request $request) {
        try{

            $vr_prod_id = $request->vr_prod_id;
            VRProductPrice::where('vr_prod_id', $vr_prod_id)->delete();

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