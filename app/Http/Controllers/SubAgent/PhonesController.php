<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/8/17
 * Time: 2:29 PM
 */

namespace App\Http\Controllers\SubAgent;

use App\Http\Controllers\Controller;
use App\Model\AccountFile;
use Auth;
use Illuminate\Http\Request;
use App\Lib\eSignature;
use Carbon\Carbon;

class PhonesController extends Controller
{

    public function show() {
        return view('sub-agent.phones');
    }

}