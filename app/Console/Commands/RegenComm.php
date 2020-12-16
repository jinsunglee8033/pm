<?php

namespace App\Console\Commands;

use App\Lib\CommissionProcessor;
use App\Lib\Helper;
use App\Model\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RegenComm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'comm:regen {sdate} {edate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate commission';

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

            $this->info('### Re-generating commission ###');

            $sdate = $this->argument('sdate');
            $edate = $this->argument('edate');

            $this->info(' - from : ' . $sdate);
            $this->info(' - to : '  . $edate);

            $sdate = Carbon::createFromFormat('m/d/Y H:i:s', $sdate . ' 00:00:00');
            $edate = Carbon::createFromFormat('m/d/Y H:i:s', $edate . ' 00:00:00');

            ### get all transactions ###
            $transactions = Transaction::where('status', '!=', 'F')
                ->where('cdate', '>=', $sdate)
                ->where('cdate', '<', $edate->copy()->addDay())
                ->get();

            $this->info(' - total : ' . count($transactions));
            $bar = $this->output->createProgressBar(count($transactions));

            if (count($transactions) > 0) {
                foreach ($transactions as $o) {

                    if ($o->type = 'S') {
                        $ret = CommissionProcessor::create($o->id, true);
                    } else {
                        $ret = CommissionProcessor::void($o->id, true);
                    }

                    if (!empty($ret['error_code'])) {
                        $this->error(' - error: ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']');
                        exit;
                    }

                    if (empty($ret['net_revenue'])) {
                        $this->error(' - error: no net_revenue => ' . var_export($ret, true));
                        exit;
                    }

                    $net_revenue = $ret['net_revenue'];
                    $o->net_revenue = $net_revenue;
                    $o->save();

                    $bar->advance();
                }
            }

            $bar->finish();
            $this->info('');


        } catch (\Exception $ex) {
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            $this->error($msg);
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Regen Comm Failed', $msg);
        }
    }
}
