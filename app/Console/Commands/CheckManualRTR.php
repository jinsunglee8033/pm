<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Lib\RTRProcessor;
use App\Model\H2OManualRTR;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckManualRTR extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manual-rtr:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check H2O Manual RTR upload status';

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

            $email_output = '';

            $m_rtrs = H2OManualRTR::where('status', 'N')->get();

            $msg = ' - Total Records To Process: ' . count($m_rtrs);
            $this->info($msg);
            $email_output .= $msg . '<br/>';

            //$bar = $this->output->createProgressBar(count($port_ins));

            if (count($m_rtrs) > 0) {
                foreach ($m_rtrs as $o) {

                    $msg = ' - Processing ' . $o->phone;
                    $this->info($msg);
                    $email_output .= $msg . '<br/>';

                    $vendor_denom = VendorDenom::where('vendor_code', 'LOC')
                        ->where('act_pid', $o->product)
                        ->where('denom', $o->amount)
                        ->first();
                    if (empty($vendor_denom)) {
                        throw new \Exception('Vendor Denomination not found');
                    }

                    $ret = RTRProcessor::applyManualRTR($o->phone, $o->recharge_on, $vendor_denom->vendor_code, $vendor_denom->rtr_pid, $o->amount, $o->created_by);
                    $msg = ' - applyManualRTR : ' . $ret['error_msg'];
                    $this->info($msg);
                    $email_output .= $msg . '<br/>';

                    if (!empty($ret['error_msg'])) {
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] H2O Manual RTR Check Failure: ' . $o->phone, $ret['error_msg']);
                        continue;
                    }

                    $o->mdate = Carbon::now();
                    $o->modified_by = 'system';
                    $o->status = 'S';
                    $o->rtr_id = $ret['id'];
                    $o->save();

                }

                Helper::send_mail('it@perfectmobileinc.com', '[PM][' .getenv('APP_ENV') . '] H2O Manual RTR Check Results', $email_output);
            }

        } catch (\Exception $ex) {
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            $this->error($msg);
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] H2O Manual RTR Check Failure', $msg);
        }
    }
}
