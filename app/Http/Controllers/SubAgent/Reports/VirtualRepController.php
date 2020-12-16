<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/27/17
 * Time: 4:15 PM
 */

namespace App\Http\Controllers\SubAgent\Reports;

use App\Http\Controllers\Controller;
use App\Model\Promotion;
use Illuminate\Http\Request;
use App\Lib\Helper;
use App\Model\VirtualRep;
use App\Model\VRRequest;
use App\Model\VRPayment;
use App\Model\VRRequestProduct;
use App\Model\VRProduct;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use Excel;

class VirtualRepController extends Controller
{
    public function showRequest(Request $request) {
        $sdate = Carbon::today()->subDays(30);
        $edate = Carbon::today();
        $edate = Carbon::createFromFormat('Y-m-d H:i:s', $edate->format('Y-m-d') . ' 23:59:59');

        if (!empty($request->sdate) && empty($request->id)) {
            $sdate = Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00');
        }

        if (!empty($request->edate) && empty($request->id)) {
            $edate = Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 23:59:59');
        }

        $login_acct = Account::find(Auth::user()->account_id);

        $query = VRRequest::join('accounts', 'accounts.id', '=', 'vr_request.account_id')
            ->where('accounts.path', 'like', $login_acct->path . '%');

        $query = $query->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) >= ?', [$sdate])
            ->whereRaw('ifnull(vr_request.mdate, vr_request.cdate) <= ?', [$edate]);

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

        return view('sub-agent.reports.vr-request', [
            'sdate' => $sdate->format('Y-m-d'),
            'edate' => $edate->format('Y-m-d'),
            'category' => $request->category,
            'status' => $request->status,
            'records' => $records
        ]);
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

    public function cancelRequest(Request $request) {
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

            $vr->status = 'CC';
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


    public function show(Request $request) {
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
            ->join('accounts', 'accounts.id', '=', 'users.account_id')
            ->where('accounts.path', 'like', $login_acct->path . '%');

        $query = $query->whereRaw('ifnull(virtual_rep.mdate, virtual_rep.cdate) >= ?', [$sdate])
            ->whereRaw('ifnull(virtual_rep.mdate, virtual_rep.cdate) < ?', [$edate]);

        if (!empty($request->category)) {
            $query = $query->where('virtual_rep.category', $request->category);
        }

        if (!empty($request->status)) {
            $query = $query->where('virtual_rep.status', $request->status);
        }

        $records = $query->select('virtual_rep.*')->orderByRaw('ifnull(virtual_rep.mdate, virtual_rep.cdate) desc')->paginate();

        return view('sub-agent.reports.virtual-rep', [
            'sdate' => $sdate->format('Y-m-d'),
            'edate' => $edate->format('Y-m-d'),
            'category' => $request->category,
            'status' => $request->status,
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

    public function addPayPal(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'vr_id' => 'required',
                'amt' => 'required|numeric',
                'payer_id' => 'required',
                'payment_id' => 'required',
                'payment_token' => 'required'
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

            $account = Account::find($request->account_id);
            if (empty($account)) {
                return response()->json([
                    'msg' => 'Invalid account ID provided'
                ]);
            }

            $vr = VRRequest::find($request->vr_id);
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid VR ID provided'
                ]);
            }
            if ($request->amt != $vr->total) {
                return response()->json([
                    'msg' => 'Order amount and payment amount are not match'
                ]);
            }


            $payment = new VRPayment;
            $payment->vr_id = $request->vr_id;
            $payment->account_id = $request->account_id;
            $payment->type = 'PayPal'; # paypal always for now.
            $payment->amt = $request->amt;
            $payment->comments = $request->comments;

            $payment->payer_id = $request->payer_id;
            $payment->payment_id = $request->payment_id;
            $payment->payment_token = $request->payment_token;

            $payment->created_by = Auth::user()->user_id;
            $payment->cdate = Carbon::now();
            $payment->save();

            $vr->status = 'PC'; // Change status to 'Paid'
            $vr->save();


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


            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('balance@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }
            //Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);


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