<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/26/17
 * Time: 4:24 PM
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\AccountShipFee;
use App\Model\Credit;
use App\Model\VRProductPrice;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;
use Validator;
use App\Model\VirtualRep;
use App\Model\VRProduct;
use App\Model\VRRequest;
use App\Model\VRPayment;
use App\Model\VRRequestProduct;

class VirtualRepController extends Controller
{

    public function shop(Request $request) {

        $account_id = Auth::user()->account_id;

        $acct_obj = Account::find($account_id);

        $account_type = $acct_obj->type;

        if($account_type == 'D'){
            $master_id = $acct_obj->master_id;
        }

        $condition = '';

        if (!empty($request->quick_search)) {
            $condition .= ' and (upper(a.model) like "%' . strtoupper($request->quick_search). '%" or upper(a.type) like "%' . strtoupper($request->quick_search). '%" or upper(a.carrier) like "%' . strtoupper($request->quick_search). '%" or upper(a.sub_carrier) like "%' . strtoupper($request->quick_search). '%" or upper(a.category) like "%' . strtoupper($request->quick_search). '%" or upper(a.sub_category) like "%' . strtoupper($request->quick_search). '%" or upper(a.id) like "%' . strtoupper($request->quick_search). '%" or upper(a.make) like "%' . strtoupper($request->quick_search). '%") ';
        }

        if (!empty($request->carrier)) {
            $condition .= ' and upper(a.carrier) like "%' . strtoupper($request->carrier) . '%" ';

        }

        if (!empty($request->sub_carrier)) {
            $condition .= ' and upper(a.sub_carrier) like "%' . strtoupper($request->sub_carrier) . '%" ';
        }

        if (!empty($request->category)) {
            $condition .= ' and upper(a.category) like "%' . strtoupper($request->category) . '%" ';
        }

        if (!empty($request->sub_category)) {
            $condition .= ' and upper(a.sub_category) like "%' . strtoupper($request->sub_category) . '%" ';
        }

        if (!empty($request->service_month)) {
            $condition .= ' and upper(a.service_month) like "%' . strtoupper($request->service_month) . '%" ';
        }

        if (!empty($request->plan)) {
            $condition .= ' and a.plan ="' . $request->plan .'" ';
        }

        if (!empty($request->make)) {
            $condition .= ' and a.make ="' . $request->make .'" ';
        }

        if (!empty($request->model)) {
            $condition .= ' and upper(a.model) like "%' . strtoupper($request->model) . '%" ';
        }

        if (!empty($request->type)) {
            $condition .= ' and a.type ="' . $request->type .'" ';
        }

        if (!empty($request->grade)) {
            $condition .= ' and a.grade ="' . $request->grade .'" ';
        }

        if (!empty($request->promotion)) {
            $condition .= ' and a.promotion ="' . $request->promotion .'" ';
        }

        if (!empty($request->min)) {
            if($account_type == 'D') {
                $condition .= ' and  IfNull(d.d_price, IfNull(m.d_price, a.distributor_price) ) >="' . $request->min . '" ';
            }elseif ($account_type == 'M'){
                $condition .= ' and IfNull(m.m_price, a.master_price) >="' . $request->min . '" ';
            }
        }

        if (!empty($request->max)) {
            if($account_type == 'D') {
                $condition .= ' and  IfNull(d.d_price, IfNull(m.d_price, a.distributor_price) ) <="' . $request->max . '" ';
            }elseif ($account_type == 'M'){
                $condition .= ' and IfNull(m.m_price, a.master_price) <="' . $request->max . '" ';
            }
        }

        if ($account_type == 'D'){

            $data = DB::select("
                select  a.*, 
                        d.m_price dis_m_price, d.d_price dis_d_price, d.s_price dis_s_price, d.m_commission dis_m_commission, d.d_commission dis_d_commission, d.min_quan dis_min_quan, d.max_quan dis_max_quan,f_get_vr_order_qty(:account_id_1, a.id) d_ordered_qty,
                        m.m_price mas_m_price, m.d_price mas_d_price, m.s_price mas_s_price, m.m_commission mas_m_commission, m.d_commission mas_d_commission, m.min_quan mas_min_quan, m.max_quan mas_max_quan,f_get_vr_order_qty(:master_id_1, a.id) m_ordered_qty,
                        IfNull(d.quick_note, IfNull(m.quick_note, '')) final_quick_note,
                        IfNull(d.d_price, IfNull(m.d_price, a.distributor_price) ) dis_price_final,
                        IfNull(d.max_quan, IfNull(m.max_quan, a.max_quantity)) dis_max_final,
                        IfNull(d.marketing, IfNull(m.marketing, a.marketing)) marketing_final,
                        IfNull(d.rebate_marketing, IfNull(m.rebate_marketing, a.rebate_marketing)) rebate_marketing_final
                from vr_product a left join vr_product_price d on a.id = d.vr_prod_id and d.account_id= :account_id_2
                                  left join vr_product_price m on a.id = m.vr_prod_id and d.account_id= :master_id_2
                where a.status ='A'
                and a.exclude_all_dis is null
                and not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='E' and account_id = :account_id_3)  
                and not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='E' and account_id = :master_id_3)
                and (   ( not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' )   
                           or  exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' and account_id = :account_id_4) )    
                     or ( not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' )   
                           or  exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' and account_id = :master_id_4) )    
                ) 
                " . $condition ."
                order by a.sorting, a.category, a.sub_category, a.carrier, a.sub_carrier, a.model                 
                ;", [
                'account_id_1'    => $account_id,
                'master_id_1'     => $master_id,
                'account_id_2'    => $account_id,
                'master_id_2'     => $master_id,
                'account_id_3'    => $account_id,
                'master_id_3'     => $master_id,
                'account_id_4'    => $account_id,
                'master_id_4'     => $master_id
            ]);

        }elseif ($account_type == 'M'){

            $data = DB::select("
                select  a.*, 
                        m.m_price mas_m_price, m.d_price mas_d_price, m.s_price mas_s_price, m.m_commission mas_m_commission, m.d_commission mas_d_commission, m.min_quan mas_min_quan, m.max_quan mas_max_quan,f_get_vr_order_qty(:account_id_1, a.id) m_ordered_qty,
                        IfNull(m.quick_note, '') final_quick_note,
                        IfNull(m.m_price, a.master_price) mas_price_final,
                        IfNull(m.max_quan, a.max_quantity) mas_max_final,
                        IfNull(m.marketing, a.marketing) marketing_final,
                        IfNull(m.rebate_marketing, a.rebate_marketing) rebate_marketing_final
                from vr_product a left join vr_product_price m on a.id = m.vr_prod_id and m.account_id= :account_id_2
                where a.status ='A'
                and a.exclude_all_mas is null
                and not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='E' and account_id = :account_id_3)  
                and ( not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' )   
                       or  exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' and account_id = :account_id_4) )        
                " . $condition ."
                order by a.sorting, a.category, a.sub_category, a.carrier, a.sub_carrier, a.model                 
                ;", [
                'account_id_1'    => $account_id,
                'account_id_2'    => $account_id,
                'account_id_3'    => $account_id,
                'account_id_4'    => $account_id
            ]);
        }

        $total_num = sizeof($data);

        $carriers = VRProduct::select('carrier')->where('status', 'A')->whereNotNull('carrier')->groupBy('carrier')->get();
        $sub_carriers = VRProduct::select('sub_carrier')->where('status', 'A')->whereNotNull('sub_carrier')->groupBy('sub_carrier')->get();
        $categories = VRProduct::select('category')->where('status', 'A')->whereNotNull('category')->groupBy('category')->get();
        $sub_categories = VRProduct::select('sub_category')->where('status', 'A')->whereNotNull('sub_category')->groupBy('sub_category')->get();
        $service_months = VRProduct::select('service_month')->where('status', 'A')->whereNotNull('service_month')->groupBy('service_month')->get();
        $plans = VRProduct::select('plan')->where('status', 'A')->whereNotNull('plan')->groupBy('plan')->get();
        $makes = VRProduct::select('make')->where('status', 'A')->whereNotNull('make')->groupBy('make')->get();
        $models = VRProduct::select('model')->where('status', 'A')->whereNotNull('model')->groupBy('model')->get();
        $types = VRProduct::select('type')->where('status', 'A')->whereNotNull('type')->groupBy('type')->get();
        $grades = VRProduct::select('grade')->where('status', 'A')->whereNotNull('grade')->groupBy('grade')->get();
        $promotions = VRProduct::select('promotion')->where('status', 'A')->whereNotNull('promotion')->groupBy('promotion')->get();

        return view('admin.virtual-rep.shop', [
            'vr_product' => $data,
            'account_id' =>$account_id,
            'min'   => $request->min,
            'max'   => $request->max,
            'promotion' => $request->promotion,
            'quick_search' => $request->quick_search,
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
            'promotions' => $promotions,
            'account_type' => $account_type,
            'total_num' => $total_num
        ]);
    }

    public function shop_old(Request $request) {
        //$vr_product = VRProduct::all();

        $account_id = Auth::user()->account_id;
        $account = Account::find($account_id);

        $sdate = null;
        $edate = null;

        if (!empty($request->sdate)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $data = VRProduct::query();

        if (!empty($sdate)) {
            $data = $data->whereRaw('upload_date >= ?', [$sdate]);
        }

        if (!empty($edate)) {
            $data = $data->whereRaw('upload_date <= ?', [$edate]);
        }

        if (!empty($request->min)) {
            if ($account->type == 'M') {
                $data = $data->whereRaw('master_price >= ?', [$request->min]);
            }else{
                $data = $data->whereRaw('distributor_price >= ?', [$request->min]);
            }
        }

        if (!empty($request->max)) {
            if ($account->type == 'M') {
                $data = $data->whereRaw('master_price <= ?', [$request->max]);
            }else{
                $data = $data->whereRaw('distributor_price <= ?', [$request->max]);
            }
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
            $data = $data->whereRaw('upper(desc) like ?', ['%' . strtoupper($request->desc). '%']);
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

        if (!empty($request->quick_search)) {
            $data = $data->whereRaw('(upper(model) like \'%' . strtoupper($request->quick_search). '%\' or upper(type) like \'%' . strtoupper($request->quick_search). '%\' or upper(carrier) like \'%' . strtoupper($request->quick_search). '%\' or upper(sub_carrier) like \'%' . strtoupper($request->quick_search). '%\' or upper(category) like \'%' . strtoupper($request->quick_search). '%\' or upper(sub_category) like \'%' . strtoupper($request->quick_search). '%\' or upper(make) like \'%' . strtoupper($request->quick_search). '%\')');
        }

        $data = $data->where('status', 'A')
//            ->whereRaw('carrier not in (select carrier from account_vr_auth where account_id = ' . $account->master_id . ')')
            ->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')
            ->orderBy('sorting', 'asc')
            ->orderBy('category', 'asc')
            ->orderBy('sub_category', 'asc')
            ->orderBy('carrier', 'asc')
            ->orderBy('sub_carrier', 'asc')
            ->orderBy('model', 'asc')
            ->paginate(40);

        $carriers = VRProduct::select('carrier')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('carrier')->groupBy('carrier')->get();
        $sub_carriers = VRProduct::select('sub_carrier')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('sub_carrier')->groupBy('sub_carrier')->get();
        $categories = VRProduct::select('category')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('category')->groupBy('category')->get();
        $sub_categories = VRProduct::select('sub_category')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('sub_category')->groupBy('sub_category')->get();
        $service_months = VRProduct::select('service_month')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('service_month')->groupBy('service_month')->get();
        $plans = VRProduct::select('plan')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('plan')->groupBy('plan')->get();
        $makes = VRProduct::select('make')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('make')->groupBy('make')->get();
        $models = VRProduct::select('model')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('model')->groupBy('model')->get();
        $types = VRProduct::select('type')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('type')->groupBy('type')->get();
        $grades = VRProduct::select('grade')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('grade')->groupBy('grade')->get();
        $promotions = VRProduct::select('promotion')->where('status', 'A')->whereRaw('id not in (select vr_product_id from account_vr_auth where account_id = ' . $account->master_id . ')')->whereNotNull('promotion')->groupBy('promotion')->get();

        return view('admin.virtual-rep.shop', [
            'vr_product' => $data,
            'account_id' =>$account_id,
            'sdate' => $sdate ? $sdate->format('Y-m-d') : null,
            'edate' => $edate ? $edate->format('Y-m-d') : null,
            'min'   => $request->min,
            'max'   => $request->max,
            'promotion' => $request->promotion,
            'quick_search' => $request->quick_search,
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
            'promotions' => $promotions,
            'account_type'    => $account->type
        ]);
    }

    public function save(Request $request) {

        DB::beginTransaction();

        try {

            switch ($request->vr_category) {
                case 'O':
                    if (trim($request->vr_order) == '' && empty($request->products)) {
                        return response()->json([
                            'msg' => 'Please select at least one item or enter your query'
                        ]);
                    }
                    break;
                case 'C':
                    if (trim($request->vr_comments) == '') {
                        return response()->json([
                            'msg' => 'Please leave your opinion in the comment box'
                        ]);
                    }
                    break;
                default:
                    return response()->json([
                        'msg' => 'Please select category of your question first'
                    ]);
            }

            $vr = new VRRequest();
            $vr->account_id = Auth::user()->account_id;
            $vr->category = $request->vr_category;

            if ($request->vr_category == 'O') {
                // order
                $vr->order = $request->vr_order;
                $vr->price = $request->vr_price;
                $vr->total = $request->vr_price;
                $vr->pay_method = $request->pay_method;
            } else {
                // general request
                $vr->comments = $request->vr_comments;
            }

            $vr->created_by = Auth::user()->user_id;
            $vr->cdate = Carbon::now();
            $vr->status = 'RQ';
            $vr->save();


            // order products
            if ($request->vr_category == 'O') {

                $products = $request->products;

                foreach ($products as $o) {
                    $vrp = new VRRequestProduct();
                    $vrp->vr_id = $vr->id;
                    $vrp->prod_sku = $o[0];
                    $vrp->order_price = $o[1];
                    $vrp->qty = $o[2];
                    $vrp->sales_type = 'S';
                    $vrp->cdate = Carbon::now();
                    $vrp->created_by = Auth::user()->user_id;
                    $vrp->save();
                }
            }

            if (getenv('APP_ENV') == 'local') {
                $ret = Helper::send_mail('it@jjonbp.com', '[PM] [' . getenv('APP_ENV') . '] New V.R. Request', ' - REQ.ID: ' . $vr->id);
            } else {
                $ret = Helper::send_mail('ops@softpayplus.com', '[PM] [' . getenv('APP_ENV') . '] New V.R. Request', ' - REQ.ID: ' . $vr->id);
            }

            if (!empty($ret)) {
                Helper::log('### SEND MAIL ERROR ###', [
                    'msg' => $ret
                ]);
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

    public function add_to_cart(Request $request) {

        try {

            $vr_product = VRProduct::find($request->id);
            if (empty($vr_product)) {
                return response()->json([
                    'msg' => 'The product is not available !!'
                ]);
            }

            $account_id = Auth::user()->account_id;
            $account = Account::find($account_id);

            $parent_id =  $account->parent_id;
            $master_id =  $account->master_id;

            if (!empty($vr_product->forever_quantity)) {
                $num_total = VRRequestProduct::get_buy_num($account_id, $vr_product->id);
                if ($num_total + $request->qty > $vr_product->forever_quantity) {
                    $result = $vr_product->forever_quantity - $num_total;
//                    return response()->json([
//                        'msg' => 'You can not order. Limit up to ' . $result
//                    ]);
                    if($result >0 ){
                        $msg = "We are sorry, but you can purchase this item up to " . $result . ' Quantity' ;
                    }else if($result == 0) {
                        $msg = "We are sorry, but you have already purchased this item before." ;
                    }else {
                        $msg = "We are sorry, but you have already purchased this item before." ;
                    }
                    return response()->json([
                        'msg' => $msg
                    ]);
                }
            }

            $ret = VRProductPrice::get_price_by_account($account_id, $vr_product->id);

            if(!empty($ret)){
                if ($request->account_type == 'M') {
                    $price = $ret->m_price;
                }else{
                    $price = $ret->d_price;
                }
            } else {
                if ($request->account_type == 'M') {
                    $price = $vr_product->master_price;
                }else{
                    $price = $vr_product->distributor_price;
                }
            }

            // check for item allow or not with min condition
            $vpp = VRProductPrice::where('account_id', $account_id)->where('vr_prod_id', $vr_product->id)->first();
            if(!empty($vpp->min_quan)){
                $num_of_month = VRProductPrice::get_buy_num_of_month($account_id, $vr_product->id);
                // Min Check
                if( ($num_of_month + $request->qty) < $vpp->min_quan ) {
                    $result = $vpp->min_quan - $num_of_month;
                    return response()->json([
                        'msg'  => 'The minimum order for the account is ' . $result
                    ]);
                }
            }

            if($account->type == 'D'){

                $data = DB::select("
                select  a.*, 
                        d.m_price dis_m_price, d.d_price dis_d_price, d.s_price dis_s_price, d.m_commission dis_m_commission, d.d_commission dis_d_commission, d.min_quan dis_min_quan, d.max_quan dis_max_quan,f_get_vr_order_qty(:account_id_1, a.id) d_ordered_qty,
                        m.m_price mas_m_price, m.d_price mas_d_price, m.s_price mas_s_price, m.m_commission mas_m_commission, m.d_commission mas_d_commission, m.min_quan mas_min_quan, m.max_quan mas_max_quan,f_get_vr_order_qty(:master_id_1, a.id) m_ordered_qty,
                        IfNull(d.quick_note, IfNull(m.quick_note, '')) final_quick_note,
                        IfNull(d.d_price, IfNull(m.d_price, a.distributor_price) ) dis_price_final,
                        IfNull(d.max_quan, IfNull(m.max_quan, a.max_quantity)) dis_max_final
                from vr_product a left join vr_product_price d on a.id = d.vr_prod_id and d.account_id= :account_id_2
                                  left join vr_product_price m on a.id = m.vr_prod_id and d.account_id= :master_id_2
                where a.status ='A'
                and not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='E' and account_id = :account_id_3)  
                and not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='E' and account_id = :master_id_3)
                and (   ( not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' )   
                           or  exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' and account_id = :account_id_4) )    
                     or ( not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' )   
                           or  exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' and account_id = :master_id_4) )    
                ) 
                and a.id = :vr_prod_id               
                ;", [
                    'account_id_1'    => $account_id,
                    'master_id_1'     => $master_id,
                    'account_id_2'    => $account_id,
                    'master_id_2'     => $master_id,
                    'account_id_3'    => $account_id,
                    'master_id_3'     => $master_id,
                    'account_id_4'    => $account_id,
                    'master_id_4'     => $master_id,
                    'vr_prod_id'      => $vr_product->id
                ]);

                if($data[0]->dis_max_final === 0){
                    return response()->json([
                        'msg'  => 'You cannot order this item now. Please contact us.'
                    ]);
                }elseif(!empty($data[0]->dis_max_final)){
                    if( $data[0]->d_ordered_qty + $request->qty > $data[0]->dis_max_final ) {
                        $result = $data[0]->dis_max_final - $data[0]->d_ordered_qty ;
                        return response()->json([
                            'msg'  => 'The maximum order for the account is ' . $result
                        ]);
                    }
                }

            } else {

                $data = DB::select("
                select  a.*, 
                        m.m_price mas_m_price, m.d_price mas_d_price, m.s_price mas_s_price, m.m_commission mas_m_commission, m.d_commission mas_d_commission, m.min_quan mas_min_quan, m.max_quan mas_max_quan,f_get_vr_order_qty(:account_id_1, a.id) m_ordered_qty,
                        IfNull(m.quick_note, '') final_quick_note,
                        IfNull(m.m_price, a.master_price) mas_price_final,
                        IfNull(m.max_quan, a.max_quantity) mas_max_final
                from vr_product a left join vr_product_price m on a.id = m.vr_prod_id and m.account_id= :account_id_2
                where a.status ='A'
                and not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='E' and account_id = :account_id_3)  
                and ( not exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' )   
                       or  exists ( select id from account_vr_auth where vr_product_id = a.id and type ='I' and account_id = :account_id_4) )        
                and a.id = :vr_prod_id                
                ;", [
                    'account_id_1'    => $account_id,
                    'account_id_2'    => $account_id,
                    'account_id_3'    => $account_id,
                    'account_id_4'    => $account_id,
                    'vr_prod_id'      => $vr_product->id
                ]);

                if($data[0]->mas_max_final === 0){
                    return response()->json([
                        'msg'  => 'You cannot order this item now. Please contact us.'
                    ]);
                }elseif(!empty($data[0]->mas_max_final)){
                    if( $data[0]->m_ordered_qty + $request->qty > $data[0]->mas_max_final ) {
                        $result = $data[0]->mas_max_final - $data[0]->m_ordered_qty ;
                        return response()->json([
                            'msg'  => 'The maximum order for the account is ' . $result
                        ]);
                    }
                }
            }

            $vr = VRRequest::where('account_id', Auth::user()->account_id)->where('status', 'CT')->first();
            if (empty($vr)) {
                $vr = new VRRequest();
                $vr->category = 'O';
                $vr->account_id = Auth::user()->account_id;
                $vr->status = 'CT';
                $vr->created_by = Auth::user()->user_id;
                $vr->cdate = Carbon::now();
                $vr->save();
            }

            $vrp = VRRequestProduct::where('vr_id', $vr->id)->where('prod_id', $request->id)->first();
            if (empty($vrp)) {
                $vrp = new VRRequestProduct();
                $vrp->vr_id = $vr->id;
                $vrp->prod_id = $request->id;
                $vrp->prod_sku = $vr_product->sku;
                $vrp->order_price = $request->qty * $price;
                $vrp->qty = $request->qty;
                $vrp->sales_type = 'S';
                $vrp->cdate = Carbon::now();
                $vrp->created_by = Auth::user()->user_id;
                $vrp->quick_note = $data[0]->final_quick_note ;
                $vrp->save();
            } else {
                $vrp->order_price = $request->qty * $price;
                $vrp->qty = $request->qty;
                $vrp->cdate = Carbon::now();
                $vrp->created_by = Auth::user()->user_id;
                $vrp->quick_note = $data[0]->final_quick_note ;
                $vrp->update();
            }

            $vr->price = VRRequestProduct::where('vr_id', $vr->id)->sum('order_price');

            if($vr->shipping_method == 'P'){
                $vr->shipping = 0;
            } else {

                // check if this account has account shipping fee set up.
                $ship_fee = AccountShipFee::where('account_id', $account_id)
                    ->where('min_amt', '<=', $vr->price)
                    ->where('max_amt', '>', $vr->price)
                    ->first();

                if (!empty($ship_fee)) {
                    $vr->shipping = $ship_fee->fee;

                } else {
                    $ship_fee = AccountShipFee::whereRaw('account_id is null')
                        ->where('min_amt', '<=', $vr->price)
                        ->where('max_amt', '>', $vr->price)
                        ->first();

                    $vr->shipping = $ship_fee->fee;
                }
            }

            $vr->total = $vr->price + $vr->shipping;
            $vr->update();

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

    public function cart() {
        $account_id = Auth::user()->account_id;
        $vr = VRRequest::where('account_id', $account_id)->where('status', 'CT')->first();

        $account = Account::find($account_id);

        $vrp = null;

        if (!empty($vr)) {

            $vrp = VRRequestProduct::where('vr_id', $vr->id)->get();

            $free_shipping = true ;
            $shippingfee_is_set = false;

            if(!empty($vrp)){
                foreach ($vrp as $o) {

                    // checking is_free_shipping in vr_product
                    $product = VRProduct::find($o->prod_id);

                    // checking is_free_ship in vr_product_price
                    $vpp = VRProductPrice::where('account_id', $account_id)->where('vr_prod_id', $o->prod_id)->first();
                    if( !empty($vpp)  ){
                        if( $vpp->is_free_ship != 'Y' ){
                            if(!$shippingfee_is_set){
                                $free_shipping = false;
                            }
                        }
                    }else {
                        if($product->is_free_shipping != 'Y'){
                            if(!$shippingfee_is_set) {
                                $free_shipping = false;
                            }
                        }
                    }

                    if (empty($product) ||
                        (($product->sku != 'SHIPPINGFEE') && ($product->status != 'A' || $product->stock < 1))) {
                        $o->order_price = 0;
                        $o->qty = 0;
                        $o->delete();

                    } else {
                        if ($product->sku == 'SHIPPINGFEE') {
                            if($account->type == 'M') {
                                $product->master_price = $o->order_price;
                            }else{
                                $product->distributor_price = $o->order_price;
                            }
                            $free_shipping = true; //Because order_price is setup manually by admin, and no more eshipping fee.
                            $shippingfee_is_set = true; //flag no more 'False' by another items.
                        } else {

                            $ret = VRProductPrice::get_price_by_account($account_id, $o->prod_id);

                            if(!empty($ret)){
                                if ($account->type == 'M') {
                                    $price = $ret->m_price;
                                }else{
                                    $price = $ret->d_price;
                                }
                            }else {
                                if ($account->type == 'M') {
                                    $price = $product->master_price;
                                }else{
                                    $price = $product->distributor_price;
                                }
                            }

                            if ($o->order_price != $o->qty * $price) {
                                $o->order_price = $o->qty * $price;
                                $o->update();
                            }
                        }
                        $o->product = $product;
                    }
                }

                $vr->price  = VRRequestProduct::where('vr_id', $vr->id)->sum('order_price');
            }else {
                $vr->price  = 0;
            }


            // checking shipping = $0, if true one of them
            if($free_shipping){
                $vr->shipping = 0;
            } else {
                if($vr->shipping_method == 'P') { //Pickup in store.
                    $vr->shipping = 0;
                } else {
                    // check account shipping fee
                    $ship_fee = AccountShipFee::where('account_id', $account_id)
                        ->where('min_amt', '<=', $vr->price)
                        ->where('max_amt', '>', $vr->price)
                        ->first();
                    if (!empty($ship_fee)) {
                        $vr->shipping = $ship_fee->fee;
                    } else {
                        $ship_fee = AccountShipFee::whereRaw('account_id is null')
                            ->where('min_amt', '<=', $vr->price)
                            ->where('max_amt', '>', $vr->price)
                            ->first();
                        $vr->shipping = $ship_fee->fee;
                    }
                }
            }

            $vr->total  = $vr->price + $vr->shipping;
            $vr->update();
        }

        return view('admin.virtual-rep.cart')->with([
            'vr'            => $vr,
            'vrp'           => $vrp,
            'account_type'  => $account->type,
            'account'       => $account
        ]);
    }

    public function cart_remove($id) {

        $vrp = VRRequestProduct::find($id);

        if (!empty($vrp)) {
            $vr = VRRequest::find($vrp->vr_id);

            $vrp->delete();

            $count = VRRequestProduct::where('vr_id', $vr->id)->count();
            if ($count < 1) {
                $vr->delete();
            } else {
                $vr->price = VRRequestProduct::where('vr_id', $vr->id)->sum('order_price');
                $vr->shipping = $vr->price < 300 ? 10 : 0;
                $vr->total = $vr->price + $vr->shipping;
                $vr->update();
            }

        }

        return redirect("/admin/virtual-rep/cart");
    }

    public function cart_update(Request $request) {

        $vr = VRRequest::where('account_id', Auth::user()->account_id)->where('status', 'CT')->first();

        if (!empty($vr)) {
            $vr->comments = $request->comments;
            $vr->update();
        }

        return redirect("/admin/virtual-rep/cart");
    }

    public function cart_paid(Request $request) {

        return response()->json([
            'msg'   => ''
        ]);

//        try {
//            $vr = VRRequest::where('account_id', Auth::user()->account_id)->where('id', $request->vr_id)->first();
//
//            if (empty($vr)) {
//                return response()->json([
//                    'msg'   => 'Invalid VR ID provided'
//                ]);
//            }
//
//            if ($vr->status != 'CT') {
//                return response()->json([
//                    'msg'   => 'Please contact SoftPayPlus.'
//                ]);
//            }
//
//            if ($request->amt != $vr->total) {
//                return response()->json([
//                    'msg' => 'Order amount and payment amount are not match'
//                ]);
//            }
//
//            $payment = new VRPayment;
//            $payment->vr_id = $vr->id;
//            $payment->account_id = Auth::user()->account_id;
//            $payment->type = 'PayPal'; # paypal always for now.
//            $payment->amt = $request->amt;
//            $payment->comments = $request->comments;
//
//            $payment->payer_id = $request->payer_id;
//            $payment->payment_id = $request->payment_id;
//            $payment->payment_token = $request->payment_token;
//
//            $payment->created_by = Auth::user()->user_id;
//            $payment->cdate = Carbon::now();
//            $payment->save();
//
//            $vr->order = $request->order_notes;
//            $vr->promo_code = $request->promo_code;
//            $vr->pay_method = 'PayPal';
//            $vr->status = 'PC'; // Change status to 'Paid'
//            $vr->mdate = Carbon::now();
//            $vr->update();
//
//            # insert promotion
//            $res = Helper::addPromotion($vr->id);
//            if (!empty($res)) {
//                return response()->json([
//                    'msg' => $res
//                ]);
//            }
//
//            # Send payment success email to balance@softpayplus.com
//            $subject = "Success Payment - VR Request (Acct.ID : " . $payment->account_id . ", Amount : $" . $payment->amt . ")";
//            $msg = "<b>Success Payment</b> <br/><br/>";
//            $msg .= "Acct.ID - " . $payment->account_id . "<br/>";
//            $msg .= "VR.ID - " . $payment->vr_id . "<br/>";
//            $msg .= "Type - " . $payment->type . "<br/>";
//            $msg .= "Amount - $" . $payment->amt . "<br/>";
//            $msg .= "Comment - " . $payment->comments . "<br/>";
//            $msg .= "Payer.ID - " . $payment->payer_id . "<br/>";
//            $msg .= "Payment.ID - " . $payment->payment_id . "<br/>";
//            $msg .= "Payment.Token - " . $payment->payment_token . "<br/>";
//            $msg .= "Created.By - " . $payment->created_by . "<br/>";
//            $msg .= "Date - " . $payment->cdate . "<br/>";
//
//
//            if (getenv('APP_ENV') == 'production') {
//                Helper::send_mail('balance@softpayplus.com', $subject, $msg);
//            } else {
//                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
//            }
//            //Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
//
//
//            return response()->json([
//                'msg'   => ''
//            ]);
//
//        } catch (\Exception $ex) {
//            return response()->json([
//                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
//            ]);
//        }
    }

    public function cart_cod(Request $request) {

        try {
            $vr = VRRequest::where('account_id', Auth::user()->account_id)->where('status', 'CT')->first();

            $account_id = Auth::user()->account_id;
            $account = Account::find($account_id);
            $account_type = $account->type;

            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid VR ID provided'
                ]);
            }

            $vr->address1   = $request->address1;
            $vr->address2   = $request->address2;
            $vr->city       = $request->city;
            $vr->state      = $request->state;
            $vr->zip        = $request->zip;

            if ($request->payment_method == 'Balance') {

                $bill_date = Carbon::today()->startOfWeek()->copy()->addDays(7);
                $period_from = Carbon::today()->startOfWeek();
                $period_to = $bill_date->copy()->subDay();

                if ($account_type == 'M') {
                    $ret = PaymentProcessor::get_master_balance($vr->account_id, $bill_date, $period_from, $period_to);
                    $bill_amt = $ret['bill_amt'];
                }else{
                    $ret = PaymentProcessor::get_distributor_balance($vr->account_id, $bill_date, $period_from, $period_to);
                    $bill_amt = $ret['bill_amt'];
                }

                $available_balance = $account->credit_limit - $bill_amt;

                if ($available_balance < $vr->total) {
                    return response()->json([
                      'msg' => 'You don\'t have enough balance !!'
                    ]);
                }

                ### Debi from balance
                $credit = new Credit();
                $credit->account_id = $vr->account_id;
                $credit->type       = 'D';
                $credit->amt        = $vr->total;
                $credit->comments   = 'Order Payment [' . $vr->id . ']';
                $credit->created_by = Auth::user()->user_id;
                $credit->cdate      = Carbon::now();
                $credit->save();

                $payment = new VRPayment;
                $payment->vr_id     = $vr->id;
                $payment->account_id = $vr->account_id;
                $payment->type      = 'Balance';
                $payment->amt       = $vr->total;
                $payment->comments  = 'Balance Payment';
                $payment->created_by = Auth::user()->user_id;
                $payment->cdate     = Carbon::now();
                $payment->save();

                $vr->order      = $request->order_notes;
                $vr->promo_code = $request->promo_code;
                $vr->pay_method = 'Balance';
                $vr->status     = 'PC'; // Change status to 'Paid'
                $vr->mdate      = Carbon::now();
                $vr->update();

                # insert promotion
                $res = Helper::addPromotion($vr->id);
                if (!empty($res)) {
                    return response()->json([
                      'msg' => $res
                    ]);
                }

                # Send payment success email to balance@softpayplus.com
                $subject = "Success Payment - VR Request (Acct.ID : " . $payment->account_id . ", Amount : $" . $payment->amt . ")";
                $msg = "<b>Success Payment</b> <br/><br/>";
                $msg .= "Acct.ID - " . $payment->account_id . "<br/>";
                $msg .= "VR.ID - " . $payment->vr_id . "<br/>";
                $msg .= "Type - " . $payment->type . "<br/>";
                $msg .= "Amount - $" . $payment->amt . "<br/>";
                $msg .= "Comment - " . $payment->comments . "<br/>";
                $msg .= "Payer.ID - " . $payment->payer_id . "<br/>";
                $msg .= "Payment.ID - " . $payment->payment_id . "<br/>";
                $msg .= "Payment.Token - " . $payment->payment_token . "<br/>";
                $msg .= "Created.By - " . $payment->created_by . "<br/>";
                $msg .= "Date - " . $payment->cdate . "<br/>";
            } else {
                $vr->order = $request->order_notes;
                $vr->promo_code = $request->promo_code;
                $vr->pay_method = $request->payment_method;
                $vr->status = 'RQ'; // Change status to 'Paid'
                $vr->mdate = Carbon::now();
                $vr->update();

                # Send payment success email to balance@softpayplus.com
                $subject = "VR Request (Acct.ID : " . $vr->account_id . ")";
                $msg = "<b>Request</b> <br/><br/>";
                $msg .= "Acct.ID - " . $vr->account_id . "<br/>";
                $msg .= "VR.ID - " . $vr->id . "<br/>";
                $msg .= "Date - " . $vr->cdate . "<br/>";
                $msg .= "Amount - $" . $vr->total . "<br/>";
                $msg .= "Comment - " . $vr->order . "<br/>";
            }

            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('balance@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
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

    public function check_status(Request $request) {
        $vr = VRRequest::where('account_id', Auth::user()->account_id)->where('id', $request->vr_id)->first();

        if (empty($vr)) {
            return response()->json([
              'code' => '-1',
              'msg'  => 'Invalid VR ID provided'
            ]);
        }

        $vr->address1   = $request->address1;
        $vr->address2   = $request->address2;
        $vr->city       = $request->city;
        $vr->state      = $request->state;
        $vr->zip        = $request->zip;
        $vr->update();

        return response()->json([
            'code' => '0',
            'status'  => $vr->status
        ]);

    }

    public function contact_me(Request $request) {

        $account = Account::where('id', $request->vr_account_id)->first();

        $vr = VRRequest::where('account_id', $request->vr_account_id)->where('id', $request->vr_id)->first();

        $vr->contact_me = 'Y';
        $vr->save();

        $subject = "Contact Me Request From VR Cart (Acct.ID : " . $vr->account_id . ")";
        $msg = "<b>Contact Me</b> <br/><br/>";
        $msg .= "Acct.ID - " . $vr->account_id . "<br/>";
        $msg .= "Acct.Name - " . $account->name . "<br/>";
        $msg .= "VR.ID - " . $vr->id . "<br/>";
        $msg .= "Date - " . $vr->cdate . "<br/>";
        $msg .= "Amount - $" . $vr->total . "<br/>";
        $msg .= "Comment - " . $vr->order . "<br/>";

//        $ret = Helper::send_mail('ops@softpayplus.com', $subject, $msg);

//        $ret = Helper::send_mail('jin@jjonbp.com', $subject, $msg);
        if (!empty($ret)) {
            Helper::log('### SEND MAIL ERROR ###', [
                'msg' => $ret
            ]);
        }

        return response()->json([
            'code' => '0',
            'status'  => $vr->status
        ]);
    }

    public function general_request() {
        return view('admin.virtual-rep.general-request');
    }

    public function general_request_save(Request $request) {

        try {

            $vr = new VRRequest();
            $vr->account_id = Auth::user()->account_id;
            $vr->category = 'C';
            $vr->comments = $request->vr_comments;
            $vr->created_by = Auth::user()->user_id;
            $vr->cdate = Carbon::now();
            $vr->status = 'RQ';
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


    public function logRequest(Request $request) {

        try {

            switch ($request->category) {
                case 'M':
                    if (
                        $request->poster_vz != 'Y' &&
                        $request->poster_h2o != 'Y' &&
                        $request->poster_lyca != 'Y' &&
                        $request->poster_patriot != 'Y' &&
                        $request->brochure_vz != 'Y' &&
                        $request->brochure_h2o != 'Y' &&
                        $request->brochure_lyca != 'Y' &&
                        $request->brochure_patriot != 'Y' &&
                        trim($request->material_other) == ''
                    ) {
                        return response()->json([
                            'msg' => 'Please select at least one item or enter your query'
                        ]);
                    }
                    break;
                case 'E':
                    if (
                        $request->sim_vz != 'Y' &&
                        $request->sim_h2o != 'Y' &&
                        $request->sim_lyca != 'Y' &&
                        $request->sim_patriot != 'Y' &&
                        $request->handset_vz != 'Y' &&
                        $request->handset_h2o != 'Y' &&
                        $request->handset_lyca != 'Y' &&
                        $request->handset_patriot != 'Y' &&
                        trim($request->equipment_other) == ''
                    ) {
                        return response()->json([
                            'msg' => 'Please select at least one item or enter your query'
                        ]);
                    }
                    break;
                case 'T':
                    if (
                        trim($request->tech_vz) == '' &&
                        trim($request->tech_h2o) == '' &&
                        trim($request->tech_lyca) == '' &&
                        trim($request->tech_patriot) == '' &&
                        trim($request->tech_portal) == 'Y' &&
                        trim($request->tech_other) == ''
                    ) {
                        return response()->json([
                            'msg' => 'Please select at least one item or enter your query'
                        ]);
                    }
                    break;
                case 'C':
                    if (trim($request->comments) == '') {
                        return response()->json([
                            'msg' => 'Please leave your opinion in the comment box'
                        ]);
                    }

                    break;
                default:
                    return response()->json([
                        'msg' => 'Please select category of your question first'
                    ]);
            }

            $vr = new VirtualRep();
            $vr->category = $request->category;
            
            $vr->poster_vz = $request->poster_vz;
            $vr->poster_h2o = $request->poster_h2o;
            $vr->poster_lyca = $request->poster_lyca;
            $vr->poster_patriot = $request->patriot;
            $vr->brochure_vz = $request->brochure_vz;
            $vr->brochure_h2o = $request->brochure_h2o;
            $vr->brochure_lyca = $request->brochure_lyca;
            $vr->brochure_patriot = $request->patriot;
            $vr->material_other = $request->material_other;

            $vr->sim_vz = $request->sim_vz;
            $vr->sim_h2o = $request->sim_h2o;
            $vr->sim_lyca = $request->sim_lyca;
            $vr->sim_patriot = $request->patriot;
            $vr->handset_vz = $request->handset_vz;
            $vr->handset_h2o = $request->handset_h2o;
            $vr->handset_lyca = $request->handset_lyca;
            $vr->handset_patriot = $request->patriot;
            $vr->equipment_other = $request->material_other;

            $vr->tech_vz = $request->tech_vz;
            $vr->tech_h2o = $request->tech_h2o;
            $vr->tech_lyca = $request->tech_lyca;
            $vr->tech_patriot = $request->tech_patriot;
            $vr->tech_portal = $request->tech_portal;
            $vr->tech_other = $request->tech_other;

            $vr->comments = $request->comments;

            $vr->created_by = Auth::user()->user_id;
            $vr->cdate = Carbon::now();
            $vr->status = 'N';
            $vr->save();


            $ret = Helper::send_mail('ops@softpayplus.com', '[PM] [' . getenv('APP_ENV') . '] New V.R. Request', ' - REQ.ID: ' . $vr->id);
            if (!empty($ret)) {
                Helper::log('### SEND MAIL ERROR ###', [
                    'msg' => $ret
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

    public function shipping_method($method) {

        $vr = VRRequest::where('account_id', Auth::user()->account_id)->where('status', 'CT')->first();

        if (!empty($vr)) {
            $vr->shipping_method = $method;
            $vr->shipping = $vr->shipping_method == 'P' ? 0 : ($vr->price < 300 ? 10 : 0);
            $vr->total = $vr->price + $vr->shipping;
            $vr->update();
        }

        return redirect("/admin/virtual-rep/cart");
    }

}