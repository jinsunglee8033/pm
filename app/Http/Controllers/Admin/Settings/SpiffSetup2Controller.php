<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/24/17
 * Time: 11:48 AM
 */

namespace App\Http\Controllers\Admin\Settings;


use App\Http\Controllers\Controller;
use App\Model\Denom;
use App\Model\Product;
use App\Model\SpiffSetup;
use App\Model\SpiffSetupSpecial;
use App\Model\SpiffTemplate;
use App\Model\SpiffTemplateOwner;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SpiffSetup2Controller extends Controller
{

    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $query = SpiffSetup::join('product', 'product.id', 'spiff_setup.product_id')
            ->leftJoin('spiff_template', 'spiff_setup.template', '=', 'spiff_template.id')
            //->where('product.status', 'A')
            ->whereNotNull('spiff_setup.template');



        if(!empty($request->template)){
            if($request->template == 'M'){
                $query = $query->where('spiff_setup.account_type', $request->template);
            }elseif($request->template == 'D'){
                $query = $query->where('spiff_setup.account_type', $request->template);
            }elseif($request->template == 'S'){
                $query = $query->where('spiff_setup.account_type', $request->template);
            }else {
                $query = $query->where('spiff_setup.template', $request->template);
            }
        }

        if(!empty($request->product)) {
            $query = $query->where('spiff_setup.product_id', $request->product);
            $denoms = Denom::where('product_id', $request->product)->get();
            //Denom::where('status', 'A')->where('product_id', $request->product)->get();
        }

        if(!empty($request->denom)){
            $query = $query->where('spiff_setup.denom', $request->denom);
        }

        if(!empty($request->search_denom)){
            $query = $query->where('spiff_setup.denom', $request->search_denom);
        }

        if ($request->excel == 'Y') {
            $data = $query->select(
                    'spiff_setup.*',
                    'spiff_template.template as template_name',
                    'product.name as product_name'
                )->orderByRaw('case when spiff_setup.account_type = "M" then 1
                                when spiff_setup.account_type = "D" then 2
                                when spiff_setup.account_type = "S" then 3
                                end asc')
                ->orderBy('product.name', 'asc')
                ->orderByRaw('spiff_setup.denom asc, spiff_template.template asc')
                ->get();

            Excel::create('spiff_setup', function($excel) use($data) {

                $excel->sheet('SpiffSetup', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'ID' => $a->id,
                            'Template' => $a->template_name,
                            'Product' => $a->product_name,
                            'Amount($)' => number_format($a->denom, 2),
                            'Account.Type' => $a->account_type_name,
                            'Spiff.1st' => $a->spiff_1st,
                            'Spiff.2nd' => $a->spiff_2nd,
                            'Spiff.3rd' => $a->spiff_3rd,
                            'Residual' => $a->residual,
                            'AR' => $a->ar,
                            'Regular.Rebate.1st' => $a->regular_rebate_1st,
                            'Regular.Rebate.2nd' => $a->regular_rebate_2nd,
                            'Regular.Rebate.3rd' => $a->regular_rebate_3rd,
                            'BYOD.Rebate.1st' => $a->byod_rebate_1st,
                            'BYOD.Rebate.2nd' => $a->byod_rebate_2nd,
                            'BYOD.Rebate.3rd' => $a->byod_rebate_3rd,
                            'Download.Date' => date("m/d/Y h:i:s A")
                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->select(
                'spiff_setup.*',
                'spiff_template.template as template_name',
                'product.name as product_name'
            )->orderByRaw('case when spiff_setup.account_type = "M" then 1
                            when spiff_setup.account_type = "D" then 2
                            when spiff_setup.account_type = "S" then 3
                            end asc')
            ->orderBy('product.name', 'asc')
            ->orderByRaw('spiff_setup.denom asc, spiff_template.template asc')
            ->paginate(100);

        $products = Product::where('status', 'A')->where('activation', 'Y')->get();
        $templates = SpiffTemplate::orderByRaw(' case when account_type = "M" then 1
                                                        when account_type = "D" then 2
                                                        when account_type = "S" then 3
                                                        end asc, template asc')->get();



        return view('admin.settings.spiff-setup2', [
            'account_type' => $request->account_type,
            'carrier' => $request->carrier,
            'product_id' => $request->product_id,
            'data' => $data,
            'denom' => $request->denom,
            'denoms' => empty($denoms) ? null : $denoms,
            'template' => $request->template,
            'templates' => $templates,
            'product' => $request->product,
            'products' => $products,
            'search_denom' => $request->search_denom
        ]);

    }

    public function add_template(Request $request) {

        try {

            $res = SpiffTemplate::where('');

            $ss = new SpiffTemplate();
            $ss->account_id = '100000';
            $ss->account_type = $request->account_type;
            $ss->template = $request->template_name;
            $ss->cdate = Carbon::now();
            $ss->save();

            if (!empty($request->copy_from)) {
                $from = SpiffSetup::where('template', $request->copy_from)->get();
                foreach ($from as $f){
                    $new = $f->replicate();
                    $new->template = $ss->id;
                    $new->cdate = Carbon::now();
                    $new->save();
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

    public function load_template(Request $request) {
        try {
            $st = SpiffTemplate::where('id', $request->template_id)->first();
            return response()->json([
                'msg' => '',
                'temp_id' => $st->id,
                'temp_name' => $st->template,
                'temp_type' => $st->account_type
            ]);
        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function edit_template(Request $request) {
        try {

            $st = SpiffTemplate::where('id', $request->temp_id)->first();
            $st->template = $request->temp_name;
            $st->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function call_amount(Request $request) {

        $denoms = Denom::where('product_id', $request->product_id)
                ->where('status', 'A')
                ->get();

        return response()->json([
            'msg'       => '',
            'denoms'    => $denoms
        ]);
    }

    public function add_new_spiff(Request $request) {
        try {

            $amount = $request->amount;
            $amount_explode = explode('|', $amount);
            $denom_id = $amount_explode[0];
            $denom  = $amount_explode[1];

            if($request->template == 'M' || $request->template == 'D'|| $request->template == 'S'){

                $account_type = $request->template;
                $spiff_temps = SpiffTemplate::where('account_type', $account_type)->get();

                SpiffSetup::where('product_id', $request->product)
                    ->where('denom', $denom)
                    ->where('account_type', $account_type)
                    ->delete();

                foreach ($spiff_temps as $st){
                    $ss = new SpiffSetup();
                    $ss->template = $st->id;
                    $ss->product_id = $request->product;
//                    $ss->denom_id = $denom_id;
                    $ss->denom = $denom;
                    $ss->account_type = $account_type;
                    $ss->spiff_1st = $request->an_sp_1;
                    $ss->spiff_2nd = $request->an_sp_2;
                    $ss->spiff_3rd = $request->an_sp_3;
                    $ss->residual = $request->an_rs;
                    $ss->ar = $request->an_ar;
                    $ss->regular_rebate_1st = $request->an_rb_1;
                    $ss->regular_rebate_2nd = $request->an_rb_2;
                    $ss->regular_rebate_3rd = $request->an_rb_3;
                    $ss->byod_rebate_1st = $request->an_by_1;
                    $ss->byod_rebate_2nd = $request->an_by_2;
                    $ss->byod_rebate_3rd = $request->an_by_3;
                    $ss->created_by = Auth::user()->user_id;
                    $ss->cdate = Carbon::now();
                    $ss->save();
                }

            } else {
                $st = SpiffTemplate::where('id', $request->template)->first();
                $account_type = $st->account_type;

                $res = SpiffSetup::where('product_id', $request->product)
                    ->where('denom', $denom)
                    ->where('account_type', $account_type)
                    ->where('template', $request->template)
                    ->first();

                if (!empty($res)) {
                    $res->template = $request->template;
                    $res->product_id = $request->product;
                    $res->denom = $denom;
                    $res->account_type = $st->account_type;
                    $res->spiff_1st = $request->an_sp_1;
                    $res->spiff_2nd = $request->an_sp_2;
                    $res->spiff_3rd = $request->an_sp_3;
                    $res->residual = $request->an_rs;
                    $res->ar = $request->an_ar;
                    $res->regular_rebate_1st = $request->an_rb_1;
                    $res->regular_rebate_2nd = $request->an_rb_2;
                    $res->regular_rebate_3rd = $request->an_rb_3;
                    $res->byod_rebate_1st = $request->an_by_1;
                    $res->byod_rebate_2nd = $request->an_by_2;
                    $res->byod_rebate_3rd = $request->an_by_3;
                    $res->created_by = Auth::user()->user_id;
                    $res->cdate = Carbon::now();
                    $res->save();
                }else {
                    $ss = new SpiffSetup();
                    $ss->template = $request->template;
                    $ss->product_id = $request->product;
                    $ss->denom = $denom;
                    $ss->account_type = $st->account_type;
                    $ss->spiff_1st = $request->an_sp_1;
                    $ss->spiff_2nd = $request->an_sp_2;
                    $ss->spiff_3rd = $request->an_sp_3;
                    $ss->residual = $request->an_rs;
                    $ss->ar = $request->an_ar;
                    $ss->regular_rebate_1st = $request->an_rb_1;
                    $ss->regular_rebate_2nd = $request->an_rb_2;
                    $ss->regular_rebate_3rd = $request->an_rb_3;
                    $ss->byod_rebate_1st = $request->an_by_1;
                    $ss->byod_rebate_2nd = $request->an_by_2;
                    $ss->byod_rebate_3rd = $request->an_by_3;
                    $ss->created_by = Auth::user()->user_id;
                    $ss->cdate = Carbon::now();
                    $ss->save();
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

    public function reset_exist_only(Request $request) {
        try {

            $amount = $request->amount;
            $amount_explode = explode('|', $amount);
            $denom_id = $amount_explode[0];
            $denom  = $amount_explode[1];

            if($request->template == 'M' || $request->template == 'D'|| $request->template == 'S') {

                $account_type = $request->template;

                SpiffSetup::where('product_id', $request->product)
                    ->where('denom', $denom)
                    ->where('account_type', $account_type)
                    ->update(['spiff_1st' => $request->sp_1,
                        'spiff_2nd' => $request->sp_2,
                        'spiff_3rd' => $request->sp_3,
                        'residual'  => $request->rs,
                        'ar'        => $request->ar,
                        'regular_rebate_1st' => $request->rb_1,
                        'regular_rebate_2nd' => $request->rb_2,
                        'regular_rebate_3rd' => $request->rb_3,
                        'byod_rebate_1st' => $request->by_1,
                        'byod_rebate_2nd' => $request->by_2,
                        'byod_rebate_3rd' => $request->by_3
                    ]);

            }else{

                $st = SpiffTemplate::where('id', $request->template)->first();
                $account_type = $st->account_type;

                $res = SpiffSetup::where('product_id', $request->product)
                    ->where('denom', $denom)
                    ->where('account_type', $account_type)
                    ->where('template', $request->template)
                    ->first();

                if(empty($res)){
                    return response()->json([
                        'msg' => 'Not Exist Spiff(s)'
                    ]);
                }

                $res->template   = $request->template;
                $res->product_id = $request->product;
//            $res->denom_id   = $denom_id;
                $res->denom      = $denom;
                $res->account_type   = $st->account_type;
                $res->spiff_1st  = $request->sp_1;
                $res->spiff_2nd  = $request->sp_2;
                $res->spiff_3rd  = $request->sp_3;
                $res->residual   = $request->rs;
                $res->ar         = $request->ar;
                $res->regular_rebate_1st  = $request->rb_1;
                $res->regular_rebate_2nd  = $request->rb_2;
                $res->regular_rebate_3rd  = $request->rb_3;
                $res->byod_rebate_1st  = $request->by_1;
                $res->byod_rebate_2nd  = $request->by_2;
                $res->byod_rebate_3rd  = $request->by_3;
                $res->modified_by = Auth::user()->user_id;
                $res->mdate       = Carbon::now();
                $res->save();
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

    public function inc_dec_exist_spiff_only(Request $request) {
        try {

            $denom = $request->amount;

            if($request->template == 'A'){
                $q = SpiffSetup::where('product_id', $request->product);

                if($denom != 'all') {
                    $q = $q->where('denom', $denom);
                }

                $q->update(['spiff_1st' => DB::raw('spiff_1st + '. $request->sp_1),
                    'spiff_2nd' => DB::raw('spiff_2nd + '. $request->sp_2),
                    'spiff_3rd' => DB::raw('spiff_3rd + '. $request->sp_3),
                    'residual'  => DB::raw('residual + '. $request->rs),
                    'ar'        => DB::raw('ar + '. $request->ar),
                    'regular_rebate_1st' => DB::raw('regular_rebate_1st + '. $request->rb_1),
                    'regular_rebate_2nd' => DB::raw('regular_rebate_2nd + '. $request->rb_2),
                    'regular_rebate_3rd' => DB::raw('regular_rebate_3rd + '. $request->rb_3),
                    'byod_rebate_1st' => DB::raw('byod_rebate_1st + '. $request->by_1),
                    'byod_rebate_2nd' => DB::raw('byod_rebate_2nd + '. $request->by_2),
                    'byod_rebate_3rd' => DB::raw('byod_rebate_3rd + '. $request->by_3)
                ]);
            }else if($request->template == 'M' || $request->template == 'D'|| $request->template == 'S'){

                $account_type = $request->template;

                $q = SpiffSetup::where('product_id', $request->product);

                if($denom != 'all') {
                    $q = $q->where('denom', $denom);
                }

                $q = $q->where('account_type', $account_type)
                    ->update(['spiff_1st' => DB::raw('spiff_1st + '. $request->sp_1),
                        'spiff_2nd' => DB::raw('spiff_2nd + '. $request->sp_2),
                        'spiff_3rd' => DB::raw('spiff_3rd + '. $request->sp_3),
                        'residual'  => DB::raw('residual + '. $request->rs),
                        'ar'        => DB::raw('ar + '. $request->ar),
                        'regular_rebate_1st' => DB::raw('regular_rebate_1st + '. $request->rb_1),
                        'regular_rebate_2nd' => DB::raw('regular_rebate_2nd + '. $request->rb_2),
                        'regular_rebate_3rd' => DB::raw('regular_rebate_3rd + '. $request->rb_3),
                        'byod_rebate_1st' => DB::raw('byod_rebate_1st + '. $request->by_1),
                        'byod_rebate_2nd' => DB::raw('byod_rebate_2nd + '. $request->by_2),
                        'byod_rebate_3rd' => DB::raw('byod_rebate_3rd + '. $request->by_3)
                    ]);
            } else {

                $st = SpiffTemplate::where('id', $request->template)->first();
                $account_type = $st->account_type;

                $res = SpiffSetup::where('product_id', $request->product);

                if($denom != 'all') {
                    $res = $res->where('denom', $denom);
                }

                $res = $res->where('denom', $denom)
                    ->where('account_type', $account_type)
                    ->where('template', $request->template)
                    ->first();

                if (empty($res)) {
                    return response()->json([
                        'msg' => 'Not Exist Spiff(s)'
                    ]);
                }

                $res->template = $request->template;
                $res->product_id = $request->product;
                $res->denom = $denom;
                $res->account_type = $st->account_type;
                $res->spiff_1st = $res->spiff_1st + $request->sp_1;
                $res->spiff_2nd = $res->spiff_2nd + $request->sp_2;
                $res->spiff_3rd = $res->spiff_3rd + $request->sp_3;
                $res->residual = $res->residual + $request->rs;
                $res->ar = $res->ar + $request->ar;
                $res->regular_rebate_1st = $res->regular_rebate_1st + $request->rb_1;
                $res->regular_rebate_2nd = $res->regular_rebate_2nd + $request->rb_2;
                $res->regular_rebate_3rd = $res->regular_rebate_3rd + $request->rb_3;
                $res->byod_rebate_1st = $res->byod_rebate_1st + $request->by_1;
                $res->byod_rebate_2nd = $res->byod_rebate_2nd + $request->by_2;
                $res->byod_rebate_3rd = $res->byod_rebate_3rd + $request->by_3;
                $res->modified_by = Auth::user()->user_id;
                $res->mdate = Carbon::now();
                $res->save();
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

}