<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Lib\PaymentProcessor;
use App\Model\Account;
use App\Model\ACHPosting;
use App\Model\Billing;
use App\Model\Commission;
use App\Model\Payment;
use App\Model\RebateTrans;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateBill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bill:create {bill_date=today}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Billing for all accounts';

    private $bill_date;
    private $period_from;
    private $period_to;

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

            $this->info('### Create Billing ###');

            $bill_date_str = $this->argument('bill_date');
            if ($bill_date_str == "today") {
                $bill_date = Carbon::today();
            } else {
                $bill_date = Carbon::createFromFormat('Y-m-d', $bill_date_str);
            }

            $this->bill_date = $bill_date->startOfWeek();
            $this->period_from = $this->bill_date->copy()->subDays(7);
            $this->period_to = $this->bill_date->copy()->subDays(1);

            $this->info(' - bill date : ' . $this->bill_date->format('m/d/Y'));
            $this->info(' - period from : ' . $this->period_from->format('m/d/Y'));
            $this->info(' - period to : ' . $this->period_to->format('m/d/Y'));

            ### 0. clean-up billing ###
            DB::statement("
                delete from billing
                where bill_date = :bill_date 
            ", [
                'bill_date' => $this->bill_date
            ]);

            ### 1. sub-agent billing ###
            $ret = $this->createSubAgentBill();
            if (!empty($ret['error_msg'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            ### 2. distributor billing ###
            $ret = $this->createDistributorBill();
            if (!empty($ret['error_msg'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            ### 3. master billing ###
            $ret = $this->createMasterBill();
            if (!empty($ret['error_msg'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            ### 4. root billing ###
            $ret = $this->createRootBill();
            if (!empty($ret['error_msg'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }


        } catch (\Exception $ex) {
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            $this->error($msg);
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Create Billing Failed', $msg);
        }
    }

    private function createRootBill() {
        try {

            $this->info('### root billing ###');

            # 1. get all accounts
            $accounts = Account::where('type', 'L')->get();
            $this->info(' - total account: ' . count($accounts));

            $bar = $this->output->createProgressBar(count($accounts));


            if (count($accounts) > 0) {
                foreach ($accounts as $o) {

                    ### last week's billing check ###
                    # - starting balance
                    # - starting deposit
                    $last_bill = Billing::where('account_id', $o->id)
                        ->where('bill_date', $this->bill_date->copy()->subDays(7))
                        ->first();

                    $starting_balance = 0;
                    $starting_deposit = 0;

                    if (!empty($last_bill)) {
                        $starting_balance = $last_bill->ending_balance;
                        $starting_deposit = $last_bill->ending_deposit;
                    }

                    ### last week's bill ACH bounce should be added ###
                    # TODO : when ACH, check last week's bill bounce
                    $ach_bounce_amt = 0;
                    $ach_bounce_fee = 0;

                    ### new deposit ###
                    $new_deposit = Payment::where('account_id', $o->id)
                        ->where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->sum('amt');

                    ### deposit bounced amt ###
                    # TODO : when ACH, check deposit ACH bounce amount
                    $deposit_bounced_amt = 0;

                    ### deposit total ###
                    $deposit_total = $starting_deposit + $new_deposit - $deposit_bounced_amt;

                    ### sales ###
                    $sales = 0;

                    ### sales margin ###
                    $sales_margin = Commission::where('account_id', $o->id)
                        ->where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->where('type', 'S')
                        ->sum('comm_amt');

                    ### void ###
                    $void = 0;

                    ### void margin ###
                    $void_margin = Commission::where('account_id', $o->id)
                        ->where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->where('type', 'V')
                        ->sum('comm_amt');

                    ### gross ###
                    $gross = $sales - $void;

                    ### net margin ###
                    $net_margin = $sales_margin - $void_margin;

                    ### net revenue ###
                    $net_revenue = $gross - $net_margin;

                    ### children paid amt ###
                    $children_paid_amt = 0;

                    ### fee ###
                    $fee = Transaction::where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->where('status', '!=', 'F')
                        ->sum(DB::raw("if(type = 'S', fee, -fee)"));

                    ### pm_fee ###
                    $pm_fee = Transaction::where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->where('status', '!=', 'F')
                        ->sum(DB::raw("if(type = 'S', pm_fee, -pm_fee)"));

                    ### spiff ###
                    # TODO : spiff logic needed
                    $spiff_credit = 0;
                    $spiff_debit = SpiffTrans::where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

                    ### rebate ###
                    $rebate_credit = 0;
                    $rebate_debit = RebateTrans::where('cdate', '>=', $this->period_from)
                        ->where('cdate', '<', $this->period_to->copy()->addDay())
                        ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

                    ### residual ###
                    # TODO : residual logic needed
                    $residual = 0;

                    ### adjustment ###
                    # TODO : adjustment logic & UI needed
                    $adjustment = 0;

                    ### promotion ###
                    # TODO : promotion logic & UI needed
                    $promotion = 0;

                    ### consignment ###
                    $consignment = 0;

                    ### bill amt ###
                    $bill_amt = $net_revenue
                        + $ach_bounce_amt
                        + $ach_bounce_fee
                        + $children_paid_amt
                        - $fee          # for root account, we collect fee from sub-agent and give it to root.
                        - $pm_fee       # for root account, we collect pm_fee from sub-agent and give it to root.
                        - $spiff_credit # + means we give them
                        + $spiff_debit
                        - $rebate_credit
                        + $rebate_debit
                        - $residual     # + means we give them
                        - $adjustment   # + means we give them
                        - $promotion   # + means we give them
                        + $consignment;

                    $ending_balance = $bill_amt;

                    ### deposit paid amt ###
                    $deposit_paid_amt = 0;
                    if ($deposit_total > 0) {
                        $deposit_paid_amt = ($deposit_total >= $ending_balance) ? $ending_balance : $deposit_total;
                    }
                    $ending_balance -= $deposit_paid_amt;

                    ### ach paid amt ###
                    $ach_paid_amt = 0; # TODO : no ACH as of now 09/18/2017
                    if ( ($o->no_ach != 'Y') &&
                         ( ($o->min_ach_amt == 0) || ($ending_balance >0 ) || ( $o->min_ach_amt <  abs($ending_balance) ) )
                        ) {
                        $ach_paid_amt = $ending_balance;
                    }
                    $ending_balance -= $ach_paid_amt;

                    ### dist paid amt ###
                    # - when there is bill bounce last week
                    # TODO : when ACH, dist paid amt = $ending_balance
                    $dist_paid_amt = 0;
                    $ending_balance -= $dist_paid_amt;

                    ### ending deposit ###
                    $ending_deposit = $deposit_total - $deposit_paid_amt;

                    ### now create bill ###
                    $bill = new Billing;
                    $bill->bill_date = $this->bill_date;
                    $bill->period_from = $this->period_from;
                    $bill->period_to = $this->period_to;
                    $bill->account_id = $o->id;
                    $bill->starting_balance = $starting_balance;
                    $bill->starting_deposit = $starting_deposit;
                    $bill->ach_bounce_amt = 0;
                    $bill->ach_bounce_fee = 0;
                    $bill->new_deposit = $new_deposit;
                    $bill->deposit_total = $deposit_total;
                    $bill->sales = $sales;
                    $bill->sales_margin = $sales_margin;
                    $bill->void = $void;
                    $bill->void_margin = $void_margin;
                    $bill->gross = $gross;
                    $bill->net_margin = $net_margin;
                    $bill->net_revenue = $net_revenue;
                    $bill->children_paid_amt = $children_paid_amt;
                    $bill->fee = $fee;
                    $bill->pm_fee = $pm_fee;
                    $bill->spiff_credit = $spiff_credit;
                    $bill->spiff_debit = $spiff_debit;
                    $bill->rebate_credit = $rebate_credit;
                    $bill->rebate_debit = $rebate_debit;
                    $bill->residual = $residual;
                    $bill->adjustment = $adjustment;
                    $bill->promotion = $promotion;
                    $bill->consignment = $consignment;
                    $bill->bill_amt = $bill_amt;
                    $bill->dist_paid_amt = $dist_paid_amt;
                    $bill->deposit_paid_amt = $deposit_paid_amt;
                    $bill->ach_paid_amt = $ach_paid_amt;
                    $bill->ending_balance = $ending_balance;
                    $bill->ending_deposit = $ending_deposit;
                    $bill->cdate = Carbon::now();
                    $bill->save();

                    $bar->advance();
                }
            }

            $bar->finish();
            $this->info('');

        } catch (\Exception $ex) {
            return [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }

    private function createMasterBill() {
        try {

            $this->info('### master billing ###');

            # 1. get all accounts
            $accounts = Account::where('type', 'M')->get();
            $this->info(' - total account: ' . count($accounts));

            $bar = $this->output->createProgressBar(count($accounts));

            if (count($accounts) > 0) {
                foreach ($accounts as $o) {

                    $ret = PaymentProcessor::get_master_balance($o->id, $this->bill_date, $this->period_from, $this->period_to);

                    ### now create bill ###
                    $bill = new Billing;
                    $bill->bill_date = $this->bill_date;
                    $bill->period_from = $this->period_from;
                    $bill->period_to = $this->period_to;
                    $bill->account_id = $o->id;
                    $bill->starting_balance = $ret['starting_balance'];
                    $bill->starting_deposit = $ret['starting_deposit'];
                    $bill->ach_bounce_amt = $ret['ach_bounce_amt'];
                    $bill->ach_bounce_fee = $ret['ach_bounce_fee'];
                    $bill->new_deposit = $ret['new_deposit'];
                    $bill->deposit_total = $ret['deposit_total'];
                    $bill->sales = $ret['sales'];
                    $bill->sales_margin = $ret['sales_margin'];
                    $bill->void = $ret['void'];
                    $bill->void_margin = $ret['void_margin'];
                    $bill->gross = $ret['gross'];
                    $bill->net_margin = $ret['net_margin'];
                    $bill->net_revenue = $ret['net_revenue'];
                    $bill->children_paid_amt = $ret['children_paid_amt'];
                    $bill->fee = 0;
                    $bill->pm_fee = 0;
                    $bill->spiff_credit = $ret['spiff_credit'];
                    $bill->spiff_debit = $ret['spiff_debit'];
                    $bill->rebate_credit = $ret['rebate_credit'];
                    $bill->rebate_debit = $ret['rebate_debit'];
                    $bill->residual = $ret['residual'];
                    $bill->adjustment = $ret['adjustment'];
                    $bill->promotion = $ret['promotion'];
                    $bill->consignment = $ret['consignment'];
                    $bill->bill_amt = $ret['bill_amt'];
                    $bill->dist_paid_amt = $ret['dist_paid_amt'];
                    $bill->deposit_paid_amt = $ret['deposit_paid_amt'];
                    $bill->ach_paid_amt = $ret['ach_paid_amt'];
                    $bill->ending_balance = $ret['ending_balance'];
                    $bill->ending_deposit = $ret['ending_deposit'];
                    $bill->cdate = Carbon::now();

                    if ($o->pay_method == 'C' && $bill->ach_paid_amt != 0) {
                        $ach = new ACHPosting;
                        $ach->type = 'B';
                        $ach->account_id = $o->id;
                        $ach->ach_bank = isset($o->ach_bank) ? $o->ach_bank : '';
                        $ach->ach_holder = isset($o->ach_holder) ? $o->ach_holder : '';
                        $ach->ach_routeno = isset($o->ach_routeno) ? $o->ach_routeno : '';
                        $ach->ach_acctno = isset($o->ach_acctno) ? $o->ach_acctno : '';
                        $ach->amt = $bill->ach_paid_amt;
                        $ach->cdate = Carbon::now();
                        $ach->save();

                        $bill->ach_id = $ach->id;
                    }

                    $bill->save();

                    $bar->advance();
                }
            }

            $bar->finish();

            $this->info('');

        } catch (\Exception $ex) {
            return [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }



    private function createDistributorBill() {
        try {

            $this->info('### distributor billing ###');

            # 1. get all accounts
            $accounts = Account::where('type', 'D')->get();
            $this->info(' - total account: ' . count($accounts));

            $bar = $this->output->createProgressBar(count($accounts));

            if (count($accounts) > 0) {
                foreach ($accounts as $o) {

                    $ret = PaymentProcessor::get_distributor_balance($o->id, $this->bill_date, $this->period_from, $this->period_to);

                    ### now create bill ###
                    $bill = new Billing;
                    $bill->bill_date = $this->bill_date;
                    $bill->period_from = $this->period_from;
                    $bill->period_to = $this->period_to;
                    $bill->account_id = $o->id;
                    $bill->starting_balance = $ret['starting_balance'];
                    $bill->starting_deposit = $ret['starting_deposit'];
                    $bill->ach_bounce_amt = $ret['ach_bounce_amt'];
                    $bill->ach_bounce_fee = $ret['ach_bounce_fee'];
                    $bill->new_deposit = $ret['new_deposit'];
                    $bill->deposit_total = $ret['deposit_total'];
                    $bill->sales = $ret['sales'];
                    $bill->sales_margin = $ret['sales_margin'];
                    $bill->void = $ret['void'];
                    $bill->void_margin = $ret['void_margin'];
                    $bill->gross = $ret['gross'];
                    $bill->net_margin = $ret['net_margin'];
                    $bill->net_revenue = $ret['net_revenue'];
                    $bill->children_paid_amt = $ret['children_paid_amt'];
                    $bill->fee = 0;
                    $bill->pm_fee = 0;
                    $bill->spiff_credit = $ret['spiff_credit'];
                    $bill->spiff_debit = $ret['spiff_debit'];
                    $bill->rebate_credit = $ret['rebate_credit'];
                    $bill->rebate_debit = $ret['rebate_debit'];
                    $bill->residual = $ret['residual'];
                    $bill->adjustment = $ret['adjustment'];
                    $bill->promotion = $ret['promotion'];
                    $bill->consignment = $ret['consignment'];
                    $bill->bill_amt = $ret['bill_amt'];
                    $bill->dist_paid_amt = $ret['dist_paid_amt'];
                    $bill->deposit_paid_amt = $ret['deposit_paid_amt'];
                    $bill->ach_paid_amt = $ret['ach_paid_amt'];
                    $bill->ending_balance = $ret['ending_balance'];
                    $bill->ending_deposit = $ret['ending_deposit'];
                    $bill->cdate = Carbon::now();

                    if ($o->pay_method == 'C' && $bill->ach_paid_amt != 0) {
                        $ach = new ACHPosting;
                        $ach->type = 'B';
                        $ach->account_id = $o->id;
                        $ach->ach_bank = isset($o->ach_bank) ? $o->ach_bank : '';
                        $ach->ach_holder = isset($o->ach_holder) ? $o->ach_holder : '';
                        $ach->ach_routeno = isset($o->ach_routeno) ? $o->ach_routeno : '';
                        $ach->ach_acctno = isset($o->ach_acctno) ? $o->ach_acctno : '';
                        $ach->amt = $bill->ach_paid_amt;
                        $ach->cdate = Carbon::now();
                        $ach->save();

                        $bill->ach_id = $ach->id;
                    }

                    $bill->save();

                    $bar->advance();
                }
            }

            $bar->finish();

            $this->info('');

        } catch (\Exception $ex) {
            return [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }

    private function createSubAgentBill() {
        try {

            $this->info('### sub-agent billing ###');

            # 1. get all sub-agent
            $accounts = Account::where('type', 'S')->get();
            $this->info(' - total account: ' . count($accounts));


            $bar = $this->output->createProgressBar(count($accounts));

            if (count($accounts) > 0) {
                foreach ($accounts as $o) {

                    $ret = PaymentProcessor::get_sub_agent_balance($o->id, $this->bill_date, $this->period_from, $this->period_to);

                    ### now create bill ###
                    $bill = new Billing;
                    $bill->bill_date = $this->bill_date;
                    $bill->period_from = $this->period_from;
                    $bill->period_to = $this->period_to;
                    $bill->account_id = $o->id;
                    $bill->starting_balance = $ret['starting_balance'];
                    $bill->starting_deposit = $ret['starting_deposit'];
                    $bill->ach_bounce_amt = $ret['ach_bounce_amt'];
                    $bill->ach_bounce_fee = $ret['ach_bounce_fee'];
                    $bill->new_deposit = $ret['new_deposit'];
                    $bill->deposit_total = $ret['deposit_total'];
                    $bill->sales = $ret['sales'];
                    $bill->sales_margin = $ret['sales_margin'];
                    $bill->void = $ret['void'];
                    $bill->void_margin = $ret['void_margin'];
                    $bill->gross = $ret['gross'];
                    $bill->net_margin = $ret['net_margin'];
                    $bill->net_revenue = $ret['net_revenue'];
                    $bill->children_paid_amt = $ret['children_paid_amt'];
                    $bill->fee = $ret['fee'];
                    $bill->pm_fee = $ret['pm_fee'];
                    $bill->spiff_credit = $ret['spiff_credit'];
                    $bill->spiff_debit = $ret['spiff_debit'];
                    $bill->rebate_credit = $ret['rebate_credit'];
                    $bill->rebate_debit = $ret['rebate_debit'];
                    $bill->residual = $ret['residual'];
                    $bill->adjustment = $ret['adjustment'];
                    $bill->promotion = $ret['promotion'];
                    $bill->consignment = $ret['consignment'];
                    $bill->bill_amt = $ret['bill_amt'];
                    $bill->dist_paid_amt = $ret['dist_paid_amt'];
                    $bill->deposit_paid_amt = $ret['deposit_paid_amt'];
                    $bill->ach_paid_amt = $ret['ach_paid_amt'];
                    $bill->ending_balance = $ret['ending_balance'];
                    $bill->ending_deposit = $ret['ending_deposit'];
                    $bill->cdate = Carbon::now();

                    if ($o->pay_method == 'C' && $bill->ach_paid_amt != 0) {
                        $ach = new ACHPosting;
                        $ach->type = 'B';
                        $ach->account_id = $o->id;
                        $ach->ach_bank = isset($o->ach_bank) ? $o->ach_bank : '';
                        $ach->ach_holder = isset($o->ach_holder) ? $o->ach_holder : '';
                        $ach->ach_routeno = isset($o->ach_routeno) ? $o->ach_routeno : '';
                        $ach->ach_acctno = isset($o->ach_acctno) ? $o->ach_acctno : '';
                        $ach->amt = $bill->ach_paid_amt;
                        $ach->cdate = Carbon::now();
                        $ach->save();

                        $bill->ach_id = $ach->id;
                    }

                    $bill->save();

                    $bar->advance();
                }
            }

            $bar->finish();
            $this->info('');

        } catch (\Exception $ex) {
            return [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage()
            ];
        }
    }
}
