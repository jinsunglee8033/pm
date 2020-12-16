<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/27/17
 * Time: 3:10 PM
 */

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Model\Credit;
use Illuminate\Http\Request;
use App\Model\VirtualRep;
use App\Model\VRProduct;
use App\Model\VRRequest;
use App\Model\VRRequestProduct;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use Excel;
use App\Lib\Helpr;

class VirtualRepController extends Controller
{

    /* New Version */
    public function showRequest(Request $request) {

        try {
            if(!Auth::check() || Auth::user()->account_type == 'S') {
                return redirect('/admin/dashboard');
            }

            $sdate = Carbon::today()->startOfWeek()->addDays(-7);
            $edate = Carbon::today()->addDays(1)->addSeconds(-1);

            if (!empty($request->sdate) && empty($request->id)) {
                $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
            }

            if (!empty($request->edate) && empty($request->id)) {
                $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
            }

            $login_acct = Account::find(Auth::user()->account_id);

            $query = VRRequest::join('accounts', 'accounts.id', '=', 'vr_request.account_id')
                ->leftJoin('vr_payment', 'vr_payment.vr_id', '=', 'vr_request.id')
                ->where('vr_request.status', '<>', 'CT')
                ->where('accounts.path', 'like', $login_acct->path . '%');

            $query = $query->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) >= ?', [$sdate])
                ->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) < ? ', [$edate]);

            if (!empty($request->category)) {
                $query = $query->where('vr_request.category', $request->category);
            }

            if (!empty($request->status)) {
                $query = $query->where('vr_request.status', $request->status);
            }

            if (!empty($request->account_id)) {
                $query = $query->where('accounts.id', $request->account_id);
            }

            if (!empty($request->acct_ids)) {
                $acct_ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
                $query = $query->whereIn('accounts.id', $acct_ids);
            }

            if (!empty($request->account_name)) {
                $query = $query->whereRaw('lower(accounts.name) like ?', ['%' . strtolower($request->account_name). '%']);
            }

            if (!empty($request->tracking_no)) {
                $query = $query->whereRaw('lower(vr_request.tracking_no) like ?', ['%' . strtolower($request->tracking_no) . '%']);
            }

            if (!empty($request->payment_note)) {
                $query = $query->whereRaw('lower(vr_request.payment_note) like ?', ['%' . strtolower($request->payment_note) . '%']);
            }

            if (!empty($request->vendor_note)) {
                $query = $query->whereRaw('lower(vr_request.vendor_note) like ?', ['%' . strtolower($request->vendor_note) . '%']);
            }

            if (isset($request->shipping_required)) {
                $query = $query->whereRaw("((vr_request.status = 'PC' and vr_request.pay_method = 'PayPal') 
                or (vr_request.status = 'CP' and vr_request.pay_method = 'COD') 
                or (vr_request.status = 'PC' and vr_request.pay_method = 'Balance') 
                or (vr_request.status = 'CP' and vr_request.pay_method = 'Direct Deposit') )");
            }

            if (!empty($request->pay_method)) {
                $query = $query->where('vr_request.pay_method', $request->pay_method);
            }

            if (!empty($request->is_dropship)){
                $dropship_ids = VRRequest::select('vr_request.id')
                    ->join('vr_request_product', 'vr_request_product.vr_id', '=', 'vr_request.id')
                    ->join('vr_product', 'vr_request_product.prod_id', '=', 'vr_product.id')
                    ->where('vr_product.is_dropship', '=', 'Y')
                    ->get();
                $query = $query->whereIn('vr_request.id', $dropship_ids);
            }

            if (!empty($request->model)){
                $model_ids = VRRequest::select('vr_request.id')
                    ->join('vr_request_product', 'vr_request_product.vr_id', '=', 'vr_request.id')
                    ->join('vr_product', 'vr_request_product.prod_id', '=', 'vr_product.id')
                    ->whereRaw('upper(vr_product.model) like ?', ['%' .  strtoupper($request->model) . '%' ])
                    ->get();
                $query = $query->whereIn('vr_request.id', $model_ids);
            }

            if (!empty($request->invoice_id)){
                $query = $query->whereRaw('upper(vr_payment.invoice_number) like ?', ['%' . strtoupper($request->invoice_id) . '%']);
            }

