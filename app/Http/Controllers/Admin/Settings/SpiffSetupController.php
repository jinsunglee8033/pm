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

class SpiffSetupController extends Controller
{

    public function show(Request $request) {


        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }


        $query = SpiffSetup::join('product', 'product.id', 'spiff_setup.product_id')
            ->leftJoin('spiff_template', 'spiff_setup.template', '=', 'spiff_template.id')
            ->where('product.status', 'A');


        $products = Product::where('status', 'A')
            ->where('activation', 'Y');

        $denoms = Denom::join('product', function($join) {
                $join->on('product.id', '=', 'denomination.product_id');
            })->join('vendor_denom', function($join) {
                $join->on('vendor_denom.denom', '=', 'denomination.denom');
                $join->on('vendor_denom.vendor_code', '=', 'product.vendor_code');
                $join->on('vendor_denom.product_id', '=', 'product.id');
            })->where('vendor_denom.act_pid', '!=', '');

        if ($request->product_id == 'WATTA') {
            $denoms = Denom::join('product', 'product.id', '=', 'denomination.product_id');
        }
        $denoms = $denoms->where('denomination.status', 'A');
        $denoms = $denoms->where('denomination.product_id', $request->product_id);

        if (!empty($request->carrier)) {
            $query = $query->where('product.carrier', $request->carrier);
            $products = $products->where('carrier', $request->carrier);
            $denoms = $denoms->where('product.carrier', $request->carrier);
        }

        if (!empty($request->product_id)) {
            $query = $query->where('spiff_setup.product_id', $request->product_id);
            //$denoms = $denoms->where('denomination.product_id', $request->product_id);
        }

        $user = Auth::user();

        $templates = null;
        $template_owners = '';
        if (!empty($request->account_type)) {
            $query = $query->where('spiff_setup.account_type', $request->account_type);

            $templates = SpiffTemplate::where('account_id', $user->account_id)
                ->where('account_type', $request->account_type)
                ->orderBy('template', 'asc')
                ->get();

            if (!empty($request->template)) {
                if ($request->template == 'default') {
                    $query->whereRaw('spiff_setup.template is null');
                } else {
                    $query->whereRaw('spiff_setup.template = ' . $request->template);

                    $owners = SpiffTemplateOwner::where('template_id', $request->template)->get();
                    $ars = [];
                    foreach ($owners as $ow) {
                        $ars[] = $ow->account_id;
                    }
                    $template_owners = implode (",", $ars);
                }
            }
        }

        if (!empty($request->denom)) {
            $query = $query->where('spiff_setup.denom', $request->denom);
        }

        if ($request->excel == 'Y') {
            $data = $query->select(
                'spiff_setup.*',
                'product.name as product',
                DB::raw('case spiff_setup.account_type when "M" then 1 when "D" then 2 when "S" then 3 else 4 end as seq'),
                DB::raw('spiff_template.template as template_name')
            )->orderBy('product.name', 'asc')
            ->orderBy('spiff_setup.denom', 'asc')
            ->orderBy('seq', 'asc')
            ->get();

            Excel::create('spiff_setup', function($excel) use($data) {

                $excel->sheet('SpiffSetup', function($sheet) use($data) {

                    $reports = [];

                    foreach ($data as $a) {

                        $reports[] = [
                            'Product' => $a->product,
                            'Amount($)' => $a->denom,
                            'Account.Type' => $a->account_type,
                            'Period.From' => $a->period_from,
                            'Period.To' => $a->period_to,
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
                            'Template' => $a->template_name,
                            'Download.Date' => date("m/d/Y h:i:s A")

                        ];
                    }
                    $sheet->fromArray($reports);
                });
            })->export('xlsx');
        }

        $data = $query->select(
                'spiff_setup.*',
                'product.carrier as carrier',
                'product.name as product',
                DB::raw('case spiff_setup.account_type when "M" then 1 when "D" then 2 when "S" then 3 else 4 end as seq'),
                DB::raw('spiff_template.template as template_name')
            )->orderBy('product.name', 'asc')
            ->orderBy('spiff_setup.denom', 'asc')
            ->orderBy('seq', 'asc')
            ->paginate(20);


        $products = $products->get();

        $denoms = $denoms->select('denomination.*')->get();

