<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/23/17
 * Time: 10:19 AM
 */

namespace App\Http\Controllers;

use App\Lib\Helper;
use Illuminate\Http\Request;
use Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use App\Model\CKEditorFile;
use Log;
use Auth;

class CKEditorController extends Controller
{

    public function upload(Request $request) {
        try {

            $v = Validator::make($request->all(), [
                'upload' => 'required'
            ]);

            if ($v->fails()) {
                $msg = '';
                foreach ($v->messages()->toArray() as $k => $v) {
                    $msg .= (empty($msg) ? '' : "|") . $v[0];
                }

                return response()->json([
                    'uploaded' => 0,
                    'error' => [
                        'message' => $msg
                    ]
                ]);
            }

            $key = 'upload';
            if (Input::hasFile($key) && Input::file($key)->isValid()) {
                $path = Input::file($key)->getRealPath();

                Helper::log('### FILE ###', [
                    'key' => $key,
                    'path' => $path
                ]);

                $contents = file_get_contents($path);
                $name = Input::file($key)->getClientOriginalName();

                $file = new CKEditorFile;
                $file->file_name = $name;
                $file->data = base64_encode($contents);
                $file->created_by = Auth::user()->user_id;
                $file->cdate = Carbon::now();
                $file->save();

                return response()->json([
                    'uploaded' => 1,
                    'fileName' => $file->file_name,
                    'url' => '/ckeditor/download/' . $file->id
                ]);

            } else {
                throw new \Excepion('Invalid file provided');
            }

        } catch (\Exception $ex) {
            return response()->json([
                'uploaded' => 0,
                'error' => [
                    'message' => $ex->getMessage() . ' (' . $ex->getCode() . ')'
                ]
            ]);
        }
    }

}