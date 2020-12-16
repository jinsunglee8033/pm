<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/31/17
 * Time: 9:48 AM
 */

namespace App\Http\Controllers\SubAgent\Activate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Product;
use App\Model\Denom;
use App\Model\State;
use App\Model\Transaction;
use App\Model\Account;
use Validator;
use Carbon\Carbon;
use Session;
use Auth;
use Log;
use Helper;
use App\Events\TransactionStatusUpdatedRoot;

class PatriotController extends Controller
{
    public function show() {
        return view('sub-agent.activate.patriot');
    }
}