            if (!empty($request->paypal_txn_id)){
                $query = $query->whereRaw('upper(vr_payment.paypal_txn_id) like ?', ['%' . strtoupper($request->paypal_txn_id) . '%']);
            }

            $records = $query->selectRaw('
            vr_request.*, 
            accounts.id as account_id,
            accounts.office_number as account_phone, 
            accounts.email as account_email,
            accounts.state,
            accounts.name as acct_name,
            accounts.type as acct_type,
            vr_payment.invoice_number as invoice_number,
            vr_payment.paypal_txn_id as paypal_txn_id
            ')->orderByRaw('ifnull(vr_request.mdate, vr_request.cdate) desc')->paginate();

            //dd($records);
            return view('admin.reports.vr-request', [
                'sdate' => $sdate->format('Y-m-d'),
                'edate' => $edate->format('Y-m-d'),
                'quick' => $request->quick,
                'category' => $request->category,
                'status' => $request->status,
                'account_id' => $request->account_id,
                'acct_ids' => $request->acct_ids,
                'account_name' => $request->account_name,
                'tracking_no' => $request->tracking_no,
                'payment_note' => $request->payment_note,
                'vendor_note' => $request->vendor_note,
                'pay_method' => $request->pay_method,
                'shipping_required' => isset($request->shipping_required) ? 'Y': 'N',
                'is_dropship' => isset($request->is_dropship) ? 'Y': 'N',
                'invoice_id' => $request->invoice_id,
                'paypal_txn_id' => $request->paypal_txn_id,
                'model' => $request->model,
                'records' => $records
            ]);
        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ])->withInput();
        }
    }

    public function showRequestForMaster(Request $request) {
        $sdate = Carbon::today()->addDays(-29);
        $edate = Carbon::today()->addDays(1)->addSeconds(-1);

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $login_acct = Account::find(Auth::user()->account_id);

        $query = VRRequest::join('accounts', 'accounts.id', '=', 'vr_request.account_id')
            ->where('vr_request.status', '<>', 'CT')
            ->where('accounts.id', '=', $login_acct->id);

        $query = $query->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) >= ?', [$sdate])
            ->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) < ?', [$edate]);

        if (!empty($request->category)) {
            $query = $query->where('vr_request.category', $request->category);
        }

        if (!empty($request->status)) {
            $query = $query->where('vr_request.status', $request->status);
        }

        $records = $query->selectRaw('
        vr_request.*, 
        accounts.id as account_id,
        accounts.office_number as account_phone, 
        accounts.email as account_email,
        accounts.contact as account_name
        ')->orderByRaw('ifnull(vr_request.mdate, vr_request.cdate) desc')->paginate();

        return view('admin.reports.vr-request-for-master', [
            'sdate' => $sdate->format('Y-m-d'),
            'edate' => $edate->format('Y-m-d'),
            'category' => $request->category,
            'status' => $request->status,
            'records' => $records
        ]);
    }

    /* New Version */
    public function cart(Request $request) {

        $login_acct = Account::find(Auth::user()->account_id);

        $query = VRRequest::join('users', 'users.user_id', '=', 'vr_request.created_by')
            ->join('accounts', 'accounts.id', '=', 'vr_request.account_id')
            ->where('vr_request.status', 'CT')
            ->where('accounts.path', 'like', $login_acct->path . '%');

        if (!empty($request->account_id)) {
            $query = $query->where('accounts.id', $request->account_id);
        }

        if (!empty($request->account_name)) {
            $query = $query->whereRaw('lower(accounts.name) like ?', ['%' . strtolower($request->account_name). '%']);
        }

        if (!empty($request->contact_me)) {
            $query = $query->where('vr_request.contact_me', 'Y');
        }

        if (!empty($request->is_dropship)){
            $dropship_ids = VRRequest::select('vr_request.id')
                ->join('vr_request_product', 'vr_request_product.vr_id', '=', 'vr_request.id')
                ->join('vr_product', 'vr_request_product.prod_id', '=', 'vr_product.id')
                ->where('vr_product.is_dropship', '=', 'Y')
                ->get();
            $query = $query->whereIn('vr_request.id', $dropship_ids);
        }

        $records = $query->selectRaw('
        vr_request.*, 
        accounts.id as account_id,
        accounts.office_number as account_phone, 
        accounts.email as account_email,
        accounts.state,
        accounts.name as acct_name,
        accounts.type as acct_type
        ')->orderByRaw('ifnull(vr_request.mdate, vr_request.cdate) desc')->paginate();

        //dd($records);
        return view('admin.reports.vr-cart', [
            'account_id' => $request->account_id,
            'account_name' => $request->account_name,
            'contact_me' => $request->contact_me,
            'is_dropship' => isset($request->is_dropship) ? 'Y': 'N',
            'records' => $records
        ]);
    }

    public function cart_detail(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->status_name = $vr->status_name();

            $vrp = VRRequestProduct::where('vr_id', $request->id)->get();

            if (!empty($vrp)) {
                foreach ($vrp as $o){
                    $o->model = '';
                    $o->category = '';
                    $o->is_dropship = '';

                    $prod = VRProduct::where('id', $o->prod_id)->first();
                    if (!empty($prod)) {
                        $o->model = $prod->model;
                        $o->category = $prod->category;
                        $o->is_dropship = $prod->is_dropship;
                        $o->url = $prod->url;
                    }

                }
            }

            return response()->json([
                'msg' => '',
                'data' => $vr,
                'products' => $vrp
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function cart_update(Request $request) {
        try {

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->op_comments = $request->op_comments;
            $vr->modified_by = Auth::user()->user_id;
            $vr->mdate = Carbon::now();
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

    public function loadDetailRequestForMaster(Request $request) {
        try {
            $v = Validator::make($request->all(), [
                'id' => 'required'
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
            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }
            $vr->status = $vr->status_name();
            $vrp = VRRequestProduct::where('vr_id', $request->id)->get();
            if (!empty($vrp)) {
                foreach ($vrp as $o){
                    $o->model = '';
                    $o->category = '';
                    $prod = VRProduct::where('id', $o->prod_id)->first();
                    if (!empty($prod)) {
                        $o->model = $prod->model;
                        $o->category = $prod->category;
                        $o->url = $prod->url;
                    }
                }
            }
            return response()->json([
                'msg' => '',
                'data' => $vr,
                'products' => $vrp
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function loadDetailRequest(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->status_name = $vr->status_name();

            $vrp = VRRequestProduct::where('vr_id', $request->id)->get();

            if (!empty($vrp)) {
                foreach ($vrp as $o){
                    $o->model = '';
                    $o->category = '';

                    $prod = VRProduct::where('id', $o->prod_id)->first();
                    if (!empty($prod)) {
                        $o->model = $prod->model;
                        $o->category = $prod->category;
                        $o->url = $prod->url;
                        $o->is_dropship = $prod->is_dropship;
                    }

                }
            }

            return response()->json([
                'msg' => '',
                'data' => $vr,
                'products' => $vrp
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function updateRequest(Request $request) {
        try {

            //dd($request->all());
            $v = Validator::make($request->all(), [
                'id' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            if ($vr->category == 'O') {
                // order
                $status = $request->status;
                if ($status == 'SH' && ($vr->pay_method == 'PayPal' || $vr->pay_method == 'Balance' || $vr->pay_method == 'Direct Deposit')) {
                    $status = 'C';
                }

                if ($status == 'PC' && $vr->pay_method == 'COD' ) {
                    $status = 'C';
                }

                $vr->status = $status;
                $vr->op_comments = $request->op_comments;
                $vr->price = $request->price;
                $vr->shipping = $request->shipping;
                $vr->total = $request->total;
                $vr->tracking_no = $request->tracking_no;
                $vr->memo = $request->memo;

                if (!in_array($vr->pay_method, ['PayPal', 'Balance'])) {
                    if ($request->status != 'CP' && $request->status != 'PC') {
                        $vr->pay_method = $request->pay_method;
                    }
                }
            } else {
                // general request
                $vr->status = $request->status2;
                $vr->op_comments = $request->op_comments2;
            }


            $vr->modified_by = Auth::user()->user_id;
            $vr->mdate = Carbon::now();
            $vr->save();


            if ($vr->category == 'O') {
                // order
                $products = $request->products;

                foreach ($products as $o) {
                    $vrp = VRRequestProduct::findOrFail($o[0]); // id
                    $vrp->order_price = ($vrp->order_price / $vrp->qty) * $o[3]; // qty
                    $vrp->qty = $o[3]; // qty
                    $vrp->save();
                }

                # when COD collected (completed)
                if ($vr->status == 'C' && ($vr->pay_method == 'COD' || $vr->pay_method == 'Direct Deposit')) {

                    # insert promotion
                    $res = Helper::addPromotion($vr->id);
                    if (!empty($res)) {
                        return response()->json([
                            'msg' => $res
                        ]);
                    }
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

    public function update_kickback(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->kick_back = $request->kick_back;
            $vr->modified_by = Auth::user()->user_id;
            $vr->mdate = Carbon::now();
            $vr->update();

            $credit = new Credit();
            $credit->account_id = $vr->account_id;
            $credit->type = 'C';
            $credit->amt = $request->kick_back;
            $credit->comments = 'Purchase kickback by VR Orders of # '.$vr->id;
            $credit->cdate = Carbon::now();
            $credit->created_by = Auth::user()->user_id;
            $credit->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_memo(Request $request) {
        try {

            //dd($request->all());
            $v = Validator::make($request->all(), [
              'id' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                  'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->tracking_no = $request->tracking_no;
            $vr->memo = $request->memo;
            $vr->payment_note = $request->payment_note;
            $vr->vendor_note = $request->vendor_note;
            $vr->modified_by = Auth::user()->user_id;
            $vr->mdate = Carbon::now();
            $vr->update();

            return response()->json([
              'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }


    public function show(Request $request) {
        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $sdate = Carbon::today();
        $edate = Carbon::today()->addDays(1)->addSeconds(-1);

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $login_acct = Account::find(Auth::user()->account_id);

        $query = VirtualRep::join('users', 'users.user_id', '=', 'virtual_rep.created_by')
            ->join('accounts', 'accounts.id', '=', 'vr_request.account_id')
            ->where('accounts.path', 'like', $login_acct->path . '%');

        $query = $query->whereRaw('ifnull(virtual_rep.mdate, virtual_rep.cdate) >= ?', [$sdate])
            ->whereRaw('ifnull(virtual_rep.mdate, virtual_rep.cdate) < ? ', [$edate]);

        if (!empty($request->category)) {
            $query = $query->where('virtual_rep.category', $request->category);
        }

        if (!empty($request->status)) {
            $query = $query->where('virtual_rep.status', $request->status);
        }

        if (!empty($request->account_id)) {
            $query = $query->where('accounts.id', $request->account_id);
        }

        if (!empty($request->account_name)) {
            $query = $query->whereRaw('lower(accounts.name) like ?', ['%' . strtolower($request->account_name). '%']);
        }

        if (!empty($request->tracking_no)) {
            $query = $query->whereRaw('lower(virtual_rep.tracking_no) like ?', ['%' . strtolower($request->tracking_no) . '%']);
        }

        $records = $query->select('virtual_rep.*')->orderByRaw('ifnull(virtual_rep.mdate, virtual_rep.cdate) desc')->paginate();

        return view('admin.reports.virtual-rep', [
            'sdate' => $sdate->format('Y-m-d'),
            'edate' => $edate->format('Y-m-d'),
            'category' => $request->category,
            'status' => $request->status,
            'account_id' => $request->account_id,
            'account_name' => $request->account_name,
            'tracking_no' => $request->tracking_no,
            'records' => $records
        ]);
    }

    public function loadDetail(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required'
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

            $vr = VirtualRep::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->last_modified = $vr->last_modified;

            return response()->json([
                'msg' => '',
                'data' => $vr
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

            $vr = VirtualRep::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $vr->status = $request->status;
            $vr->op_comments = $request->op_comments;
            $vr->tracking_no = $request->tracking_no;
            $vr->modified_by = Auth::user()->user_id;
            $vr->mdate = Carbon::now();
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

    /* New Version */
    public function sales(Request $request) {
        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $sdate = Carbon::today();
        $edate = Carbon::today()->addDays(1)->addSeconds(-1);

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $login_acct = Account::find(Auth::user()->account_id);

        $query = VRRequest::join('vr_request_product', 'vr_request_product.vr_id', '=', 'vr_request.id')
            ->join('vr_product', 'vr_request_product.prod_id', '=', 'vr_product.id')
            ->join('accounts', 'vr_request.account_id', '=', 'accounts.id')
            ->where('vr_request.category', 'O')
            ->where('vr_request.status', '<>', 'CT')
            ->where('vr_product.sku', '<>', 'SHIPPINGFEE');

        $query = $query->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) >= ?', [$sdate])
            ->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) < ? ', [$edate]);

        if (!empty($request->status)) {
            $query = $query->where('vr_request.status', $request->status);
        }

        if (!empty($request->product)) {
            $query->whereRaw('lower(model) like \'%' . strtolower($request->product) . '%\'');
        }

        if (!empty($request->marketing)) {
            $query->whereRaw('lower(marketing) like \'%' . strtolower($request->marketing) . '%\'');
        }

        if (!empty($request->supplier)) {
            $query->whereRaw('lower(supplier) like \'%' . strtolower($request->supplier) . '%\'');
        }

        if (isset($request->is_consignment) && $request->is_consignment == 'Y') {
            $query = $query->where('vr_product.for_consignment', 'Y');
        }

        if (isset($request->is_dropship) && $request->is_dropship == 'Y') {
            $query = $query->where('vr_product.is_dropship', 'Y');
        }

        if (!empty($request->account)) {
            $query->whereRaw('(accounts.id = \'' . $request->account . '\' or lower(accounts.name) like \'%' . strtolower($request->account) . '%\')');
        }

        if (!empty($request->acct_ids)) {
            $acct_ids = preg_split('/[\ \r\n\,]+/', $request->acct_ids);
            $query = $query->whereIn('accounts.id', $acct_ids);
        }

        if (!empty($request->quick_note)) {
            $query->whereRaw(' lower(vr_request_product.quick_note) like \'%' . strtolower($request->quick_note) . '%\' ');
        }

        if ($request->excel == 'Y') {
            $records = $query->selectRaw('
                vr_request_product.qty, 
                vr_request_product.order_price,
                vr_request_product.quick_note, 
                vr_request.pay_method,
                vr_request.cdate,
                vr_request.status,
                vr_request.tracking_no,
                vr_product.id as prod_id,
                vr_product.category,
                vr_product.model,
                vr_product.marketing,
                vr_product.supplier,
                vr_product.subagent_price,
                vr_product.is_dropship,
                accounts.id as account_id,
                accounts.name as acct_name,
                accounts.type as acct_type,
                accounts.state,
                accounts.parent_id,
                accounts.contact,
                accounts.office_number,
                accounts.email,
                accounts.address1,
                accounts.address2,
                accounts.city,
                accounts.zip
            ')->get();

            foreach ($records as $r) {
                $r->status_name = VRRequest::get_status_name($r->status);
            }

            Excel::create('vr_sales', function($excel) use($records) {

                ini_set('memory_limit', '2048M');

                $excel->sheet('vr_sales', function($sheet) use($records) {

                    $data = [];
                    foreach ($records as $o) {

                        $parent = Account::find($o->parent_id);

                        $row = [
                            'Prod.ID' => $o->prod_id,
                            'Parent.ID' => $parent->id,
                            'Parent.Name' => $parent->name,
                            'Acct.Type' => $o->acct_type,
                            'Acct.ID' => $o->account_id,
                            'Acct.Name' => $o->acct_name,
                            'Addr1'     => $o->address1,
                            'Addr2'     => $o->address2,
                            'City'      => $o->city,
                            'State'     => $o->state,
                            'Zip'       => $o->zip,
                            'Contact Name'   => $o->contact,
                            'Tel'       => $o->office_number,
                            'email'     => $o->email,
                            'Dropshop'  => $o->is_dropship,
                            'Product' => $o->model,
                            'quick_notes' => $o->quick_note,
                            'Marketing' => $o->marketing,
                            'Category' => $o->category,
                            'Supplier' => $o->supplier,
                            'Qty' => $o->qty,
                            'Price' => $o->subagent_price,
                            'Total' => $o->order_price,
                            'Status' => $o->status_name,
                            'Payment' => $o->pay_method,
                            'Date & Time' => $o->cdate
                        ];

                        $data[] = $row;

                    }

                    $sheet->fromArray($data);

                });

            })->export('xlsx');

        }
        
        $summary = new \stdClass();
        $summary->qty = $query->sum('vr_request_product.qty');
        $summary->total = $query->sum('vr_request_product.order_price');

        $records = $query->selectRaw('
            vr_request_product.*, 
            vr_request.pay_method,
            vr_request.status,
            vr_request.tracking_no,
            vr_product.category,
            vr_product.model,
            vr_product.marketing,
            vr_product.supplier,
            vr_product.subagent_price,
            vr_product.sku,
            vr_request.account_id,
            accounts.name as acct_name,
            accounts.type as acct_type,
            accounts.state
        ')->orderByRaw('ifnull(vr_request.mdate, vr_request.cdate) desc')->paginate();

        //dd($records);
        return view('admin.reports.vr-sales', [
            'sdate'         => $sdate->format('Y-m-d'),
            'edate'         => $edate->format('Y-m-d'),
            'quick'         => $request->quick,
            'status'        => $request->status,
            'product'       => $request->product,
            'quick_note'    => $request->quick_note,
            'marketing'     => $request->marketing,
            'supplier'      => $request->supplier,
            'account'       => $request->account,
            'account_id'    => $request->account_id,
            'acct_ids'      => $request->acct_ids,
            'account_name'  => $request->account_name,
            'tracking_no'   => $request->tracking_no,
            'is_consignment' => isset($request->is_consignment) ? 'Y': 'N',
            'is_dropship'   => isset($request->is_dropship) ? 'Y': 'N',
            'records'       => $records,
            'summary'       => $summary
        ]);
    }

    public function additional_shipping_fee(Request $request) {
        try {

            $v = Validator::make($request->all(), [
              'id' => 'required',
              'additional_shipping_fee' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                  'msg' => 'Invalid ID provided'
                ]);
            }

            $product = VRProduct::where('sku', 'SHIPPINGFEE')->first();

            $vrn = VRRequest::where('account_id', $vr->account_id)->where('status', 'CT')->first();
            if (empty($vrn)) {
                $vrn = new VRRequest();
                $vrn->category = 'O';
                $vrn->account_id = $vr->account_id;
                $vrn->status = 'CT';
                $vrn->created_by = 'system';
                $vrn->cdate = Carbon::now();
                $vrn->save();
            }

            $vrp = VRRequestProduct::where('vr_id', $vrn->id)->where('prod_id', $product->id)->first();
            if (empty($vrp)) {
                $vrp = new VRRequestProduct();
                $vrp->vr_id         = $vrn->id;
                $vrp->prod_id       = $product->id;
                $vrp->prod_sku      = 'SHIPPINGFEE';
                $vrp->order_price   = $request->additional_shipping_fee;
                $vrp->qty           = 1;
                $vrp->sales_type    = 'S';
//                $vrp->is_dropship   = $product->is_dropship;
                $vrp->cdate         = Carbon::now();
                $vrp->created_by    = Auth::user()->user_id;
                $vrp->save();
            } else {
                $vrp->order_price   = $request->additional_shipping_fee;
                $vrp->qty           = 1;
                $vrp->cdate         = Carbon::now();
                $vrp->created_by    = Auth::user()->user_id;
                $vrp->update();
            }

            $vrn->price = VRRequestProduct::where('vr_id', $vrn->id)->sum('order_price');
            $vrn->shipping  = 0;
            $vrn->total     = $vrn->price + $vrn->shipping;
            $vrn->update();

            return response()->json([
              'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
              'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function apply_to_debit(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'amt' => 'required'
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

            $vr = VRRequest::find($request->id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid ID provided'
                ]);
            }

            $credit = new Credit;
            $credit->account_id = $vr->account_id;
            $credit->type = 'D';
            $credit->amt = $request->amt;
            $credit->comments = 'Shipping Fee by vr_order ['.$vr->id.']';
            $credit->created_by = Auth::user()->user_id;
            $credit->cdate = Carbon::now();
            $credit->save();

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