<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 12/19/18
 * Time: 10:32 AM
 */

namespace App\Http\Controllers\Admin\Settings\SpiffSetup;


use App\Http\Controllers\Controller;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\Product;
use App\Model\SpiffSetup;
use App\Model\SpiffSetupSpecial;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class SpecialController extends Controller
{

    public function show(Request $request) {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas',
              'admin', 'system']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        $denoms = '';
        $query_product = '1=1';
        $query_special = '1=1';
        if (!empty($request->denom)) {
            $query_product = "id in (select id from product where carrier =  '" . $request->carrier . "')";
            $denoms = Denom::where('product_id', $request->product_id)
              ->whereRaw("denom in (select denom from denomination where product_id = '" . $request->product_id . "')")
              ->get([DB::raw("distinct(denom) as denom")]);
            $query_special = "product_id = '" . $request->product_id . "' and denom = " . $request->denom . "";
        } else if (!empty($request->product_id)) {
            $query_product = "id in (select id from product where carrier =  '" . $request->carrier . "')";
            $denoms = Denom::where('product_id', $request->product_id)
                ->whereRaw("denom in (select denom from denomination where product_id = '" . $request->product_id . "')")
                ->get([DB::raw("distinct(denom) as denom")]);
            $query_special = "product_id = '" . $request->product_id . "' and denom in (select denom from denomination where product_id = '" . $request->product_id . "')";
        } else if (!empty($request->carrier)) {
            $query_product = "id in (select id from product where carrier =  '" . $request->carrier . "')";
            $query_special = "(product_id, denom) in (select product_id, denom from denomination where product_id in (select id from product where carrier =  '" . $request->carrier . "'))";
        }

        $query = SpiffSetupSpecial::join('product', 'spiff_setup_special.product_id', '=', 'product.id')
            ->whereRaw($query_special);

        if (!empty($request->account_type)) {
            $query->where('account_type', $request->account_type);
        }

        $products = Product::where('activation', 'Y')->where('status', 'A')->whereRaw($query_product)->get();

        if ($request->excel == 'Y') {
            $specials = $query->orderBy('id', 'desc')
              ->get([
                'spiff_setup_special.id',
                'spiff_setup_special.name',
                DB::raw("ifnull(spiff_setup_special.note1, '') as note1"),
                DB::raw("ifnull(spiff_setup_special.note2, '') as note2"),
                'spiff_setup_special.product_id',
                'spiff_setup_special.denom',
                'spiff_setup_special.account_type',
                DB::raw("ifnull(spiff_setup_special.include, '') as include"),
                DB::raw("ifnull(spiff_setup_special.exclude, '') as exclude"),
                'spiff_setup_special.period_from',
                'spiff_setup_special.period_to',
                DB::raw('ifnull(spiff_setup_special.terms, \'\') as terms'),
                'spiff_setup_special.maxqty',
                'spiff_setup_special.spiff',
                'spiff_setup_special.pay_to',
                'spiff_setup_special.pay_to_amt',
                'spiff_setup_special.status',
                'product.carrier',
                DB::raw('product.name as prod_name')
              ]);

            Excel::create('special_spiff', function($excel) use($specials) {

                $excel->sheet('SpecialSpiff', function($sheet) use($specials) {

                    $reports = [];

                    foreach ($specials as $a) {

                        $reports[] = [
                          'ID' => $a->id,
                          'Product' => $a->prod_name,
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

        $specials = $query->orderBy('id', 'desc')
          ->paginate(500, [
            'spiff_setup_special.id',
            'spiff_setup_special.name',
            DB::raw("ifnull(spiff_setup_special.note1, '') as note1"),
            DB::raw("ifnull(spiff_setup_special.note2, '') as note2"),
            'spiff_setup_special.product_id',
            'spiff_setup_special.denom',
            'spiff_setup_special.account_type',
            DB::raw("ifnull(spiff_setup_special.include, '') as include"),
            DB::raw("ifnull(spiff_setup_special.exclude, '') as exclude"),
            'spiff_setup_special.period_from',
            'spiff_setup_special.period_to',
            DB::raw('ifnull(spiff_setup_special.terms, \'\') as terms'),
            'spiff_setup_special.maxqty',
            'spiff_setup_special.spiff',
            'spiff_setup_special.pay_to',
            'spiff_setup_special.pay_to_amt',
            'spiff_setup_special.status',
            'product.carrier',
            DB::raw('product.name as prod_name')
          ]);

        $carriers = Carrier::where('has_activation', 'Y')->get();

        return view('admin.settings.spiffsetup.special', [
            'account_type' => $request->account_type,
            'carrier' => $request->carrier,
            'product_id' => $request->product_id,
            'denom' => $request->denom,
            'products' => $products,
            'denoms' => $denoms,
            'specials' => $specials,
            'carriers' =>$carriers
        ]);
    }

    public function add(Request $request) {
        try {

            $v = Validator::make($request->all(), [
              'product_id' => 'required',
              'denom' => 'required',
              'account_type' => 'required',
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
            $special->note1 = $request->note1;
            $special->note2 = $request->note2;
            $special->period_from = $request->period_from;
            $special->period_to = $request->period_to;
            $special->terms = $request->terms;
            $special->maxqty = $request->maxqty;
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

            return response()->json([
              'code'    => '0',
              'msg'     => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'code'    => '-9',
              'msg'     => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update(Request $request) {
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
            $special->note1 = $request->note1;
            $special->note2 = $request->note2;
            $special->period_from = $request->period_from;
            $special->period_to = $request->period_to;
            $special->spiff = $request->spiff;
            $special->include = $request->include;
            if ($special->terms !== 'referal') {
                $special->terms = $request->terms;
            }
            $special->maxqty = $request->maxqty;
            if ($special->terms == 'referal') {
                $special->pay_to = $request->pay_to;
                $special->pay_to_amt = $request->pay_to_amt;
            } else {
                $special->exclude = $request->exclude;
            }
            $special->modified_by = Auth::user()->user_id;
            $special->mdate = Carbon::now();
            $special->save();

            return response()->json([
              'code'    => '0',
              'msg'     => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'code'    => '-9',
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }
}