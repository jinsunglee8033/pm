<?php

namespace App\Console\Commands;

use App\Jobs\ProcessRTR;
use App\Model\RTRQueue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ApplyQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:apply-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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

            // $this->info('### Applying pending queues ###');

            // $queues = RTRQueue::where('run_at', '<', Carbon::now())
            //     ->where('result', 'N')
            //     ->where('vendor_pid', 'WH2OU')
            //     ->get();

            // $this->info(' - total pending queues : ' . count($queues));

            // if (count($queues) > 0) {
            //     foreach ($queues as $q) {
            //         $job = (new ProcessRTR($q))
            //             ->onQueue('RTR')
            //             ->delay(Carbon::now()->addMinutes(0));

            //         dispatch($job);

            //         $this->info(' - job : ' . $q->id . ' dispatched');
            //     }
            // }

            // $this->info(' - completed');

            ### Applying failed queues

            $this->info('### Applying failed queues ###');

            $queues = RTRQueue::where('run_at', '<', Carbon::now())
                      ->where('run_at', '>',Carbon::now()->copy()->subDays(5) )
                      ->where('result', 'F')
                      ->get();

            $this->info(' - total failed queues : ' . count($queues));

            if (count($queues) > 0) {
                foreach ($queues as $q) {
                    $job = (new ProcessRTR($q))
                        ->onQueue('RTR')
                        ->delay(Carbon::now()->addMinutes(0));

                    dispatch($job);

                    $this->info(' - job : ' . $q->id . ' dispatched');
                }
            }

            $this->info(' - failed queues add completed');

        } catch (\Exception $ex) {

            $this->error(' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . ']');

        }
    }
}
