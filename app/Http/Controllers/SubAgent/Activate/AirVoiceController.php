<?php
/**
 * Created by Royce
 * Date: 6/22/18
 */

namespace App\Http\Controllers\SubAgent\Activate;


use App\Events\TransactionStatusUpdated;
use App\Lib\boom;
use App\Lib\ConsignmentProcessor;
use App\Lib\Helper;
use App\Lib\liberty;
use App\Lib\PaymentProcessor;
use App\Lib\RebateProcessor;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\gen;
use App\Model\Account;
use App\Model\Carrier;
use App\Model\Denom;
use App\Model\GenActivation;
use App\Model\GenFee;
use App\Model\LbtActivation;
use App\Model\PmModelSimLookup;
use App\Model\Product;
use App\Model\State;
use App\Model\Transaction;
use App\Model\VendorDenom;
use App\Model\Promotion;
use App\Model\SpiffSetupSpecial;

use App\Model\StockSim;
use App\Model\StockESN;
use App\Model\StockMapping;

use App\Model\Zip;
use Carbon\Carbon;
use function Couchbase\defaultDecoder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AirVoiceController
{
    public function show(Request $request) {

        $account = Account::find(Auth::user()->account_id);
        if (empty($account)) {
            return redirect('/sub-agent/error')->with([
              'error_msg' => 'Your session has been expired! Please login again'
            ]);
        }

        return view('sub-agent.activate.airvoice')->with([
        ]);
    }

}