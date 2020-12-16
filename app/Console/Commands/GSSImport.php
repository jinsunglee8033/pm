<?php

namespace App\Console\Commands;

use App\Model\emida;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GSSImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gss:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import GSS Products';

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
        \App\Model\GSSProduct::init_product();
    }
}