        return view('admin.settings.spiff-setup', [
            'account_type' => $request->account_type,
            'carrier' => $request->carrier,
            'product_id' => $request->product_id,
            'products' => $products,
            'data' => $data,
            'denom' => $request->denom,
            'denoms' => $denoms,
            'template' => $request->template,
            'templates' => $templates,
            'template_owners' => $template_owners
        ]);

    }

    public function loadProduct(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'carrier' => 'required'
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

            $products = Product::where('carrier', $request->carrier)
                ->where('status', 'A')
                ->where('activation', 'Y')
                ->get();

            return response()->json([
                'msg' => '',
                'products' => $products
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadDenoms(Request $request) {
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

            $denoms = Denom::join('product', 'product.id', '=', 'denomination.product_id')
                ->join('vendor_denom', function($join) {
                    $join->on('vendor_denom.denom', '=', 'denomination.denom');
                    $join->on('vendor_denom.vendor_code', '=', 'product.vendor_code');
                    $join->on('vendor_denom.product_id', '=', 'product.id');
                })
                ->leftjoin('spiff_setup', function($join) {
                  $join->on('spiff_setup.id', '=', 'denomination.product_id')
                    ->where('spiff_setup.denom', '=', 'denomination.denom');
              })
                ->where('vendor_denom.act_pid', '!=', '');

            if ($request->product_id == 'WATTA') {
                $denoms = Denom::query();
            }

            $denoms = $denoms->where('denomination.product_id', $request->product_id)
                ->where('denomination.status', 'A')
                ->select('denomination.*')
                ->get();

            return response()->json([
                'msg' => '',
                'denoms' => $denoms
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadDetail(Request $request) {
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

            $spiff_setup = SpiffSetup::find($request->id);
            if (empty($spiff_setup)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $specials = SpiffSetupSpecial::where('product_id', $spiff_setup->product_id)
                ->where('denom', $spiff_setup->denom)
                ->where('account_type', $spiff_setup->account_type)
                ->orderBy('period_from', 'desc')
                ->get();

            $product = Product::find($spiff_setup->product_id);
            $products = [];
            $denoms = [];
            $templates = [];
            if (!empty($product)) {
                $products = Product::where('carrier', $product->carrier)
                    ->where('status', 'A')
                    ->get();

                $denoms = Denom::where('product_id', $product->id)
                    ->where('status', 'A')
                    ->get();

                $templates = SpiffTemplate::where('account_type', $spiff_setup->account_type)->get();
            }

            return response()->json([
                'msg' => '',
                'spiff_setup' => $spiff_setup,
                'specials'  => $specials,
                'products' => $products,
                'denoms' => $denoms,
                'templates' => $templates
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
                'product_id' => 'required',
                'denom' => 'required|numeric',
                'account_type' => 'required|in:M,D,S',
                'spiff_1st' => 'required|numeric',
                'spiff_2nd' => 'required|numeric',
                'spiff_3rd' => 'required|numeric',
                'regular_rebate_1st' => 'required|numeric',
                'regular_rebate_2nd' => 'required|numeric',
                'regular_rebate_3rd' => 'required|numeric',
                'byod_rebate_1st' => 'required|numeric',
                'byod_rebate_2nd' => 'required|numeric',
                'byod_rebate_3rd' => 'required|numeric'
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

            $denom = Denom::where('product_id', $product->id)
                ->where('denom', $request->denom)
                ->where('status', 'A')
                ->first();

            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid denomination provided'
                ]);
            }

            $spiff_setup = SpiffSetup::where('product_id', $request->product_id)
                ->where('denom', $request->denom)
                ->where('account_type', $request->account_type)
                ->where('template', $request->template)
                ->first();

            if (!empty($spiff_setup)) {
                return response()->json([
                    'msg' => 'Duplicated setup found'
                ]);
            }

            $o = new SpiffSetup;
            $o->product_id = $request->product_id;
            $o->denom = $request->denom;
            $o->account_type = $request->account_type;
            $o->spiff_1st = $request->spiff_1st;
            $o->spiff_2nd = $request->spiff_2nd;
            $o->spiff_3rd = $request->spiff_3rd;
            $o->regular_rebate_1st = $request->regular_rebate_1st;
            $o->regular_rebate_2nd = $request->regular_rebate_2nd;
            $o->regular_rebate_3rd = $request->regular_rebate_3rd;
            $o->byod_rebate_1st = $request->byod_rebate_1st;
            $o->byod_rebate_2nd = $request->byod_rebate_2nd;
            $o->byod_rebate_3rd = $request->byod_rebate_3rd;
            if (!empty($request->template)) {
                $o->template = $request->template;
            }
            $o->cdate = Carbon::now();
            $o->created_by = Auth::user()->user_id;
            $o->save();

            return response()->json([
                'msg' => '',
                'id' => $o->id
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
                'product_id' => 'required',
                'denom' => 'required|numeric',
                'account_type' => 'required|in:M,D,S',
                'spiff_1st' => 'required|numeric',
                'spiff_2nd' => 'required|numeric',
                'spiff_3rd' => 'required|numeric',
                'regular_rebate_1st' => 'required|numeric',
                'regular_rebate_2nd' => 'required|numeric',
                'regular_rebate_3rd' => 'required|numeric',
                'byod_rebate_1st' => 'required|numeric',
                'byod_rebate_2nd' => 'required|numeric',
                'byod_rebate_3rd' => 'required|numeric'
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

            ### product check ###
            $product = Product::find($request->product_id);
            if (empty($product)) {
                return response()->json([
                    'msg' => 'Invalid product ID provided'
                ]);
            }

            ### denom check ###
            $denom = Denom::where('product_id', $product->id)
                ->where('denom', $request->denom)
                ->where('status', 'A')
                ->first();

            if (empty($denom)) {
                return response()->json([
                    'msg' => 'Invalid denomination provided'
                ]);
            }

            ### dup check ###
            $spiff_setup = SpiffSetup::where('product_id', $request->product_id)
                ->where('denom', $request->denom)
                ->where('account_type', $request->account_type)
                ->where('template', $request->template)
                ->where('id', '!=', $request->id)
                ->first();

            if (!empty($spiff_setup)) {
                return response()->json([
                    'msg' => 'Duplicated setup found'
                ]);
            }

            ### now update ###
            $o = SpiffSetup::find($request->id);
            $o->product_id = $request->product_id;
            $o->denom = $request->denom;
            $o->account_type = $request->account_type;
            $o->spiff_1st = $request->spiff_1st;
            $o->spiff_2nd = $request->spiff_2nd;
            $o->spiff_3rd = $request->spiff_3rd;
            $o->regular_rebate_1st = $request->regular_rebate_1st;
            $o->regular_rebate_2nd = $request->regular_rebate_2nd;
            $o->regular_rebate_3rd = $request->regular_rebate_3rd;
            $o->byod_rebate_1st = $request->byod_rebate_1st;
            $o->byod_rebate_2nd = $request->byod_rebate_2nd;
            $o->byod_rebate_3rd = $request->byod_rebate_3rd;
            $o->mdate = Carbon::now();
            $o->modified_by = Auth::user()->user_id;
            $o->save();

            return response()->json([
                'msg' => '',
                'id' => $o->id
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadSpecial(Request $request) {
        try {

            $specials = SpiffSetupSpecial::where('product_id', $request->product_id)
                ->where('denom', $request->denom)
                ->where('account_type', $request->account_type)
                ->orderBy('period_from', 'desc')
                ->get([
                    'id',
                    'name',
                    DB::raw("ifnull(include, '') as include"),
                    DB::raw("ifnull(exclude, '') as exclude"),
                    'period_from',
                    'period_to',
                    DB::raw('ifnull(terms, \'\') as terms'),
                    'spiff',
                    'pay_to',
                    'pay_to_amt',
                    'status'
                ]);

            return response()->json([
                'msg' => '',
                'specials'  => $specials
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function addSpecial(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'name' => 'required',
                'period_from' => 'required',
                'period_to' => 'required',
                'spiff' => 'required'
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

            $special = new SpiffSetupSpecial();
            $special->product_id = $request->product_id;
            $special->denom = $request->denom;
            $special->account_type = $request->account_type;
            $special->name = $request->name;
            $special->period_from = $request->period_from;
            $special->period_to = $request->period_to;
            $special->terms = $request->terms;
            $special->spiff = $request->spiff;
            $special->include = $request->include;
            if ($request->terms == 'referal') {
                $special->pay_to = $request->pay_to;
                $special->pay_to_amt = $request->pay_to_amt;
            } else {
                $special->exclude = $request->exclude;
            }
            $special->created_by = Auth::user()->user_id;
            $special->cdate = Carbon::now();
            $special->save();

            $specials = SpiffSetupSpecial::where('product_id', $request->product_id)
                ->where('denom', $request->denom)
                ->where('account_type', $request->account_type)
                ->orderBy('period_from', 'desc')
                ->get([
                  'id',
                  'name',
                  DB::raw("ifnull(include, '') as include"),
                  DB::raw("ifnull(exclude, '') as exclude"),
                  'period_from',
                  'period_to',
                  DB::raw('ifnull(terms, \'\') as terms'),
                  'spiff',
                  'pay_to',
                  'pay_to_amt',
                  'status'
                ]);

            return response()->json([
                'msg' => '',
                'specials'  => $specials
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updateSpecial(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'name' => 'required',
                'period_to' => 'required'
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

            $special = SpiffSetupSpecial::find($request->id);

            if (empty($special)) {
                return response()->json([
                    'msg' => 'No special spiff data found.'
                ]);
            }

            $special->name = $request->name;
            $special->period_from = $request->period_from;
            $special->period_to = $request->period_to;
            $special->spiff = $request->spiff;
            $special->include = $request->include;
            if ($special->terms == 'referal') {
                $special->pay_to = $request->pay_to;
                $special->pay_to_amt = $request->pay_to_amt;
            } else {
                $special->exclude = $request->exclude;
            }
            $special->modified_by = Auth::user()->user_id;
            $special->mdate = Carbon::now();
            $special->save();

            $specials = SpiffSetupSpecial::where('product_id', $special->product_id)
                ->where('denom', $special->denom)
                ->where('account_type', $special->account_type)
                ->orderBy('period_from', 'desc')
                ->get([
                  'id',
                  'name',
                  DB::raw("ifnull(include, '') as include"),
                  DB::raw("ifnull(exclude, '') as exclude"),
                  'period_from',
                  'period_to',
                  DB::raw('ifnull(terms, \'\') as terms'),
                  'spiff',
                  'pay_to',
                  'pay_to_amt',
                  'status'
                ]);

            return response()->json([
                'msg' => '',
                'specials'  => $specials
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }


    public function downloadSpecial(Request $request) {

        $data = SpiffSetupSpecial::join("product", 'spiff_setup_special.product_id', '=', 'product.id')
            ->leftjoin('accounts', 'spiff_setup_special.account_id', '=', 'accounts.id')
            ->get([
                'spiff_setup_special.*',
                DB::raw('product.name as product_name'),
                DB::raw('accounts.name as account_name')
            ]);

        Excel::create('special_spiff', function($excel) use($data) {

            $excel->sheet('SpecialSpiff', function($sheet) use($data) {

                $reports = [];

                foreach ($data as $a) {

                    $reports[] = [
                        'ID' => $a->id,
                        'Product' => $a->product_name,
                        'Amount($)' => $a->denom,
                        'Account.Type' => $a->account_type,
                        'Name' => $a->name,
                        'Period.From' => $a->period_from,
                        'Period.To' => $a->period_to,
                        'Terms' => $a->terms,
                        'Spiff' => $a->spiff,
                        'Include' => $a->include,
                        'Exclude' => $a->exclude,
                        'Pay.To' => $a->pay_to,
                        'Pay.To.Amt' => $a->pay_to_amt,
                        'Download.Date' => date("m/d/Y h:i:s A")

                    ];
                }
                $sheet->fromArray($reports);
            });
        })->export('xlsx');

    }

    public function add_template(Request $request) {
        if (!empty($request->template_name) && !empty($request->account_type)) {
            $template = SpiffTemplate::find($request->template_id);

            if (empty($template)) {
                $template = new SpiffTemplate();
                $template->account_id = Auth::user()->account_id;
                $template->account_type = $request->account_type;
                $template->template = $request->template_name;
                $template->cdate = Carbon::now();
                $template->save();
            } else {
                $template->template = $request->template_name;
                $template->mdate = Carbon::now();
                $template->update();
            }

            SpiffTemplateOwner::where('template_id', $template->id)->delete();
            if (!empty($request->master_ids)) {
                $owner_ids = explode(',', $request->master_ids);
                if (!empty($owner_ids) && count($owner_ids) > 0) {
                    foreach ($owner_ids as $oid) {
                        $owner = new SpiffTemplateOwner();
                        $owner->template_id = $template->id;
                        $owner->account_id  = $oid;
                        $owner->save();
                    }
                }
            }

            return redirect('/admin/settings/spiff-setup?account_type=' . $request->account_type . '&template=' . $template->id);
        }

        return redirect('/admin/settings/spiff-setup?account_type=' . $request->account_type);
    }
}