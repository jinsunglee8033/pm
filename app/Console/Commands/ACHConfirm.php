<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Model\ACHPosting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ACHConfirm extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ach:confirm';

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
        DB::beginTransaction();

        try {

            $this->output('### processing ACH confirm ###');

            $ach_processed_folder = 'sh_ach/SPP/PROCESSED/';
            $ach_confirmed_folder = 'confirmed/';

            ### check if file exists in PROCESSED folder ###
            $processed_files = Storage::disk('ach')->allFiles($ach_processed_folder);
            $this->output(' - total files found : ' . count($processed_files));

            if (count($processed_files) > 0) {
                foreach ($processed_files as $file) {
                    ### if found read each line and make set record's confirm_date as now ###

                    $file_name = str_replace($ach_processed_folder, '', $file);
                    $this->output(' - processing file : ' . $file_name);

                    $contents = Storage::disk('ach')->get($file);
                    $lines = explode(PHP_EOL, $contents);
                    $this->output(' - total lines : ' . count($lines));
                    if (count($lines) > 0) {
                        foreach ($lines as $line) {
                            //$this->output(' - line: ' . $line);
                            if (trim($line) == '') {
                                continue;
                            }

                            list($id, $amt, $date, $type) = explode(",", $line);
                            if (strlen($id) != 15) {
                                throw new \Exception('Invalid ID length : ' . $id, -1);
                            }

                            $type = trim($type);

                            $ach_id = substr($id, 3, 6);
                            if (!in_array($type, ['C', 'D'])) {
                                throw new \Exception('Invalid Type value found : ' . $type . ' for ID : ' . $ach_id, -2);
                            }

                            $amt = $type == 'C' ? -1 * $amt : $amt;
                            $ach = ACHPosting::where('id', $ach_id)
                                //->where('amt', $amt)
                                ->whereNull('confirm_date')
                                ->first();
                            if (empty($ach)) {
                                throw new \Exception('Unable to find ACH posting record with ID: ' . $ach_id . ' and Amount: ' . $amt, -3);
                            }

                            $ach->confirm_date = Carbon::now();
                            $ach->save();
                        }
                    }

                    ### then move the file to confirmed folder ###
                    Storage::disk('ach')->move($file, $ach_confirmed_folder . $file_name);
                }
            }

            $this->output(' - success');
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollback();

            $msg = ' - error: ' . $ex->getMessage() . ' [' . $ex->getCode() . ']';// . $ex->getTraceAsString();
            $this->error($msg);
            $this->email_msg .= $msg . "<br/>";
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ACH Confirm Error', $this->email_msg);
        }
    }

    private function output($msg) {
        $this->info($msg);
        $this->email_msg .= $msg . "<br/>";
    }
}
