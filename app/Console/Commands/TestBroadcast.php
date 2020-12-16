<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\Transaction;
use App\User;
use App\Events\TransactionStatusUpdated;
use Log;

class TestBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'broadcast:test {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test transaction event broadcasting';

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
            $transaction = Transaction::first();

            event(new TransactionStatusUpdated($transaction, $this->argument('message')));

        } catch (\Exception $ex) {
            $this->error(' - error code : ' . $ex->getCode());
            $this->error(' - error msg : ' . $ex->getMessage());
        }

    }
}
