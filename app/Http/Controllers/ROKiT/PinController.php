<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/25/19
 * Time: 7:36 AM
 */

namespace App\Http\Controllers\ROKiT;

use App\Http\Controllers\Controller;
use App\Lib\CommissionProcessor;
use App\Lib\emida;
use App\Lib\epay;
use App\Lib\gen;
use App\Lib\gss;

use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Lib\RTRProcessor;
use App\Lib\telestar;

use App\Model\Account;
use App\Model\Denom;
use App\Model\Product;
use App\Model\RTRPayment;
use App\Model\StockPin;
use App\Model\Transaction;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PinController extends Controller
{

    public function show(Request $request) {

        $products   = Product::where('carrier', 'ROKiT')->where('status', 'A')->get();
//        $denoms     = Denom::whereIn('product_id', ['WROKT', 'WROKL'])->where('status', 'A')->orderBy('denom', 'asc')->get();

        $product_id = $request->old('product_id', $request->get('product_id'));

//        if (empty($product_id) && count($products) == 1) {
//            $product_id = $products[0]->product_id;
//        }
        $denoms = null;
        if(!empty($product_id)) {
            ### denominations ###
            $denoms = DB::select("
                select 
                    c.id,
                    b.id as denom_id,
                    b.denom,
                    b.name,
                    v.rtr_pid,
                    v.fee
                from denomination b
                    inner join product c on b.product_id = c.id
                    inner join vendor_denom v on c.vendor_code = v.vendor_code and c.id = v.product_id and b.denom = v.denom
                where c.id = :product_id
                and c.status = 'A'
                and b.status = 'A'
            ",[
                'product_id' => $product_id
            ]);
        }

        return view('rokit.pin')->with([
            'products'  => $products,
            'product_id'=> $product_id,
            'denoms'    => $denoms
        ]);
    }

    public function process(Request $request) {

        $trans = null;

        try {

            $v = Validator::make($request->all(), [
              'denom_id' => 'required'
            ], [
                'denom_id.required' => 'Please select amount first',
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

            ### check sales limit ###
            $c_store_rtr_account_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
            $account = Account::find($c_store_rtr_account_id);
            if (empty($account)) {
                throw new \Exception('Logged in user account is invalid. Please contact our customer care.', -7);
            }

            $denom = Denom::where('id', $request->denom_id)->first();

            ### now create order ###
            $trans = new Transaction;
            $trans->type = 'S';
            $trans->account_id = $account->id;
            $trans->product_id = $denom->product_id;
            $trans->action = 'PIN';
            $trans->denom = $denom->denom;
            $trans->phone = '';
            $trans->status = 'I';
            $trans->cdate = Carbon::now();
            $trans->created_by = 'cstore';
            $trans->vendor_code = 'SPP';
            $trans->api = 'Y';
            $trans->collection_amt = 0;
            $trans->rtr_month = 1;
            $trans->net_revenue = 0;
            $trans->fee = 0;
            $trans->pm_fee = 0;
            $trans->save();

//            $ret = gen::RedeemPin($trans);
            \DB::beginTransaction();
            try {
                ### get pin from DB ###
                $stock_pin = StockPin::where('product', $denom->product_id)
                    ->where('status', 'A')
                    ->where('amount',$denom->denom)
                    ->orderBy('id', 'asc')
                    ->first();

                if (empty($stock_pin)) {
                    $ret['error_code'] = -1;
                    $ret['error_msg'] = "PIN Not Exist";
                } else {
                    $pin = $stock_pin->pin;
                    $vendor_tx_id = $trans->id;

                    ### update stock_pin ###
                    $stock_pin->status = 'S';
                    $stock_pin->used_trans_id = $vendor_tx_id;
                    $stock_pin->amount = $denom->denom;
                    $stock_pin->used_date = Carbon::now();
                    $stock_pin->save();
                    $ret['error_code'] = '';
                }
                \DB::commit();
            } catch (\Exception $ex) {
                \DB::rollBack();
                return back()->withInput()->withErrors([
                    'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
                ]);
            }

            if (!empty($ret['error_code'])) {
                $trans->status = 'F';
                $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                $trans->save();

                return response()->json([
                  'code' => '-5',
                  'data' => [
                    'fld'   => 'exception',
                    'msg'   => $trans->note
                  ]
                ]);
            }

            ### mark as success ###
            $trans->status  = 'C';
            $trans->pin     = $pin;
            $trans->mdate   = Carbon::now();
            $trans->modified_by = 'system';
            $trans->save();

            return response()->json([
                'msg' => '',
                'invoice_no' => $trans->id,
                'carrier' => $request->carrier,
                'product' => $denom->name,
                'amount' => $trans->denom,
                'pin' => $pin,
                'sub_total' => $trans->rtr_month * $trans->denom,
                'fee' => 0,
                'total' => $trans->rtr_month * $trans->denom
            ]);

        } catch (\Exception $ex) {

            if (isset($trans)) {
                $trans->status = 'F';
                $trans->note = $ex->getMessage() . ' [' . $ex->getCode() . ']';
                $trans->save();
            }

            $msg = ' - code : ' . $ex->getCode() . '<br/>';
            $msg .= ' - msg : ' . $ex->getMessage() . '<br/>';
            $msg .= ' - trace : ' . $ex->getTraceAsString() . '<br/>';

            Helper::send_mail('it@perfectmobileinc.com', 'E-commerce ROKiT PIN Failed', $msg);

            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}