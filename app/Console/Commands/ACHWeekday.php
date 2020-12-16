<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\ACHPosting;
use App\Model\Payment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ACHWeekday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ach:weekday';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $email_msg = '';

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

            ### create ach_posting record for weekday ACH ###
            ### TODO : modify ach:post to run on every weekday except and to include type = 'W' ( weekday ) as well ###

            $this->output('#### Weekday ACH processing ###');

            $weekday = Carbon::today()->dayOfWeek;
            if (!in_array($weekday, [2,3,4,5])) {
                $this->output(' - no need to run.');
                exit;
            }

            ### 1. get all post pay sub-agents : with pay_method = 'C' ###
            $query = Account::where('pay_method', 'C')
                ->where('type','S')
                ->where('status', 'A');

            switch (Carbon::today()->dayOfWeek) {
                case 2:
                    $this->output(' - Tuesday processing started... ');
                    $query = $query->where('ach_tue', 'Y');
                    break;
                case 3:
                    $this->output(' - Wednesday processing started... ');
                    $query = $query->where('ach_wed', 'Y');
                    break;
                case 4:
                    $this->output(' - Thursday processing started... ');
                    $query = $query->where('ach_thu', 'Y');
                    break;
                case 5:
                    $this->output(' - Fri processing started... ');
                    $query = $query->where('ach_fri', 'Y');
                    break;
            }

            $accounts = $query->get();
            $this->output(' - total accounts to process: ' . count($accounts));

            $bill_date = Carbon::today()->startOfWeek()->addDays(7);
            $period_from = $bill_date->copy()->subDays(7);

            ### for easier check-up, cut off up to yesterday balance ###
            $period_to = Carbon::today()->subDay();//$bill_date->copy()->subDays(1);

            $skipped_qty = 0;

            DB::beginTransaction();

            if (count($accounts) > 0) {
                foreach ($accounts as $o) {
                    ### 2. get expected bill amount of the sub-agent for next week Monday ###
                    $ret = PaymentProcessor::get_sub_agent_balance($o->id, $bill_date, $period_from, $period_to);
                    $ach_paid_amt = $ret['ach_paid_amt'];

                    ### charge via weekday ACH only when there is amount to collect ###
                    if ($ach_paid_amt <= 0) {
                        $skipped_qty++;
                        $this->output(' - processing ' . $o->id . ' : No ACH amount to collect.');
                        continue;
                    }

                    $this->output(' - processing ' . $o->id . ' : $' . number_format($ach_paid_amt));

                    ### create ACH posting record ###
                    $post = new ACHPosting;
                    $post->type = 'W';
                    $post->account_id = $o->id;
                    $post->ach_bank = $o->ach_bank;
                    $post->ach_holder = $o->ach_holder;
                    $post->ach_routeno = $o->ach_routeno;
                    $post->ach_acctno = $o->ach_acctno;
                    $post->amt = $ach_paid_amt;
                    $post->cdate = Carbon::now();
                    $post->save();

                    ### make weekday payment record ###
                    $payment = new Payment;
                    $payment->account_id = $o->id;
                    $payment->type = 'W'; # W: Weekday
                    $payment->method = 'A'; # A: ACH
                    $payment->category = 'Weekday ACH';
                    $payment->deposit_amt = $ach_paid_amt;
                    $payment->fee = 0;
                    $payment->amt = $ach_paid_amt;
                    $payment->comments = 'Weekday ACH';
                    $payment->ach_posting_id = $post->id;
                    $payment->cdate = Carbon::now();
                    $payment->created_by = 'system';
                    $payment->save();
                }
            }

            DB::commit();

            $this->output(' - total ignored accounts: ' . $skipped_qty);
            $this->output(' - done.');

            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ACH Weekday Success', $this->email_msg);

        } catch (\Exception $ex) {

            DB::rollback();
            $this->output(' - exception: ' . $ex->getMessage() . ' [' . $ex->getCode() . ']: ' . $ex->getTraceAsString());

            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ACH Weekday Failure', $this->email_msg);
        }

    }

    private function output($msg) {
        $this->info($msg);
        $this->email_msg .= $msg . "<br/>";
    }
}
