<?php
/**
 * Created by Royce.
 * Date: 6/27/18
 */

namespace App\Http\Controllers\Admin\Account;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\Permission;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Model\Account;
use App\Model\ConsignmentVendor;
use App\Model\Credit;

class ConsignmentVendorController extends Controller
{

    public function add(Request $request) {
        try {

            if (!Permission::can($request->path(), 'modify')) {
                return response()->json([
                    'msg' => 'You are not authorized to modify any information'
                ]);
            }

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'type' => 'required|in:C,D',
                'amt' => 'required|numeric',
                'comments' => 'required'
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

            if ($request->amt <= 0) {
                return response()->json([
                    'msg' => 'Invalid amount.'
                ]);
            }

            if ($request->type == 'D') {
                $balance = ConsignmentVendor::get_balance($request->account_id);

                if (round($balance,2) < round($request->amt, 2)) {
                    return response()->json([
                        'msg' => 'Invalid amount. Consignment balance is $' . $balance
                    ]);
                }
            }

            $consignment = new ConsignmentVendor();
            $consignment->account_id = $request->account_id;
            $consignment->type = $request->type;
            $consignment->amt = $request->amt;
            $consignment->comments = $request->comments;
            $consignment->paid_memo = $request->paid_memo;
            if ($request->type == 'D') {
                $consignment->do_ach = $request->do_ach;
            }
            $consignment->created_by = Auth::user()->user_id;
            $consignment->cdate = Carbon::now();
            $consignment->save();

            if ($request->type == 'D' && $consignment->do_ach == 'Y') {
                $credit = new Credit();
                $credit->account_id = $request->account_id;
                $credit->type = 'C';
                $credit->amt = $request->amt;
                $credit->comments = 'Send reverse consignment. [' . $consignment->id . ']';
                $credit->paid_memo = $request->paid_memo;
                $credit->cdate = Carbon::now();
                $credit->created_by = Auth::user()->user_id;
                $credit->save();
            }

            # Send credit email to payment@softpayplus.com
            $subject = "New " . $consignment->type_name . " Record (Acct.ID : " . $consignment->account_id . ", Amount : $" . $consignment->amt . ")";
            $msg = "<b>Success Payment</b> <br/><br/>";
            $msg .= "Acct.ID - " . $consignment->account_id . "<br/>";
            $msg .= "Type - " . $consignment->getTypeNameAttribute() . "<br/>";
            $msg .= "Amount - $" . $consignment->amt . "<br/>";
            $msg .= "Comment - " . $consignment->comments . "<br/>";
            $msg .= "Paid.Memo - " . $consignment->paid_memo . "<br/>";
            $msg .= "Created.By - " . $consignment->created_by . "<br/>";
            $msg .= "Date - " . $consignment->cdate . "<br/>";


            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('payment@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }
            // Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);


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

            $consignment = ConsignmentVendor::where('id', $request->cv_id)->first();
            $consignment->comments = $request->comments;
            $consignment->paid_memo = $request->paid_memo;
            $consignment->status = $request->status;
            $consignment->update();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function getList(Request $request) {

        try {

            $v = Validator::make($request->all(), [
                'account_id' => 'required',
                'sdate' => 'required|date',
                'edate' => 'required|date',
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

            $data = ConsignmentVendor::where('account_id', $request->account_id)
                ->where('cdate', '>=', Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00'))
                ->where('cdate', '<', Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 00:00:00')->addDay());

            if (!empty($request->type)) {
                $data = $data->where('type', $request->type);
            }

            if (!empty($request->comments)) {
                $data = $data->whereRaw('lower(comments) like ?', ['%' . strtolower($request->comments) . '%']);
            }

            if (!empty($request->paid_memo)) {
                $data = $data->whereRaw('lower(paid_memo) like ?', ['%' . strtolower($request->paid_memo) . '%']);
            }

            if (!empty($request->status)) {
                if($request->status == 'N'){
                    $data = $data->where('status', $request->status)->where('type', 'C');
                }
                $data = $data->where('status', $request->status);
            }

            $data = $data->orderBy('cdate', 'desc')
                ->get();

            return response()->json([
                'msg' => '',
                'data' => $data
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

}