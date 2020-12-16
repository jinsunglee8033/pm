<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/12/17
 * Time: 2:51 PM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use App\Lib\Permission;
use App\Lib\SimValueBinder;
use App\Model\VRProduct2;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Input;


class VRUpload2Controller extends Controller
{

    public function show(Request $request) {

        if(!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $data = VRProduct2::whereNotNull('cdate');


        if (!empty($request->product_name)) {
            $data = $data->whereRaw('upper(product_name) like ?', ['%' . strtoupper($request->product_name). '%']);
        }

        if (!empty($request->carrier)) {
            $data = $data->whereRaw('upper(carrier) like ?', ['%' . strtoupper($request->carrier). '%']);
        }

        if (!empty($request->category)) {
            $data = $data->whereRaw('upper(category) like ?', ['%' . strtoupper($request->category). '%']);
        }

        if (!empty($request->memo1)) {
            $data = $data->whereRaw('upper(memo1) like ?', ['%' . strtoupper($request->memo1). '%']);
        }

        if (!empty($request->memo2)) {
            $data = $data->whereRaw('upper(memo2) like ?', ['%' . strtoupper($request->memo2). '%']);
        }

        if (!empty($request->comment)) {
            $data = $data->whereRaw('upper(comment) like ?', ['%' . strtoupper($request->comment). '%']);
        }

        if (!empty($request->status)) {
            $data = $data->where('status', strtoupper($request->status));
        }

        $data = $data->orderBy('id', 'desc')->paginate(20);

        $carriers = VRProduct2::select('carrier')->whereNotNull('carrier')->groupBy('carrier')->get();
        $categories = VRProduct2::select('category')->whereNotNull('category')->groupBy('category')->get();
        $statuss = VRProduct2::select('status')->where('status', '<>', 'D')->whereNotNull('status')->groupBy('status')->get();

        return view('admin.settings.vr-upload2', [
            'data' => $data,
            'carrier' => $request->carrier,
            'category' => $request->category,
            'status' => $request->status,
            'product_name' => $request->product_name,
            'memo1' => $request->memo1,
            'memo2' => $request->memo2,
            'comment' => $request->comment,
            'carriers' => $carriers,
            'categories' => $categories,
            'statuss'   => $statuss
        ]);
    }

    public function update(Request $request) {

        try {

            $v = Validator::make($request->all(),
                [
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

            $vr = VRProduct2::where('id', $request->prod_id)->first();
            if (empty($vr)) {
                return response()->json([
                    'msg' => 'Invalid product'
                ]);
            }

            $vr->status = $request->status;

            $url = $vr->url;
            $file = substr($url, 9);

            if($request->status == 'I'){
                $vr->url =  '/img/vr3/' . $file;
            }else{
                $vr->url =  '/img/vr2/' . $file;
            }

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

    public function copy_update(Request $request) {

        try {

            $product = new VRProduct2();

            $product->carrier = strtoupper($request->carrier);
            $product->category = strtoupper($request->category);
            $product->product_name = $request->product_name;
            $product->memo1 = $request->memo1;
            $product->memo2 = $request->memo2;
            $product->comment = $request->comment;
            $product->status = 'A';
            $product->cdate = Carbon::now();

            $product->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }

    }

    public function upload_image(Request $request) {
        try{
            $product = VRProduct2::find($request->prod_id);

            $file = $request->image;

            if (empty($file)) {
                return response()->json([
                    'msg' => 'No image chosen'
                ]);
            }

            if (!$file->isValid()) {
                return response()->json([
                    'msg' => 'Not valid image'
                ]);
            }

            Helper::log('XXX UPLOAD IMAGE FILE NAME XXX', [
                'ID'    => $request->prod_id,
                'FILE NAME' => $file->path()
            ]);

            if (!empty($product->url)) {
                unlink(public_path() . $product->url);
            }

            $photoName = time() . '.' . $file->getClientOriginalExtension();
            $path = $file->move(public_path('img/vr2'), $photoName);
            $product->url = '/img/vr2/' . $photoName;
            $product->update();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }


    public function show_detail(Request $request) {
        try{
            $product = VRProduct2::find($request->prod_id);

            if (empty($product)) {
                return response()->json([
                    'msg' => 'Product not available !!'
                ]);
            }

            return response()->json([
                'msg'   => '',
                'data'  => $product
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function update_detail(Request $request) {
        try{
            $product = VRProduct2::find($request->u_id);

            if (empty($product)) {
                return response()->json([
                    'msg' => 'Product not available !!'
                ]);
            }

            $product->carrier = strtoupper($request->carrier);
            $product->category = strtoupper($request->category);
            $product->product_name = $request->product_name;
            $product->memo1 = $request->memo1;
            $product->memo2 = $request->memo2;
            $product->comment = $request->comment;
            $product->cdate = Carbon::now();
            $product->update();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    public function add_detail(Request $request) {

        try {

            $product = new VRProduct2();

            $product->carrier = strtoupper($request->carrier);
            $product->category = strtoupper($request->category);
            $product->product_name = $request->product_name;
            $product->memo1 = $request->memo1;
            $product->memo2 = $request->memo2;
            $product->comment = $request->comment;
            $product->status = 'A';
            $product->cdate = Carbon::now();
            $product->save();

            return response()->json([
                'msg' => ''
            ]);

        } catch (\Exception $ex) {
            return response()->json([
                'msg' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

    private function output_error($msg) {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.myApp.showError(\"" . str_replace("\"", "'", $msg) . "\");";
        echo "</script>";
        exit;
    }

    private function output_success() {
        echo "<script>";
        echo "parent.myApp.hideLoading();";
        echo "parent.close_modal();";
        echo "</script>";
        exit;
    }

}