<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/9/17
 * Time: 10:45 AM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Model\Phones;
use Auth;
use Validator;
use Input;

class PhonesController extends Controller
{

    public function show() {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        return view('admin.settings.phones', [
            'show_detail' => 'N',
            'id' => '',
            'sdate' => '',
            'edate' => '',
            'phones' => null
        ]);
    }

    public function add(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'name' => 'required',
                'upload_file' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                $this->output($msg);
            }

            if (Input::hasFile('upload_file') && Input::file('upload_file')->isValid()) {
                $path = Input::file('upload_file')->getRealPath();
                $image = file_get_contents($path);
            } else {

                $this->output('Please choose valid phone image file to upload');
            }

            $phone = new Phones;
            $phone->name = $request->name;
            $phone->image = base64_encode($image);
            $phone->linke = $request->link;
            $phone->status = $request->status;
            $phone->created_by = Auth::user()->user_id;
            $phone->cdate = Carbon::now();
            $phone->save();

            $this->output('Your request has been processed successfully!', $close_modal = true, $is_error = false, $phone->id);

        } catch (\Exception $ex) {

            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function update(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'status' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "<br/>") . $v[0];
                }

                $this->output($msg);
            }


            $phone = Phones::find($request->id);
            if (empty($phone)) {
                $this->output('Invalid phone ID provided');
            }

            $phone->name = $request->name;
            if (Input::hasFile('upload_file') && Input::file('upload_file')->isValid()) {
                $path = Input::file('upload_file')->getRealPath();
                $image = file_get_contents($path);
                $phone->image = base64_encode($image);
            }

            $phone->link = $request->link;
            $phone->status = $request->status;
            $phone->modified_by = Auth::user()->user_id;
            $phone->mdate = Carbon::now();
            $phone->save();

            $this->output('Your request has been processed successfully!', $close_modal = true, $is_error = false, $phone->id);

        } catch (\Exception $ex) {
            $this->output($ex->getMessage() . ' [' . $ex->getCode() . ']');
        }
    }

    public function detail(Request $request) {
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

            $news = News::find($request->id);
            if (empty($news)) {
                return [
                    'msg' => 'Invalid news ID provided'
                ];
            }

            return response()->json([
                'msg' => '',
                'data' => $news
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    private function output($msg, $close_modal = false, $is_error = true, $id = null) {
        echo "<script>";

        if (is_null($id)) {
            $id= '';
        }

        if ($close_modal) {
            echo "parent.close_modal('div_account_detail', '$id');";
        }

        if ($is_error) {
            echo "parent.myApp.hidePleaseWait();";
            echo "parent.myApp.showError(\"$msg\");";
        } else {
            echo "parent.myApp.hidePleaseWait();";
            echo "parent.myApp.showSuccess(\"$msg\");";
        }

        echo "</script>";
        exit;
    }
}