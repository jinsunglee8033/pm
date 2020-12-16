<?php

namespace App\Console\Commands;

use App\Lib\reup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReupImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reup:import';

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
        DB::beginTransaction();

        try {

            // Start News update for Expired
            DB::statement("
                update news 
                set status = 'E' 
                where edate < curdate() 
                and status ='A'
            ");
            // End

            $this->info('### Starting Re-UP import ###');
            $this->info('1. Carriers...');

            $ret = reup::get_carriers();
            if (!empty($ret['error_code'])) {
                throw new \Exception($ret['error_msg'], $ret['error_code']);
            }

            $carriers = $ret['carriers'];

            $this->info(' - total carriers : ' . count($carriers));

            ### cleanup old data ###
            DB::statement("truncate table reup_carrier");
            DB::statement("truncate table reup_plans");

            foreach ($carriers as $carrier) {
                $this->info(' - carrier : ' . $carrier->carrierName . ' [ ' . $carrier->carrierId . ']');

                ### init carrier ###
                $ret = DB::insert("
                    insert into reup_carrier (
                        id, name, cdate
                    ) values (
                        :id, :name, current_timestamp
                    )
                ", [
                    'id' => $carrier->carrierId,
                    'name' => $carrier->carrierName
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to init carrier : ' . $carrier->carrierName);
                }

                ### get plans ###
                $ret = reup::get_plans($carrier->carrierId);
                if (!empty($ret['error_code'])) {
                    throw new \Exception($ret['error_msg'], $ret['error_code']);
                }

                $plans = $ret['plans'];
                $this->info(' - total plans : ' . count($plans));

                $bar = $this->output->createProgressBar(count($plans));

                foreach ($plans as $plan) {
                    $ret = DB::insert("
                        insert into reup_plans (
                            id, carrier_id, plan_type, reup_plan_id, name,
                            plan_face_value, topup_discount_rates, 
                            topup_enabled, activation_portin_enabled, cdate 
                        ) values ( 
                            :id, :carrier_id, :plan_type, :reup_plan_id, :name,
                            :plan_face_value, :topup_discount_rates,
                            :topup_enabled, :activation_portin_enabled, current_timestamp
                        )
                    ", [
                        'id' => $plan->planId,
                        'carrier_id' => $carrier->carrierId,
                        'plan_type' => $plan->planType,
                        'reup_plan_id' => $plan->reupPlanId,
                        'name' => $plan->planName,
                        'plan_face_value' => $plan->planFaceValue,
                        'topup_discount_rates' => str_replace("%", "", $plan->topupDiscountRate),
                        'topup_enabled' => $plan->topupEnabled == true ? 'Y' : 'N',
                        'activation_portin_enabled' => $plan->activationPortinEnabled == true ? 'Y' : 'N'
                    ]);

                    if ($ret < 1) {
                        throw new \Exception('Failed to init plan : ' . $plan->planName);
                    }

                    $bar->advance();
                }

                $bar->finish();
                $this->info('');
            }

            DB::commit();

            $this->info(' - Finished... ');

        } catch (\Exception $ex) {
            DB::rollBack();
            $this->error(' - Error : ' . $ex->getMessage() . ' [ ' . $ex->getCode() . ']');
        }



    }
}
