<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Model\Account;
use App\Model\ACHPosting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CashPickup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:cash-pickup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily update cash pickup';

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

            ### create ach_posting record for prepay cash pickup everyday###
            $this->output('#### Posting Cash Pickup processing ###');

            ### 1. get all cash pickup (pre pay) of sub-agents : with pay_method = 'C' ###
            $today = \Carbon\Carbon::today()->format('Y-m-d');
            $yesterday = \Carbon\Carbon::yesterday()->format('Y-m-d');

            $accounts = Account::join('payment', 'payment.collection_id', '=', 'accounts.id')
                ->where('payment.cdate','<',$today)
                ->where('payment.cdate','>',$yesterday)
                ->where('payment.type', '=', 'P')
                ->where('payment.method', '=', 'H')
                ->select('payment.account_id as id', 'payment.deposit_amt as deposit_amt', 'accounts.ach_bank as ach_bank',
                        'accounts.ach_routeno as ach_routeno', 'accounts.ach_acctno as ach_acctno', 'accounts.ach_holder as ach_holder')
                ->get();

            $this->output(' - total accounts to process: ' . count($accounts));

            DB::beginTransaction();

            if (count($accounts) > 0) {
                foreach ($accounts as $o) {
                    ### 2. get expected bill amount of the sub-agent for next week Monday ###

                    $this->output(' - processing ' . $o->id . ' : $' . number_format($o->deposit_amt));

                    ### create ACH posting record ###
                    $post = new ACHPosting;
                    $post->type = 'P';
                    $post->account_id = $o->id;
                    $post->ach_bank = $o->ach_bank;
                    $post->ach_holder = $o->ach_holder;
                    $post->ach_routeno = $o->ach_routeno;
                    $post->ach_acctno = $o->ach_acctno;
                    $post->amt = $o->deposit_amt;
                    $post->cdate = Carbon::now();
                    $post->save();
                }
            }

            DB::commit();

            $this->output(' - done.');

            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Cash Pickup Success', $this->email_msg);

        } catch (\Exception $ex) {

            DB::rollback();
            $this->output(' - exception: ' . $ex->getMessage() . ' [' . $ex->getCode() . ']: ' . $ex->getTraceAsString());

            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Cash Pickup Failure', $this->email_msg);
        }

    }

    private function output($msg) {
        $this->info($msg);
        $this->email_msg .= $msg . "<br/>";
    }
}
