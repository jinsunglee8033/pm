<?php

namespace App\Console\Commands;

use App\Lib\boom;
use App\Lib\emida2;
use App\Lib\gen;
use App\Lib\liberty;
use App\Lib\RebateProcessor;
use App\Lib\reup;
use App\Lib\RTRProcessor;
use App\Lib\SpiffProcessor;
use App\Lib\telestar;
use App\Lib\h2o;
use App\Lib\emida;
use App\Lib\gss;
use App\Lib\Helper;
use App\Model\Account;
use App\Model\GenFee;
use App\Model\H2OSim;
use App\Model\Product;
use App\Model\Promotion;
use App\Model\ROKESN;
use App\Model\ROKSim;
use App\Model\SpiffSetupSpecial;
use App\Model\StockESN;
use App\Model\StockSim;
use App\Model\StockMapping;
use Illuminate\Console\Command;
use App\Model\Transaction;
use App\Model\RTRQueue;
use App\Model\Denom;
use App\Model\VendorDenom;
use Carbon\Carbon;
use App\Jobs\ProcessRTR;
use App\Events\TransactionStatusUpdated;

class RefreshPortInStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'port-in:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check port-in request status every 10 minutes';

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

            $port_ins = Transaction::join('product', 'transaction.product_id', 'product.id')
                ->where('transaction.status', 'Q')
                ->where('transaction.action', 'Port-In')
                ->where('transaction.cdate', '>=', Carbon::today()->subDay(10))
                ->selectRaw('transaction.*, product.carrier')
                ->get();

            $msg = ' - Total Records To Process: ' . count($port_ins);
            $this->info($msg);
            $email_output .= $msg . '<br/>';

            //$bar = $this->output->createProgressBar(count($port_ins));
            $cnt = 0;

            if (count($port_ins) > 0) {

                foreach ($port_ins as $o) {

                    if ($o->carrier() == 'FreeUP') {
                        //$ret = emida2::freeUp_portin_status($o->phone, $o->id);
                        $ret = emida2::freeUp_portin_status($o->phone, $o->id);
                        if (!empty($ret['error_code'])) {
                            $o->portstatus = $ret['error_code'];
                            $o->portable_reason = $ret['error_msg'];
                            $o->save();

                            Helper::send_mail('it@perfectmobileinc.com', '[FreeUP][' . getenv('APP_ENV') . '] Refresh Port-In Status Failure', var_export($ret, true));
                            continue;
                        }

                        // SUBMITTED='S'
                        // DELAY='D'
                        // CONFIRMED='C'
                        // RESOLUTION='R'
                        // REJECTED='X'
                        // OPEN='O'       : Not exist at the latest docs.
                        // CONFLICT = 'T' : Contact Emida for further support
                        // CANCELLED ='N' : * Port has been cancelled. Carrier will cancel a stagnant port 30 days from last stagnant status.  If this happens, a new port must be submitted.
                        // DENIED = 'N' : Contact Emida for further support
                        // ERROR = 'E' : Port errored out, Contact Emida for support. likely cause, SIM already used
                        // HOLD = 'H' :The OSP is holding the port until x date and time. Once that due date and time is met/reached, the port will complete.

                        $o->portstatus = $ret['error_msg'];
                        switch ($o->portstatus) {
                            case 'S':
                                $o->portable_reason = 'SUBMITTED';
                                break;
                            case 'D':
                                $o->portable_reason = 'DELAY';
                                break;
                            case 'C':
                                $o->portable_reason = 'CONFIRMED';
                                break;
                            case 'R':
                                $o->portable_reason = 'RESOLUTION';
                                break;
                            case 'X':
                                $o->portable_reason = 'REJECTED';
                                break;
                            case 'O':
                                $o->portable_reason = 'OPEN';
                                break;
                            case 'T':
                                $o->portable_reason = 'CONFLICT';
                                break;
                            case 'N':
                                $o->portable_reason = 'CANCELLED';
                                break;
                            case 'H':
                                $o->portable_reason = 'HOLD';
                                break;
                        }
                        $o->save();

                        ### MDN must be Remove from the queue When R,X,N,T,E
                        if ($o->portstatus == 'R' || $o->portstatus == ' X' || $o->portstatus == 'N'
                            || $o->portstatus == 'T' || $o->portstatus == 'E') {

                            $o->status = 'F';
                            $o->note = $o->portable_reason;
                            $o->save();

                            $sim_obj = StockSim::where('product', $o->product_id)->where('sim_serial', $o->sim)->first();
                            if (!empty($sim_obj)) {
                                $sim_obj->status = 'A';
                                $sim_obj->update();
                            }

                            $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                            if (!empty($esn_obj)) {
                                if ($esn_obj->status == 'P') {
                                    $esn_obj->status = 'A';
                                    $esn_obj->update();
                                }

                                $mapping = StockMapping::where('product', $o->product_id)->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->first();
                                if (!empty($mapping)) {
                                    $mapping->status = 'A';
                                    $mapping->update();
                                }
                            }
                        }


                        ### REJECTED 
                        if ($o->portstatus == 'X') {
                            $msg = ' - Port-in Failed. Notifying sub-agent.';
                            $this->info($msg);
                            $email_output .= $msg . '<br/>';

                            $o->status = 'F';
                            $o->note = $o->portable_reason;
                            $o->save();

                            $sim_obj = StockSim::where('product', $o->product_id)->where('sim_serial', $o->sim)->first();
                            if (!empty($sim_obj)) {
                                $sim_obj->status = 'A';
                                $sim_obj->update();
                            }

                            $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                            if (!empty($esn_obj)) {
                                if ($esn_obj->status == 'P') {
                                    $esn_obj->status = 'A';
                                    $esn_obj->update();
                                }

                                $mapping = StockMapping::where('product', $o->product_id)->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->first();
                                if (!empty($mapping)) {
                                    $mapping->status = 'A';
                                    $mapping->update();
                                }
                            }

                            $msg = "Transaction " . $o->id . " has failed! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";
                            event(new TransactionStatusUpdated($o, $msg));

                            $cnt++;
                            continue;
                        }

                        ### CONFIRMED
                        if ($o->portstatus == 'C') {
                            $o->status = 'C';
                            $o->update();

                            ### 1st spiff for port-in ###
                            $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);
                            if (!empty($ret['error_msg'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][FreeUP][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            ### Pay extra spiff and sim charge, sim rebate
                            $sim_obj = StockSim::where('product', $o->product_id)->where('sim_serial', $o->sim)->first();
                            $account = \App\Model\Account::find($o->account_id);
                            \App\Model\Promotion::create_by_order($sim_obj, $account, $o->id);

                            ### Pay extra spiff and esn charge, esn rebate
                            $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                            Promotion::create_by_order_esn($esn_obj, $account, $o->id);

                            ### rebate ###
                            if (!empty($o->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                                if (!empty($esn_obj)) {
                                    $esn_obj->esn_charge = null;
                                    $esn_obj->esn_rebate = null;
                                    $esn_obj->status = 'U';
                                    $esn_obj->update();

                                    $mapping = StockMapping::where('product', $o->product_id)->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->first();
                                    if (!empty($mapping)) {
                                        $mapping->status = 'U';
                                        $mapping->update();
                                    }
                                }

                                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
                                if (!empty($ret['error_msg'])) {
                                    ### send message only ###
                                    $msg = ' - trans ID : ' . $o->id . '<br/>';
                                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][FreeUP][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                }
                            }

                            $ret = RTRProcessor::applyRTR(
                                1,
                                '',
                                $o->id,
                                'Carrier',
                                $o->phone,
                                $o->product_id,
                                $o->vendor_code,
                                '',
                                $o->denom,
                                'system',
                                false,
                                null,
                                1,
                                $o->fee,
                                $o->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][FreeUP][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                            }
                        }


                    } else if ($o->carrier() == 'AT&T') {
                        $product = Product::find($o->product_id);
                        if (empty($product)) {
                            throw new \Exception('product not found');
                        }

                        $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                            ->where('product_id', $product->id)
                            ->where('denom_id', $o->denom_id)
                            ->where('status', 'A')
                            ->first();
                        if (empty($vendor_denom)) {
                            throw new \Exception('Vendor Denomination not found');
                        }

                        $ret = gss::port_status($o->id, $vendor_denom->act_pid, $o->vendor_tx_id, $o->phone);
                        $o->portable_reason = $ret['error_msg'];
                        $o->portstatus = $ret['status'];
                        $o->update();

                        if (!empty($ret['error_code'])) {
                            if ($o->portstatus == 'Conflict' || $o->portstatus == 'Problem') {

                                $msg = ' - Port-in resolution required. Notifying sub-agent.';
                                $this->info($msg);
                                $email_output .= $msg . '<br/>';

                                $o->status = 'R';
                                $o->note = $o->portable_reason;
                                $o->save();

                                $msg = "Transaction " . $o->id . " has some issue! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";

                                event(new TransactionStatusUpdated($o, $msg));

                                $cnt++;

                                continue;
                            
                            } else if ($o->portstatus == 'Failed') {
                                $msg = ' - Port-in Failed. Notifying sub-agent.';
                                $this->info($msg);
                                $email_output .= $msg . '<br/>';

                                $o->status = 'F';
                                $o->note = $o->portable_reason;
                                $o->save();

                                $msg = "Transaction " . $o->id . " has failed! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";
                                event(new TransactionStatusUpdated($o, $msg));

                                $cnt++;

                                continue;
                            } else {
                                continue;
                            }
                        }

                        ### status ('Open', Complete', 'Conflict', 'Failed')
                        $o->status = 'C';
                        $o->update();

                        ### ATT Scheduling Availability
                        if ($o->rtr_month > 1) {
                            \App\Model\ATTBatchMDNAvailability::create_availability($o->account_id, $o->phone, Carbon::today(), $o->rtr_month);
                        }

                        ### 1st spiff for port-in ###
                        $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);
                        if (!empty($ret['error_msg'])) {
                            ### send message only ###
                            $msg = ' - trans ID : ' . $o->id . '<br/>';
                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                            Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                        }

                        ### Pay extra spiff and sim charge, sim rebate
                        $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
                        $account = \App\Model\Account::find($o->account_id);
                        \App\Model\Promotion::create_by_order($sim_obj, $account, $o->id);

                        ### Pay extra spiff and esn charge, esn rebate
                        $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                        Promotion::create_by_order_esn($esn_obj, $account, $o->id);

                        ### rebate ###
                        if (!empty($o->esn)) {
                            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
                            $spiff_amt = $ret_spiff['spiff_amt'];

                            $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();
                            $rebate_type = empty($esn_obj) ? 'B' : 'R';
                            $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
                            if (!empty($ret['error_msg'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                            }
                        }

                        if($o->product_id == 'WATTA'){
                            $rtr_product_id = 'WATTR';
                        }elseif($o->product_id == 'WATTPVA'){
                            $rtr_product_id = 'WATTPVR';
                        }else{
                            $rtr_product_id = $o->product_id;
                        }

                        $ret = RTRProcessor::applyRTR(
                            1,
                            '',
                            $o->id,
                            'Carrier',
                            $o->phone,
                            $rtr_product_id,
                            $o->vendor_code,
                            '',
                            $vendor_denom->denom,
                            'system',
                            false,
                            null,
                            1,
                            $vendor_denom->fee,
                            $o->rtr_month
                        );

                        if (!empty($ret)) {
                            Helper::send_mail('it@perfectmobileinc.com', '[PM][ATT][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                        }


                        if ($o->rtr_month > 1) {
                            $error_msg = RTRProcessor::applyRTR(
                                $o->rtr_month,
                                '',
                                $o->id,
                                'House',
                                $o->phone,
                                $rtr_product_id,
                                $vendor_denom->vendor_code,
                                $vendor_denom->rtr_pid,
                                $vendor_denom->denom,
                                'system',
                                true,
                                null,
                                2,
                                $vendor_denom->fee
                            );

                            if (!empty($error_msg)) {
                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                                $msg .= ' - product : ' . $product->id . '<br/>';
                                $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                                $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                                $msg .= ' - error : ' . $error_msg;
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] ATT Activation - applyRTR remaining month failed', $msg);
                            }
                        }
                    } else if ($o->carrier() == 'GEN Mobile') {

                        if($o->product_id == 'WGENTA' || $o->product_id == 'WGENTOA'){
                            $o->network = 'TMB';
                        }else{
                            $o->network = 'SPR';
                        }

                        $ret = gen::QueryPortin($o);

                        if (empty($ret['error_code'])) {
                            $responsetype = $ret['responsetype'];
                            $reasoncode = $ret['reasoncode'];

                            $o->portable_reason = $ret['error_msg'];
                            $o->portstatus = $responsetype . '/' . $reasoncode;
                            $o->update();

                            if ($responsetype == 'D') {
                                continue;
                            }

                            if ($responsetype == 'R') {
                                switch ($reasoncode) {
                                    case '8A':
                                        $o->portable_reason .= ' account number is missing/incorrect.';
                                        break;
                                    case '8C':
                                        $o->portable_reason .= ' password/PIN is missing/incorrect.';
                                        break;
                                }
                                $o->portable_reason .= 'call Gen Mobile Customer Service at 833-548-1380.';
                                $o->update();

                                $msg = ' - Port-in resolution required. Notifying sub-agent. call Gen Mobile Customer Service at 833-548-1380.';
                                $this->info($msg);
                                $email_output .= $msg . '<br/>';

                                $o->status = 'R';
                                $o->note = $o->portable_reason;
                                $o->save();

                                $msg = "Transaction " . $o->id . " has some issue! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";

                                event(new TransactionStatusUpdated($o, $msg));

                                $cnt++;

                                continue;

                            }

                            ### status ('Open', Complete', 'Conflict', 'Failed')
                            $o->status = 'C';
                            $o->update();


                            ### Pay extra spiff and sim charge, sim rebate
                            $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
                            $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();

                            $special_spiffs = SpiffSetupSpecial::get_special_spiffs(
                              $o->product_id, $o->denom, 'S', $o->account_id, $sim_obj, $esn_obj, []
                            );

                            $pay_activation_fee = true;
                            if (!empty($special_spiffs)) {
                                foreach ($special_spiffs as $s) {
                                    if (in_array($s['special_id'], [295, 296, 297, 298, 299])) {
                                        $pay_activation_fee = false;
                                        break;
                                    }
                                }
                            }

//                            if ($pay_activation_fee) {
//                                $account = Account::find($o->account_id);
//                                ### Pay GEN Activation FEE ###
//                                GenFee::pay_fee($o->account_id, 'A', $o->id, $account);
//                            }

                            ### 1st spiff for port-in ###
                            $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);
                            if (!empty($ret['error_msg'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            ### Pay extra spiff and sim charge, sim rebate
                            $account = \App\Model\Account::find($o->account_id);
                            \App\Model\Promotion::create_by_order($sim_obj, $account, $o->id);

                            ### Pay extra spiff and esn charge, esn rebate
                            Promotion::create_by_order_esn($esn_obj, $account, $o->id);

                            ### rebate ###
                            if (!empty($o->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
                                if (!empty($ret['error_msg'])) {
                                    ### send message only ###
                                    $msg = ' - trans ID : ' . $o->id . '<br/>';
                                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                }
                            }

                            $ret = RTRProcessor::applyRTR(
                              1,
                              '',
                              $o->id,
                              'Carrier',
                              $o->phone,
                              $o->product_id,
                              $o->vendor_code,
                              '',
                              $o->denom,
                              'system',
                              false,
                              null,
                              1,
                              $o->fee,
                              $o->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][GEN][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                            }
                        }
                    } else if ($o->carrier() == 'Liberty Mobile') {

                        // getmdninfo($mdn)
                        $ret = liberty::getmdninfo($o->phone);

                        Helper::send_mail('it@jjonbp.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Port In - Called getmdninfo() ', $ret['error_code'] . '-' . $ret['message'] . '-' . $ret['mdn']);

                        // if status 6 (success) , refill start
                        if (empty($ret['error_code'])) {

                            $o->status = 'C';
                            $o->update();

                            $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);

                            if (!empty($ret['error_msg'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            ### Pay extra spiff and sim charge, sim rebate
                            Promotion::create_by_order($sim_obj, $o->account, $o->id);

                            ### Pay extra spiff and esn charge, esn rebate
                            Promotion::create_by_order_esn($esn_obj, $o->account, $o->id);

                            ### rebate ###
                            if (!empty($o->esn)) {
                                $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, null, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
                                if (!empty($ret['error_msg'])) {
                                    ### send message only ###
                                    $msg = ' - trans ID : ' . $o->id . '<br/>';
                                    $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                    $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][Liberty][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                }
                            }

                            $ret = RTRProcessor::applyRTR(
                                1,
                                isset($sim_type) ? $sim_type : '',
                                $o->id,
                                'Carrier',
                                $o->phone,
                                $o->product_id,
                                $o->vendor_code,
                                '',
                                $o->denom,
                                $o->user_id,
                                true,
                                null,
                                1,
                                $o->fee,
                                $o->rtr_month
                            );

                            if (!empty($ret)) {
                            Helper::send_mail('it@perfectmobileinc.com', '[PM][LBT][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                            }

                            if ($o->rtr_month > 1) {
                                if($o->product_id == 'WGENA'){
                                    $rtr_product_id = 'WGENR';
                                }elseif($o->product_id == 'WGENOA'){
                                    $rtr_product_id = 'WGENOR';
                                }else{
                                    $rtr_product_id = $o->product_id;
                                }

                                $error_msg = RTRProcessor::applyRTR(
                                    $o->rtr_month,
                                    $sim_type,
                                    $o->id,
                                    'House',
                                    $o->phone,
                                    $rtr_product_id,
                                    $o->vendor_code,
                                    '',
                                    $o->denom,
                                    $o->user_id,
                                    true,
                                    null,
                                    2,
                                    $o->fee
                                );

                                if (!empty($error_msg)) {
                                    $msg = ' - trans ID : ' . $o->id . '<br/>';
                                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                                    $msg .= ' - product : ' . $product->id . '<br/>';
                                    $msg .= ' - denom : ' . $o->denom . '<br/>';
                                    $msg .= ' - fee : ' . $o->fee . '<br/>';
                                    $msg .= ' - error : ' . $error_msg;
                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] LBT Activation - applyRTR remaining month failed', $msg);
                                }
                            }
                        }

                    } else if($o->carrier() == 'Boom Mobile') {

                        if($o->product_id == 'WBMBA'){
                            $network = 'BLUE';
                        }elseif ($o->product_id == 'WBMRA'){
                            $network = 'RED';
                        }elseif ($o->product_id == 'WBMPA' || $o->product_id == 'WBMPOA'){
                            // have to update when <network>PURPLE</network>
                            $network =  'PINK';
                        }

                        $ret = boom::checkPortStatus($network, $o->phone);

                        $o->portable_reason = $ret['error_msg'];
                        $o->portstatus = $ret['port_status'];

//                        if($ret['comments'] != ''){
//                            //$o->note = $ret['comments'] . ' - ' . $o->note; //No attachement, because of truncate issue.
//                            $o->note = $ret['comments'] ;
//                        }

                        $o->update();

                        if (!empty($ret['error_code'])) { // If not 11_0, keep going

                            continue;
                        } else {

                            // Get Plan_code
                            $product = Product::find($o->product_id);
                            if (empty($product)) {
                                throw new \Exception('product not found');
                            }

                            $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                                ->where('product_id', $product->id)
                                ->where('denom_id', $o->denom_id)
                                ->where('status', 'A')
                                ->first();
                            if (empty($vendor_denom)) {
                                throw new \Exception('Vendor Denomination not found');
                            }

                            $port_status = $ret['port_status'];

                            if($port_status == 'RESOLUTION_REQUIRED'){
                                /*
                                 * Update transaction status to 'R' for escaping loop.
                                 */
                                $o->status = 'R';

                                if($ret['comments'] != ''){
                                    $o->note = $ret['comments'] ;
                                }

                                $o->save();

                                $msg = "Transaction " . $o->id . " has some issue! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";
                                event(new TransactionStatusUpdated($o, $msg));

                                $cnt++;
                                continue;
                            } elseif ($port_status == 'CONFIRMED' || $port_status == 'COMPLETED') {

                                if($ret['comments'] != ''){
                                    $o->note = $ret['comments'] . ' - ' . $o->note; //No attachement, because of truncate issue.
                                }

                                if($network == 'BLUE' && $port_status == 'CONFIRMED') {

                                    $esn = $o->esn;
                                    $sim = $o->sim;
                                    $cust_nbr = $o->cust_nbr;
                                    $plan_code = $vendor_denom->act_pid;
                                    $first_name = $o->first_name;
                                    $last_name = $o->last_name;
                                    $address1 = $o->address1;
                                    $city = $o->city;
                                    $state = $o->state;
                                    $zip = $o->zip;
                                    $email = $o->email;
                                    $phone = $o->phone;
                                    $account_zip = $o->account_zip;
                                    $acct = $o->account_no;
                                    $pw = $o->account_pin;
                                    $cur_car = $o->current_carrier;

                                    $ret = boom::finalizeActivation($esn, $sim, $plan_code, $cust_nbr, $first_name, $last_name, $address1, $city, $state, $zip, $email, $phone, $account_zip, $acct, $pw, $cur_car);

                                    if($ret['error_code'] == '5_3' || $ret['error_code'] == '5_5'){ // Completed or Pending

                                        if($ret['error_code'] == '5_5') { // If Pending, Call for checking port status
                                            $re_check = boom::checkPortStatus($network, $o->phone);
                                            $o->portstatus = $re_check['port_status'];
                                        }

                                        $o->status = 'C';
                                        $o->save();

                                        ### 1st spiff for port-in ###
                                        $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);
                                        if (!empty($ret['error_msg'])) {
                                            ### send message only ###
                                            $msg = ' - trans ID : ' . $o->id . '<br/>';
                                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                                            Helper::send_mail('it@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                                        }

                                        ### Pay extra spiff and sim charge, sim rebate
                                        $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
                                        $account = Account::find($o->account_id);
                                        Promotion::create_by_order($sim_obj, $account, $o->id);

                                        ### Pay extra spiff and esn charge, esn rebate
                                        $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                                        Promotion::create_by_order_esn($esn_obj, $account, $o->id);

                                        ### rebate ###
                                        if (!empty($o->esn)) {
                                            $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
                                            $spiff_amt = $ret_spiff['spiff_amt'];

                                            $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();
                                            $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                            $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
                                            if (!empty($ret['error_msg'])) {
                                                ### send message only ###
                                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                                Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                            }
                                        }

                                        $ret = RTRProcessor::applyRTR(
                                            1,
                                            isset($sim_type) ? $sim_type : '',
                                            $o->id,
                                            'Carrier',
                                            $o->phone,
                                            $o->product_id,
                                            $o->vendor_code,
                                            $vendor_denom->act_pid,
                                            $o->denom,
                                            'system',
                                            false,
                                            null,
                                            1,
                                            $o->fee,
                                            $o->rtr_month
                                        );

                                        if (!empty($ret)) {
                                            Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                                        }

                                        if ($o->rtr_month > 1) {
                                            if($o->product_id == 'WBMBA'){
                                                $rtr_product_id = 'WBMBAR';
                                            }elseif($o->product_id == 'WBMPA'){
                                                $rtr_product_id = 'WBMPAR';
                                            }elseif($o->product_id == 'WBMRA'){
                                                $rtr_product_id = 'WBMRAR';
                                            }else{
                                                $rtr_product_id = $o->product_id;
                                            }
                                            $error_msg = RTRProcessor::applyRTR(
                                                $o->rtr_month,
                                                $sim_type,
                                                $o->id,
                                                'House',
                                                $o->phone,
                                                $rtr_product_id,
                                                $o->vendor_code,
                                                $vendor_denom->act_pid,
                                                $o->denom,
                                                'system',
                                                true,
                                                null,
                                                2,
                                                $o->fee
                                            );

                                            if (!empty($error_msg)) {
                                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                                $msg .= ' - vendor : ' . $o->vendor_code . '<br/>';
                                                $msg .= ' - product : ' . $o->product_id . '<br/>';
                                                $msg .= ' - denom : ' . $o->denom . '<br/>';
                                                $msg .= ' - fee : ' . $o->fee . '<br/>';
                                                $msg .= ' - error : ' . $error_msg;
                                                Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
                                            }
                                        }
                                    }elseif ($ret['error_code'] == '5_4'){ // If Fail
                                        Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] finalizeActivation() Fail on Boom Blue Port-In', $ret['error_msg']);
                                    }
                                }

                                if($network == 'RED' || $network == 'PINK') {
                                    $boom_mdn = $o->phone;
                                    $cnt = 0;
                                    /*
                                     * 30 * 5 = 100 sec. almost 2 min 30 sec.
                                     */
                                    while($cnt < 30){
                                        $ret2 = boom::getServiceStatus($boom_mdn, $network);
                                        if($ret2['error_code'] == '') {
                                            // Activation is complete!
                                            $cnt = $cnt+30;
                                        }else{
                                            $cnt++;
                                            sleep(5);
                                        }
                                    }

                                    if($ret2['error_code'] != ''){
                                        // Status check few times but didn't get status complete
                                        // But we should deal with complete.
                                        // Just sent email
                                        $msg_boom = '';
                                        $msg_boom .= ' - MDN : ' . $boom_mdn . '<br/>';
                                        $msg_boom .= ' - NETWORK : '. $network . '<br/>';
                                        $msg_boom .= ' - error code : ' . $ret2['error_code'] . '<br/>';
                                        $msg_boom .= ' - error msg : ' . $ret2['error_msg'] . '<br/>';
                                        $msg_boom .= ' - cnt : ' . $cnt;
                                        Helper::send_mail('it@jjonbp.com', '[PM][BOOM][' . getenv('APP_ENV') . '] Failed to obtain Service Status.', $msg_boom);
                                    }

                                    $o->status = 'C';
                                    $o->save();

                                    ### 1st spiff for port-in ###
                                    $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);
                                    if (!empty($ret['error_msg'])) {
                                        ### send message only ###
                                        $msg = ' - trans ID : ' . $o->id . '<br/>';
                                        $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                        $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';
                                        Helper::send_mail('it@jjonbp.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                                    }

                                    ### Pay extra spiff and sim charge, sim rebate
                                    $sim_obj = StockSim::where('sim_serial', $o->sim)->where('product', $o->product_id)->first();
                                    $account = Account::find($o->account_id);
                                    Promotion::create_by_order($sim_obj, $account, $o->id);

                                    ### Pay extra spiff and esn charge, esn rebate
                                    $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                                    Promotion::create_by_order_esn($esn_obj, $account, $o->id);

                                    ### rebate ###
                                    if (!empty($o->esn)) {
                                        $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
                                        $spiff_amt = $ret_spiff['spiff_amt'];

                                        $esn_obj = StockESN::where('esn', $o->esn)->where('product', $o->product_id)->first();
                                        $rebate_type = empty($esn_obj) ? 'B' : 'R';
                                        $ret = RebateProcessor::give_rebate($rebate_type, $o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->denom * $o->rtr_month - $spiff_amt, $o->id, $o->created_by, 1, $o->esn, $o->denom_id);
                                        if (!empty($ret['error_msg'])) {
                                            ### send message only ###
                                            $msg = ' - trans ID : ' . $o->id . '<br/>';
                                            $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                            $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                            Helper::send_mail('it@perfectmobileinc.com', '[PM][Boom][' . getenv('APP_ENV') . '] Failed to give rebate', $msg);
                                        }
                                    }

                                    $ret = RTRProcessor::applyRTR(
                                        1,
                                        isset($sim_type) ? $sim_type : '',
                                        $o->id,
                                        'Carrier',
                                        $o->phone,
                                        $o->product_id,
                                        $o->vendor_code,
                                        $vendor_denom->act_pid,
                                        $o->denom,
                                        'system',
                                        false,
                                        null,
                                        1,
                                        $o->fee,
                                        $o->rtr_month
                                    );

                                    if (!empty($ret)) {
                                        Helper::send_mail('it@perfectmobileinc.com', '[PM][BMR][' . getenv('APP_ENV') . '] applyRTR issue', $ret);
                                    }

                                    if ($o->rtr_month > 1) {
                                        if($o->product_id == 'WBMBA'){
                                            $rtr_product_id = 'WBMBAR';
                                        }elseif($o->product_id == 'WBMPA'){
                                            $rtr_product_id = 'WBMPAR';
                                        }elseif($o->product_id == 'WBMRA'){
                                            $rtr_product_id = 'WBMRAR';
                                        }else{
                                            $rtr_product_id = $o->product_id;
                                        }
                                        $error_msg = RTRProcessor::applyRTR(
                                            $o->rtr_month,
                                            $sim_type,
                                            $o->id,
                                            'House',
                                            $o->phone,
                                            $rtr_product_id,
                                            $o->vendor_code,
                                            $vendor_denom->act_pid,
                                            $o->denom,
                                            'system',
                                            true,
                                            null,
                                            2,
                                            $o->fee
                                        );

                                        if (!empty($error_msg)) {
                                            $msg = ' - trans ID : ' . $o->id . '<br/>';
                                            $msg .= ' - vendor : ' . $o->vendor_code . '<br/>';
                                            $msg .= ' - product : ' . $o->product_id . '<br/>';
                                            $msg .= ' - denom : ' . $o->denom . '<br/>';
                                            $msg .= ' - fee : ' . $o->fee . '<br/>';
                                            $msg .= ' - error : ' . $error_msg;
                                            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Boom Activation - applyRTR remaining month failed', $msg);
                                        }
                                    }
                                }

                            } else {
                                /*
                                 * Unknown, Pending, Delayed, Error, Request
                                 */
                                if($ret['comments'] != ''){
                                    $o->note = $ret['comments'] ;
                                    $o->save();
                                }
                                continue;
                            }
                        }

                    } else if ($o->carrier() == 'Lyca') {

                        $product = Product::find($o->product_id);
                        if (empty($product)) {
                            throw new \Exception('product not found');
                        }

                        $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                            ->where('product_id', $product->id)
                            ->where('denom_id', $o->denom_id)
                            ->where('status', 'A')
                            ->first();

                        if (empty($vendor_denom)) {
                            throw new \Exception('Vendor Denomination not found');
                        }

                        //$ret = emida2::LycaPortInDetails($vendor_denom->act_pid, $o->vendor_tx_id);
                        $ret = emida2::LycaPortInDetails($vendor_denom->act_pid, $o->vendor_tx_id);

                        if(!empty($ret['error_code'])) { // response failed -> still pending

                            $o->portstatus = $ret['error_code'];
                            $o->portable_reason = $ret['error_msg'];
                            $o->save();

                            Helper::send_mail('it@perfectmobileinc.com', '[Lyca][' . getenv('APP_ENV') . '] Refresh Port-In Status Failure', var_export($ret, true));
                            continue;
                        }

                        $lyca_ref_code = $ret['lyca_ref_code'];
                        $lyca_return_desc = $ret['lyca_return_desc'];

                        // IF (R) -> Action required (
                        if($lyca_ref_code != '1') {
                            $msg = ' - Port-in resolution required. Notifying sub-agent.';
                            $this->info($msg);
                            $email_output .= $msg . '<br/>';

                            $o->status = 'R';
                            $o->portstatus = $lyca_ref_code;
                            $o->portable_reason = $lyca_return_desc;
                            $o->note = $o->portable_reason;
                            $o->update();

                            $msg = "Transaction " . $o->id . " has some issue! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";

                            event(new TransactionStatusUpdated($o, $msg));

                            $cnt++;
                            continue;
                        }

                        // IF (F) -> Failed. Quick Q status.
//                        if ($lyca_ref_code != '1') {
//                            $msg = ' - Port-in Failed. Notifying sub-agent.';
//                            $this->info($msg);
//                            $email_output .= $msg . '<br/>';
//
//                            $o->status = 'F';
//                            $o->portstatus = $lyca_ref_code;
//                            $o->portable_reason = $lyca_return_desc;
//                            $o->note = $o->portable_reason;
//                            $o->save();
//
//                            $msg = "Transaction " . $o->id . " has failed! Click <a style='color:yellow;' href='/sub-agent/reports/transaction?id=" . $o->id . "'>here</a> to see detail info!";
//                            event(new TransactionStatusUpdated($o, $msg));
//
//                            $cnt++;
//
//                            continue;
//                        }

                        // IF (C) -> Port In Completed.
                        if($lyca_ref_code == '1') {

                            $o->status = 'C';
                            $o->update();

                            ### 1st spiff for port-in ###
                            $ret = SpiffProcessor::give_spiff($o->account_id, $o->product_id, $o->denom, 1, $o->phone, $o->id, $o->created_by, 1, null, $o->sim, $o->esn, $o->denom_id);
                            if (!empty($ret['error_msg'])) {
                                ### send message only ###
                                $msg = ' - trans ID : ' . $o->id . '<br/>';
                                $msg .= ' - error code : ' . $ret['error_code'] . '<br/>';
                                $msg .= ' - error msg : ' . $ret['error_msg'] . '<br/>';

                                Helper::send_mail('it@perfectmobileinc.com', '[PM][FreeUP][' . getenv('APP_ENV') . '] Failed to give spiff', $msg);
                            }

                            ### Pay extra spiff and sim charge, sim rebate
                            $sim_obj = StockSim::where('product', $o->product_id)->where('sim_serial', $o->sim)->first();
                            $account = \App\Model\Account::find($o->account_id);
                            \App\Model\Promotion::create_by_order($sim_obj, $account, $o->id);

                            ### Pay extra spiff and esn charge, esn rebate
                            $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                            Promotion::create_by_order_esn($esn_obj, $account, $o->id);

                            ### rebate ### (Do not need)
                            if (!empty($o->esn)) {
                                $ret_spiff = SpiffProcessor::get_account_spiff_amt($account, $o->product_id, $o->denom, 1, 1, $o->phone_type, $o->sim, $o->esn);
                                $spiff_amt = $ret_spiff['spiff_amt'];

                                $esn_obj = StockESN::where('product', $o->product_id)->where('esn', $o->esn)->first();
                                if (!empty($esn_obj)) {
                                    $esn_obj->esn_charge = null;
                                    $esn_obj->esn_rebate = null;
                                    $esn_obj->status = 'U';
                                    $esn_obj->update();

                                    $mapping = StockMapping::where('product', $o->product_id)->where('esn', $esn_obj->esn)->where('sim', $sim_obj->sim_serial)->first();
                                    if (!empty($mapping)) {
                                        $mapping->status = 'U';
                                        $mapping->update();
                                    }
                                }

                            }

                            $ret = RTRProcessor::applyRTR(
                                1,
                                '',
                                $o->id,
                                'Carrier',
                                $o->phone,
                                $o->product_id,
                                $o->vendor_code,
                                '',
                                $o->denom,
                                'system',
                                false,
                                null,
                                1,
                                $o->fee,
                                $o->rtr_month
                            );

                            if (!empty($ret)) {
                                Helper::send_mail('it@perfectmobileinc.com', '[PM][Lyca][' . getenv('APP_ENV') . '] applyRTR() issue', $ret);
                            }

                            if ($o->rtr_month > 1) {
                                if($o->product_id == 'WLYCA'){
                                    $rtr_product_id = 'WLYCAN';
                                }else{
                                    $rtr_product_id = $o->product_id;
                                }
                                $error_msg = RTRProcessor::applyRTR(
                                    $o->rtr_month,
                                    '',
                                    $o->id,
                                    'House',
                                    $o->phone,
                                    $rtr_product_id,
                                    $vendor_denom->vendor_code,
                                    $vendor_denom->rtr_pid,
                                    $vendor_denom->denom,
                                    'system',
                                    true,
                                    null,
                                    2,
                                    $vendor_denom->fee
                                );

                                if (!empty($error_msg)) {
                                    $msg = ' - trans ID : ' . $o->id . '<br/>';
                                    $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                                    $msg .= ' - product : ' . $product->id . '<br/>';
                                    $msg .= ' - denom : ' . $vendor_denom->denom . '<br/>';
                                    $msg .= ' - fee : ' . $vendor_denom->fee . '<br/>';
                                    $msg .= ' - error : ' . $error_msg;
                                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Lyca Activation - applyRTR remaining month failed', $msg);
                                }
                            }

                        }
                    }

                }

                if ($cnt > 0) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' .getenv('APP_ENV') . '] Port-In Status Check Results', $email_output);
                }
            }

        } catch (\Exception $ex) {
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            $this->error($msg);
            Helper::send_mail('it@perfectmobileinc.com', '[PM][' . getenv('APP_ENV') . '] Refresh Port-In Status Failure', $msg);
        }

    }
}
