<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Mail\ACHBounced;
use App\Model\Account;
use App\Model\ACHPosting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ACHBounce extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ach:bounce';

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

            $this->output('### processing ACH bounce ###');

            $ach_return_folder = 'sh_ach/SPP/RETURN/';
            $ach_bounced_folder = 'bounced/';

            ### watch bounce folder ###
            $return_files = Storage::disk('ach')->allFiles($ach_return_folder);
            $this->output(' - total files found : ' . count($return_files));

            if (count($return_files) > 0) {
                foreach ($return_files as $file) {
                    $file_name = str_replace($ach_return_folder, '', $file);
                    $this->output(' - processing file : ' . $file_name);

                    $contents = Storage::disk('ach')->get($file);
                    $lines = explode(PHP_EOL, $contents);
                    $this->output(' - total lines : ' . count($lines));

                    if (count($lines) > 0) {
                        foreach ($lines as $line) {
                            if (trim($line) == '') {
                                continue;
                            }

                            list($id, $amt, $code) = explode(",", $line);
                            if (strlen($id) != 15) {
                                throw new \Exception('Invalid ID length : ' . $id, -1);
                            }

                            $ach_id = substr($id, 3, 6);
                            $ach = ACHPosting::where('id', $ach_id)
                                ->whereNotNull('post_date')
                                ->whereNotNull('confirm_date')
                                ->whereNull('bounce_date')
                                ->first();
                            if (empty($ach)) {
                                throw new \Exception('Unable to find ACH posting record with ID: ' . $ach_id, -2);
                            }

                            ### check amount ###
                            if (abs($ach->amt) != abs($amt)) {
                                throw new \Exception('Amount mismatch : ' . abs($ach->amt) . ' vs ' . abs($amt), -3);
                            }

                            ### set bounce date ###
                            $ach->bounce_date = Carbon::now();
                            $ach->bounce_code = trim($code);
                            $ach->save();


                            ### set account status = F ###
                            $account = Account::find($ach->account_id);
                            if (empty($account)) {
                                throw new \Exception('Unable to find account with ACH posting record with ID: ' . $ach_id, -3);
                            }

                            $account->status = 'F';
                            $account->save();

                            ### send bounce email ###
                            $ret = Mail::to($account->email)->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($ach));
                            if (!$ret) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', ' - ACH ID : ' . $ach->id . '<br/> - account email : ' . $account->email);
                            }

                            if ($account->type != 'M') {
                                $ret = Mail::to($account->parent->email)->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($ach));
                                if (!$ret) {
                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', ' - ACH ID : ' . $ach->id . '<br/> - parent email : ' . $account->parent->email);
                                }

                                if ($account->parent->id != $account->master->id) {
                                    $ret = Mail::to($account->master->email)->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($ach));
                                    if (!$ret) {
                                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', ' - ACH ID : ' . $ach->id . '<br/> - master email : ' . $account->master->email);
                                    }
                                }
                            }

                            $ret = Mail::to('it@perfectmobileinc.com')->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($ach));
                            if (!$ret) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', $ach->id);
                            }
                        }
                    }

                    ### move file to bounced folder ###
                    $this->output(' - moving file to bounced folder');
                    Storage::disk('ach')->move($file, $ach_bounced_folder . $file_name);
                }
            }

            $this->output(' - success');

            DB::commit();

        } catch (\Exception $ex) {

            DB::rollback();

            $msg = ' - error : ' . $ex->getMessage() . ' [ ' . $ex->getCode() . ']';
            $this->error($msg);
            $this->email_msg .= $msg . "<br/>";
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ACH Bounce Error', $this->email_msg);
        }
    }

    private function output($msg) {
        $this->info($msg);
        $this->email_msg .= $msg . "<br/>";
    }
}
