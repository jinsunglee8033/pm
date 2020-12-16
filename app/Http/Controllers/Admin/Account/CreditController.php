<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 2/5/18
 * Time: 3:44 PM
 */

namespace App\Http\Controllers\Admin\Account;


use App\Model\Credit;
use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\Permission;
use App\Model\Account;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CreditController extends Controller
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



            $credit = new Credit;
            $credit->account_id = $request->account_id;
            $credit->type = $request->type;
            $credit->amt = $request->amt;
            $credit->comments = $request->comments;
            $credit->created_by = Auth::user()->user_id;
            $credit->cdate = Carbon::now();
            $credit->save();

            # Send credit email to payment@softpayplus.com
            $subject = "New " . $credit->type_name . " Record (Acct.ID : " . $credit->account_id . ", Amount : $" . $credit->amt . ")";
            $msg = "<b>Success Payment</b> <br/><br/>";
            $msg .= "Acct.ID - " . $credit->account_id . "<br/>";
            $msg .= "Type - " . $credit->getTypeNameAttribute() . "<br/>";
            $msg .= "Amount - $" . $credit->amt . "<br/>";
            $msg .= "Comment - " . $credit->comments . "<br/>";
            $msg .= "Created.By - " . $credit->created_by . "<br/>";
            $msg .= "Date - " . $credit->cdate . "<br/>";


            if (getenv('APP_ENV') == 'production') {
                Helper::send_mail('payment@softpayplus.com', $subject, $msg);
            } else {
                Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);
            }
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . ']' . $subject, $msg);


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

            $data = Credit::where('account_id', $request->account_id)
                ->where('cdate', '>=', Carbon::createFromFormat('Y-m-d H:i:s', $request->sdate . ' 00:00:00'))
                ->where('cdate', '<', Carbon::createFromFormat('Y-m-d H:i:s', $request->edate . ' 00:00:00')->addDay());

            if (!empty($request->type)) {
                $data = $data->where('type', $request->type);
            }

            if (!empty($request->comments)) {
                $data = $data->whereRaw('lower(comments) like ?', ['%' . strtolower($request->comments) . '%']);
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