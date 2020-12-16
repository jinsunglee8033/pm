<?php

namespace App\Console\Commands;

use App\Lib\emida;
use App\Lib\emida2;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmidaImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emida:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Emida Products';

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
        ### 1. Trans Type List ###
        $this->import_trans_types();

        ### 2. Carrier List ###
        $this->import_carriers();

        ### 3. Category List ###
        $this->import_categories();

        ### 4. Product List ###
        $this->import_products();
    }

    private function import_trans_types() {

        DB::beginTransaction();

        try {
            $this->line('### Importing Transaction Types ###');
            $ret = emida2::get_trans_types();
            if (!empty($ret['error_msg'])) {
                $this->error(' - error: ' . $ret['error_msg'] . ' [' . $ret['error_code']);
                exit;
            }

            $trans_types = $ret['trans_types'];
            $this->info(' - total: ' . count($trans_types));
            $bar = $this->output->createProgressBar(count($trans_types));

            ### cleanup first ###
            DB::statement("
                truncate table emida_trans_type
            ");

            foreach ($trans_types as $o) {
                $ret = DB::table('emida_trans_type')->insert([
                    'trans_type_id' => $o->TransTypeId,
                    'description' => $o->Description,
                    'product_count' => $o->ProductCount,
                    'cdate' => Carbon::now()
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add transaction type record');
                }

                $bar->advance();
            }

            $bar->finish();

            DB::commit();
            $this->line(' - done.');

        } catch (\Exception $ex) {

            DB::rollback();
            $this->line('');
            $this->error(' - error: ' . $ex->getMessage() . ' [' . $ex->getCode() . ']');
            exit;
        }


    }

    private function import_carriers() {

        DB::beginTransaction();

        try {
            $this->line('### Importing Carriers ###');
            $ret = emida2::get_carriers();
            if (!empty($ret['error_msg'])) {
                $this->error(' - error: ' . $ret['error_msg'] . ' [' . $ret['error_code']);
                exit;
            }

            $carriers = $ret['carriers'];
            $this->info(' - total: ' . count($carriers));
            $bar = $this->output->createProgressBar(count($carriers));

            ### cleanup first ###
            DB::statement("
                truncate table emida_carrier
            ");

            foreach ($carriers as $o) {
                $ret = DB::table('emida_carrier')->insert([
                    'carrier_id' => $o->CarrierId,
                    'description' => $o->Description,
                    'product_count' => $o->ProductCount,
                    'cdate' => Carbon::now()
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add carrier record');
                }

                $bar->advance();
            }

            $bar->finish();

            DB::commit();
            $this->line(' - done.');

        } catch (\Exception $ex) {

            DB::rollback();
            $this->line('');
            $this->error(' - error: ' . $ex->getMessage() . ' [' . $ex->getCode() . ']');
            exit;
        }


    }

    private function import_categories() {

        DB::beginTransaction();

        try {
            $this->line('### Importing Categories ###');
            $ret = emida2::get_categories();
            if (!empty($ret['error_msg'])) {
                $this->error(' - error: ' . $ret['error_msg'] . ' [' . $ret['error_code']);
                exit;
            }

            $categories = $ret['categories'];
            $this->info(' - total: ' . count($categories));
            $bar = $this->output->createProgressBar(count($categories));

            ### cleanup first ###
            DB::statement("
                truncate table emida_category
            ");

            foreach ($categories as $o) {
                $ret = DB::table('emida_category')->insert([
                    'category_id' => $o->CategoryId,
                    'description' => $o->Description,
                    'product_count' => $o->ProductCount,
                    'cdate' => Carbon::now()
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add category record');
                }

                $bar->advance();
            }

            $bar->finish();

            DB::commit();
            $this->line('');
            $this->line(' - done.');

        } catch (\Exception $ex) {

            DB::rollback();
            $this->error(' - error: ' . $ex->getMessage() . ' [' . $ex->getCode() . ']');
            exit;
        }


    }

    private function import_products() {

        DB::beginTransaction();

        try {
            $this->line('### Importing Products ###');
            $ret = emida2::get_products();
            if (!empty($ret['error_msg'])) {
                $this->error(' - error: ' . $ret['error_msg'] . ' [' . $ret['error_code']);
                exit;
            }

            $products = $ret['products'];
            $this->info(' - total: ' . count($products));
            $bar = $this->output->createProgressBar(count($products));

            ### cleanup first ###
            DB::statement("
                truncate table emida_product
            ");

            foreach ($products as $o) {

                $ret_fee = emida2::get_product_fee($o->ProductId, $o->amount);
                if (!empty($ret_fee['error_msg'])) {
                    throw new \Exception($ret_fee['error_msg'] . '[' . $ret_fee['error_code'] . ']');
                }

                $ret = DB::table('emida_product')->insert([
                    'product_id' => $o->ProductId,
                    'description' => $o->Description,
                    'short_description' => $o->ShortDescription,
                    'amount' => $o->Amount,
                    'carrier_id' => $o->CarrierId,
                    'category_id' => $o->CategoryId,
                    'trans_type_id' => $o->TransTypeId,
                    'discount_rate' => $o->DiscountRate,
                    'currency_code' => $o->CurrencyCode,
                    'currency_symbol' => $o->CurrencySymbol,
                    'fee' => $ret_fee['fee'],
                    'cdate' => Carbon::now()
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add category record');
                }

                $bar->advance();
            }

            $bar->finish();

            DB::commit();
            $this->line('');
            $this->line(' - done.');

        } catch (\Exception $ex) {

            DB::rollback();
            $this->error(' - error: ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString());
            exit;
        }


    }
}
