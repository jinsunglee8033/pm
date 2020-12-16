<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 3/22/17
 * Time: 2:53 PM
 */

namespace App\Http\Controllers\SubAgent\Phones;

use App\Http\Controllers\Controller;
use App\Model\AccountFile;
use Auth;
use Illuminate\Http\Request;
use App\Lib\eSignature;
use Carbon\Carbon;
use App\Model\Account;
use DB;

class AccessoryController extends Controller
{

    public function show() {
        return view('sub-agent.phones.accessory');
    }

}