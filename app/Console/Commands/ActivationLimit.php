<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 12/11/18
 * Time: 4:08 PM
 */

namespace App\Console\Commands;

use App\Http\Controllers\ATT\RechargeController;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\AccountActivationLimit;

use App\Model\Transaction;
use Illuminate\Console\Command;

class ActivationLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activation:limit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activation Limit by hourly, daily, weekly, monthly';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $threshold_activations = Helper::check_threshold_activations_by_account(5);

            if (!empty($threshold_activations) && count($threshold_activations) > 0) {
                $email_msg = '';

                $default_limit = AccountActivationLimit::where('account_id', 100000)->first();
                foreach ($threshold_activations as $t) {
                    $account = Account::find($t->account_id);
                    $activation_limit = AccountActivationLimit::where('account_id', $t->account_id)->first();
                    if (empty($activation_limit)) {
                        $activation_limit = $default_limit;
                    }

                    $msg = '';

                    ## Preload
                    if ($t->hourly_preload >= $activation_limit->hourly_preload - 2) {
                        $msg .= '<br>[Preload] - Recent one hour activations: ' . $t->hourly_preload . '. / Limit: ' . $activation_limit->hourly_preload;
                    }
                    if ($t->daily_preload >= $activation_limit->daily_preload - 2) {
                        $msg .= '<br>[Preload] - Recent one day activations: ' . $t->daily_preload . '. / Limit:  ' . $activation_limit->daily_preload;
                    }
                    if ($t->weekly_preload >= $activation_limit->weekly_preload - 2) {
                        $msg .= '<br>[Preload] - Recent one week activations: ' . $t->weekly_preload . '. / Limit:  ' . $activation_limit->weekly_preload;
                    }
                    if ($t->monthly_preload >= $activation_limit->monthly_preload - 2) {
                        $msg .= '<br>[Preload] - Recent one month activations: ' . $t->monthly_preload . '. / Limit:  ' . $activation_limit->monthly_preload;
                    }

                    ## Regular
                    if ($t->hourly_regular >= $activation_limit->hourly_regular - 2) {
                        $msg .= '<br>[Regular] - Recent one hour activations: ' . $t->hourly_regular . '. / Limit:  ' . $activation_limit->hourly_regular;
                    }
                    if ($t->daily_regular >= $activation_limit->daily_regular - 2) {
                        $msg .= '<br>[Regular] - Recent one day activations: ' . $t->daily_regular . '. / Limit:  ' . $activation_limit->daily_regular;
                    }
                    if ($t->weekly_regular >= $activation_limit->weekly_regular - 2) {
                        $msg .= '<br>[Regular] - Recent one week activations: ' . $t->weekly_regular . '. / Limit:  ' . $activation_limit->weekly_regular;
                    }
                    if ($t->monthly_regular >= $activation_limit->monthly_regular - 2) {
                        $msg .= '<br>[Regular] - Recent one month activations: ' . $t->monthly_regular . '. / Limit:  ' . $activation_limit->monthly_regular;
                    }

                    ## BYOS
                    if ($t->hourly_byos >= $activation_limit->hourly_byos - 2) {
                        $msg .= '<br>[BYOS] - Recent one hour activations: ' . $t->hourly_byos . '. / Limit:  ' . $activation_limit->hourly_byos;
                    }
                    if ($t->daily_byos >= $activation_limit->daily_byos - 2) {
                        $msg .= '<br>[BYOS] - Recent one day activations: ' . $t->daily_byos . '. / Limit:  ' . $activation_limit->daily_byos;
                    }
                    if ($t->weekly_byos >= $activation_limit->weekly_byos - 2) {
                        $msg .= '<br>[BYOS] - Recent one week activations: ' . $t->weekly_byos . '. / Limit:  ' . $activation_limit->weekly_byos;
                    }
                    if ($t->monthly_byos >= $activation_limit->monthly_byos - 2) {
                        $msg .= '<br>[BYOS] - Recent one month activations: ' . $t->monthly_byos . '. / Limit:  ' . $activation_limit->monthly_byos;
                    }

                    if (!empty($msg)) {
                        $email_msg .= '<br><br> - Account : (' . $account->id . ') ' . $account->name . $msg;
                    }
                }

                if (!empty($email_msg)) {
                    helper::send_mail(
                        'it@perfectmobileinc.com',
                        '[PM][' . getenv('APP_ENV') . '] Threshold of Activations',
                        $email_msg
                    );

                    helper::send_mail(
                        'tom@perfectmobileinc.com',
                        '[PM][' . getenv('APP_ENV') . '] Threshold of Activations',
                        $email_msg
                    );
                }
            }

//            $transactions = Transaction::where('status', 'I')
//                ->whereRaw("invoice_number in (select invoice_number from paypal_log where invoice_number like '%E-%' and payment_status = 'Completed')")
//                ->get();
//            foreach ($transactions as $transaction) {
//                switch ($transaction->product_id) {
//                    case 'WATTR':
//                        RechargeController::process_after_pay($transaction->invoice_number);
//                        break;
//                }
//            }

        } catch (\Exception $ex) {
            Helper::log('#### EXCEPTION ####', $ex->getTraceAsString());
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] Activation limit Check Process Failure', $msg);
        }
    }
}
