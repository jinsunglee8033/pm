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
use Symfony\Component\Process\Process;

class ACHPost extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ach:post';

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

            $this->output('### ACH Posting Process ###');
            $this->output(' - date : ' . Carbon::today()->format('Y-m-d'));

            $ach_post_folder = '/sh_ach/SPP/ACH/';
            $ach_posted_folder = '/posted/';
            $ach_backup_folder = '/backup/';

            $debit_file_name = 'S_100_SPP01_US_' . Carbon::today()->format('Ymd') . '_001';
            $credit_file_name = 'S_100_SPP01_US_' . Carbon::today()->format('Ymd') . '_002';

            if (getenv('APP_ENV') != 'production') {
                $this->output(' - cleanup files for demo');

                if (Storage::disk('ach')->exists($ach_backup_folder . $debit_file_name)) {
                    Storage::disk('ach')->delete($ach_backup_folder . $debit_file_name);
                }

                if (Storage::disk('ach')->exists($ach_backup_folder . $debit_file_name . '.asc')) {
                    Storage::disk('ach')->delete($ach_backup_folder . $debit_file_name . '.asc');
                }

                if (Storage::disk('ach')->exists($ach_backup_folder . $credit_file_name)) {
                    Storage::disk('ach')->delete($ach_backup_folder . $credit_file_name);
                }

                if (Storage::disk('ach')->exists($ach_backup_folder . $credit_file_name . '.asc')) {
                    Storage::disk('ach')->delete($ach_backup_folder . $credit_file_name . '.asc');
                }

                if (Storage::disk('ach')->exists($ach_posted_folder . $debit_file_name)) {
                    Storage::disk('ach')->delete($ach_posted_folder . $debit_file_name);
                }

                if (Storage::disk('ach')->exists($ach_posted_folder . $debit_file_name . '.asc')) {
                    Storage::disk('ach')->delete($ach_posted_folder . $debit_file_name . '.asc');
                }

                if (Storage::disk('ach')->exists($ach_posted_folder . $credit_file_name)) {
                    Storage::disk('ach')->delete($ach_posted_folder . $credit_file_name);
                }

                if (Storage::disk('ach')->exists($ach_posted_folder . $credit_file_name . '.asc')) {
                    Storage::disk('ach')->delete($ach_posted_folder . $credit_file_name . '.asc');
                }
            }

            $this->output(' - cleanup files');

            if (Storage::disk('ach')->exists($debit_file_name)) {
                Storage::disk('ach')->move($debit_file_name, $ach_backup_folder . $debit_file_name);
            }

            if (Storage::disk('ach')->exists($debit_file_name . '.asc')) {
                Storage::disk('ach')->move($debit_file_name . '.asc', $ach_backup_folder . $debit_file_name . '.asc');
            }

            if (Storage::disk('ach')->exists($credit_file_name)) {
                Storage::disk('ach')->move($credit_file_name, $ach_backup_folder . $credit_file_name);
            }

            if (Storage::disk('ach')->exists($credit_file_name . '.asc')) {
                Storage::disk('ach')->move($credit_file_name . '.asc', $ach_backup_folder . $credit_file_name . '.asc');
            }

            ### debit posting ###
            $debit_achs = ACHPosting::where('cdate', '>=', Carbon::today())
                ->where('amt', '>', 0)
                ->whereNull('post_date')
                ->whereRaw("ifnull(ach_holder, '') != ''")
                ->whereRaw("ifnull(ach_routeno, '') != ''")
                ->whereRaw("ifnull(ach_acctno, '') != ''")
                ->get();

            $this->output(' - debit records : ' . count($debit_achs));

            $debit_total = 0;
            $credit_total = 0;

            if (count($debit_achs) > 0) {
                foreach ($debit_achs as $o) {

                    $ach_holder = str_pad(trim(preg_replace('/^[ ]{2}$/', " ", $o->ach_holder)), 22, ' ', STR_PAD_RIGHT);
                    $key = 'SP-' . str_pad($o->id, 6, '0', STR_PAD_LEFT) . '_00000';
                    $amt = str_pad(abs($o->amt), 10, ' ', STR_PAD_RIGHT);
                    $ach_routeno = str_pad(trim($o->ach_routeno), 18, ' ', STR_PAD_RIGHT);
                    $ach_acctno = str_pad(trim($o->ach_acctno), 17, ' ', STR_PAD_RIGHT);

                    $line = $ach_holder .
                        $key .
                        $amt .
                        $ach_routeno .
                        $ach_acctno .
                        Carbon::today()->format('m/d/Y') . 'D';

                    Storage::disk('ach')->append($debit_file_name, $line);

                    $debit_total += $o->amt;
                }
            }
            $this->output(' - debit amount : $' . $debit_total);

            ### credit posting ###
            $credit_achs = ACHPosting::where('cdate', '>=', Carbon::today())
                ->where('amt', '<', 0)
                ->whereNull('post_date')
                ->whereRaw("ifnull(ach_holder, '') != ''")
                ->whereRaw("ifnull(ach_routeno, '') != ''")
                ->whereRaw("ifnull(ach_acctno, '') != ''")
                ->get();

            $this->output(' - credit records : ' . count($credit_achs));

            if (count($credit_achs) > 0) {
                foreach ($credit_achs as $o) {

                    $ach_holder = str_pad( trim(preg_replace('/^[ ]{2}$/', ' ', $o->ach_holder)), 22,' ', STR_PAD_RIGHT);
                    $key = 'SP-' . str_pad($o->id, 6, '0', STR_PAD_LEFT) . '_00000';
                    $amt = str_pad(abs($o->amt), 10, ' ', STR_PAD_RIGHT);
                    $ach_routeno = str_pad(trim($o->ach_routeno), 18, ' ', STR_PAD_RIGHT);
                    $ach_acctno = str_pad(trim($o->ach_acctno), 17, ' ', STR_PAD_RIGHT);

                    $line = $ach_holder .
                        $key .
                        $amt .
                        $ach_routeno .
                        $ach_acctno .
                        Carbon::today()->format('m/d/Y') . 'C';

                    Storage::disk('ach')->append($credit_file_name, $line);

                    $credit_total += $o->amt;
                }
            }
            $this->output(' - credit amount : $' . $credit_total);

            ########################################################
            ### file generation is done on /var/scripts/live/ACH ###
            ########################################################

            ### encrypt file ###
            $this->output(' - encrypting files');

            if (count($debit_achs) > 0) {
                $process = new Process("gpg --armor -r it@perfectmobileinc.com --encrypt-file " . $debit_file_name, "/var/scripts/live/ACH/");
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \Exception($process->getErrorOutput(), $process->getExitCode());
                }
            }

            if (count($credit_achs) > 0) {
                $process = new Process("gpg --armor -r it@perfectmobileinc.com --encrypt-file " . $credit_file_name, "/var/scripts/live/ACH/");
                $process->run();
                if (!$process->isSuccessful()) {
                    throw new \Exception($process->getErrorOutput(), $process->getExitCode());
                }
            }

            ### copy encrypted file to /var/scripts/live/ACH/sh_ach/SPP/ACH ###
            $this->output(' - copying encrypted files');
            if (Storage::disk('ach')->exists($debit_file_name . '.asc')) {
                $ret = Storage::disk('ach')->copy($debit_file_name . '.asc', $ach_post_folder . $debit_file_name . '.asc');
                if (!$ret) {
                    throw new \Exception('Failed to copy encrypted debit file to SH folder', -1);
                }
            }

            if (Storage::disk('ach')->exists($credit_file_name . '.asc')) {
                $ret = Storage::disk('ach')->copy($credit_file_name . '.asc', $ach_post_folder . $credit_file_name . '.asc');
                if (!$ret) {
                    throw new \Exception('Failed to copy encrypted credit file to SH folder', -2);
                }
            }

            ### move generated file to /var/scripts/live/ACH/posted ###
            $this->output(' - backing up encrypted files');

            if (Storage::disk('ach')->exists($debit_file_name)) {
                $ret = Storage::disk('ach')->move($debit_file_name, $ach_posted_folder . $debit_file_name);
                if (!$ret) {
                    throw new \Exception('Failed to move debit file to posted folder', -3);
                }
            }

            if (Storage::disk('ach')->exists($debit_file_name . '.asc')) {
                $ret = Storage::disk('ach')->move($debit_file_name . '.asc', $ach_posted_folder . $debit_file_name . '.asc');
                if (!$ret) {
                    throw new \Exception('Failed to move encrypted debit file to posted folder', -4);
                }
            }

            if (Storage::disk('ach')->exists($credit_file_name)) {
                $ret = Storage::disk('ach')->move($credit_file_name, $ach_posted_folder . $credit_file_name);
                if (!$ret) {
                    throw new \Exception('Failed to move credit file to posted folder', -5);
                }
            }

            if (Storage::disk('ach')->exists($credit_file_name . '.asc')) {
                $ret = Storage::disk('ach')->move($credit_file_name . '.asc', $ach_posted_folder . $credit_file_name . '.asc');
                if (!$ret) {
                    throw new \Exception('Failed to move encrypted credit file to posted folder', -5);
                }
            }


            ### update ACH records with empty ACH info as bounced ###
            $empty_achs = ACHPosting::where('cdate', '>=', Carbon::today())
                    ->where('amt', '!=', 0)
                    ->whereNull('post_date')
                    ->where(function($query) {
                        return $query->whereRaw("ifnull(ach_holder, '') = ''")
                            ->orWhereRaw("ifnull(ach_routeno, '') = ''")
                            ->orWhereRaw("ifnull(ach_acctno, '') = ''");
                    })
                    ->get();

            $this->output(' - empty ACH info records: ' . count($empty_achs));

            if (count($empty_achs) > 0) {
                foreach ($empty_achs as $o) {
                    $o->post_date = Carbon::now();
                    $o->confirm_date = Carbon::now();
                    $o->bounce_date = Carbon::now();
                    $o->bounce_code = 'WRR';
                    $o->save();

                    ### set account status = F ###
                    $account = Account::find($o->account_id);
                    if (empty($account)) {
                        throw new \Exception('Unable to find account with ACH posting record with ID: ' . $o->ach_id, -3);
                    }

                    $account->status = 'F';
                    $account->save();

                    ### send bounce email ###
                    $ret = Mail::to($account->email)->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($o));
                    if (!$ret) {
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', ' - ACH ID : ' . $o->id . '<br/> - account email : ' . $account->email);
                    }

                    if ($account->type != 'M') {
                        $ret = Mail::to($account->parent->email)->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($o));
                        if (!$ret) {
                            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', ' - ACH ID : ' . $o->id . '<br/> - parent email : ' . $account->parent->email);
                        }

                        if ($account->parent->id != $account->master->id) {
                            $ret = Mail::to($account->master->email)->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($o));
                            if (!$ret) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', ' - ACH ID : ' . $o->id . '<br/> - master email : ' . $account->master->email);
                            }
                        }
                    }

                    $ret = Mail::to('it@perfectmobileinc.com')->bcc('tom@perfectmobileinc.com')->send(new ACHBounced($o));
                    if (!$ret) {
                        Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Sending ACH bounce email failed', $o->id);
                    }
                }
            }

            ### update normal ACH records as posted ###
            if (count($debit_achs) > 0) {
                foreach ($debit_achs as $o) {
                    $o->post_date = Carbon::now();
                    $o->save();
                }
            }

            if (count($credit_achs) > 0) {
                foreach ($credit_achs as $o) {
                    $o->post_date = Carbon::now();
                    $o->save();
                }
            }

            DB::commit();
            $this->output(' - success');

            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ACH Post Success', $this->email_msg);

        } catch (\Exception $ex) {

            DB::rollback();

            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . ']';
            $this->error($msg);
            $this->email_msg .= $msg . '<br/>';
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ACH Post Failed', $this->email_msg);
        }
    }

    private function output($msg) {
        $this->info($msg);
        $this->email_msg .= $msg . "<br/>";
    }
}
