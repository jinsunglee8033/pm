<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/16/17
 * Time: 5:18 PM
 */

namespace App\Http\Controllers\SubAgent\Setting;

use App\Http\Controllers\Controller;
use App\Lib\Helper;
use Illuminate\Http\Request;
use Validator;
use Session;
use Auth;
use Log;
use Excel;
use Carbon\Carbon;
use App\User;
use App\Model\AccountFile;
use Illuminate\Support\Facades\Input;

class DocumentsController extends Controller
{

    public function show() {
        //$files = AccountFile::where('account_id', Auth::user()->account_id);
        $types = AccountFile::types();
        $files = [];
        foreach ($types as $k => $v) {
            $file = AccountFile::where('account_id', Auth::user()->account_id)
                ->where('type', $k)
                ->first();
            $o = new \stdClass();
            $o->key = $k;
            $o->label = $v;
            $o->file = $file;
            $files[] = $o;
        }
        return view('sub-agent.setting.documents', [
            'files' => $files
        ]);
    }

    public function show_h2o() {
        $types = AccountFile::h2o_types();
        $files = [];
        foreach ($types as $k => $v) {
            $file = AccountFile::where('account_id', Auth::user()->account_id)
                ->where('type', $k)
                ->first();
            $o = new \stdClass();
            $o->key = $k;
            $o->label = $v;
            $o->file = $file;
            $files[] = $o;
        }
        return view('sub-agent.setting.h2o-documents', [
            'files' => $files
        ]);
    }

    public function post(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'file_type' => 'required'
            ]);

            if ($v->fails()) {
                return back()->withErrors($v)->withInput();
            }

            $key = $request->file_type;

            if (Input::hasFile($key) && Input::file($key)->isValid()) {
                $path = Input::file($key)->getRealPath();

                Helper::log('### FILE ###', [
                    'key' => $key,
                    'path' => $path
                ]);

                $contents = file_get_contents($path);
                $name = Input::file($key)->getClientOriginalName();

                $file = AccountFile::where('account_id', Auth::user()->account_id)
                    ->where('type', $key)
                    ->first();
                if (empty($file)) {
                    $file = new AccountFile;
                }

                $file->type = $key;
                $file->account_id = Auth::user()->account_id;
                $file->data = base64_encode($contents);
                $file->file_name = $name;
                $file->created_by = Auth::user()->user_id;
                $file->cdate = Carbon::now();
                $file->save();
            } else {
                return back()->withErrors([
                    $key => 'Please choose file first: ' . $key
                ])->withInput();
            }

            return back()->with([
                'success' => 'Your request has been processed successfully!'
            ]);

        } catch (\Exception $ex) {
            return back()->withErrors([
                'exception' => $ex->getMessage() . ' [' . $ex->getCode() . ']'
            ]);
        }
    }

}