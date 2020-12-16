<?php

namespace App\Console\Commands;

use App\Lib\Helper;
use App\Lib\RTRProcessor;
use App\Lib\CommissionProcessor;
use App\Lib\PaymentProcessor;
use App\Lib\gss;

use App\Model\Account;
use App\Model\ATTBatch;
use App\Model\ATTBatchFee;
use App\Model\ATTBatchFeeBase;
use App\Model\ATTSimSwap;
use App\Model\ChangePlan;
use App\Model\Denom;
use App\Model\Product;
use App\Model\VendorDenom;
use App\Model\Transaction;

use Carbon\Carbon;
use Illuminate\Console\Command;

class ATTBatchSchedule extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'att:batch-schedule';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'ATT Batch includes RTR, SIM SWAP, PLAN CHANGE';

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
      $today = Carbon::today();

      $batchs = ATTBatch::where('status', 'N')->where('process_date','<=', $today)->orderBy('id', 'asc')->get();
      if (empty($batchs)) return;

      $feeobjs = ATTBatchFeeBase::get();
      $base_fee = null;
      $tier_fees = array();

      foreach ($feeobjs as $f) {
          if ($f->type == 'B') {
              $base_fee = $f;
          } else {
              $tier_fees[$f->id] = $f;
          }
      }

      foreach ($batchs as $batch) {
          try {
              $account = Account::find($batch->account_id);
              $batch_fee_obj = ATTBatchFee::get_batch_fee($batch->account_id);

              $batch_fee = new \stdClass();
              $batch_fee->for_rtr = ATTBatchFee::get_for_rtr_fee($batch_fee_obj, $base_fee, $tier_fees);
              $batch_fee->for_sim_swap = ATTBatchFee::get_for_sim_swap_fee($batch_fee_obj, $base_fee, $tier_fees);
              $batch_fee->for_plan_change = ATTBatchFee::get_for_plan_change_fee($batch_fee_obj, $base_fee, $tier_fees);

              $tid = Helper::get_att_tid($account);
              if (empty($tid)) {
                  $batch->comment = 'No TID assigned';
                  $batch->status  = 'F';
                  $batch->update();

                  Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. No TID assigned [' . $batch->mdn . ']', $batch->comment);
              }

              $product_id = 'WATTA';

              $product = Product::find($product_id);
              if (empty($product) || $product->status != 'A') {
                  $batch->comment = 'The product is not available.';
                  $batch->status  = 'F';
                  $batch->update();

                  Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. The product is not available. [' . $batch->mdn . ']', $batch->comment);
              }

              $denom = Denom::where('product_id', $product_id)->where('denom', $batch->plan)->first();
              if (empty($denom)) {
                  $batch->comment = 'Invalid denomination provided.';
                  $batch->status  = 'F';
                  $batch->update();

                  Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. Invalid denomination provided. [' . $batch->mdn . ']', $batch->comment);
              }

              $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                ->where('product_id', $denom->product_id)
                ->where('denom_id', $denom->id)
                ->where('status', 'A')
                ->first();
              if (empty($vendor_denom)) {
                  $batch->comment = 'Vendor configuration incomplete.';
                  $batch->status  = 'F';
                  $batch->update();

                  Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. Vendor configuration incomplete. [' . $batch->mdn . ']', $batch->comment);
              }

              if ($batch->for_sim_swap == 'Y' && $batch->for_sim_swap_status == 'N') {

                  $balance = Helper::get_limit($account->id);
                  if ($balance < $batch_fee->for_sim_swap) {
                      $batch->comment = 'No enough balance.';
                      $batch->for_sim_swap_status = 'F';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                  }

                  $simswap = new ATTSimSwap();
                  $simswap->account_id  = $account->id;
                  $simswap->new_sim     = $batch->sim;
                  $simswap->mdn         = $batch->mdn;
                  $simswap->status      = 'N';
                  $simswap->att_tid     = $tid;
                  $simswap->created_by  = 'system';
                  $simswap->cdate       = Carbon::now();
                  $simswap->save();

                  ### SWAP SIM ###
                  // SwapEquipment($trans_id, $pid, $phone, $sim, $imei, $tid)
                  $ret = gss::SwapEquipment($simswap->id, $vendor_denom->act_pid, $batch->mdn, $batch->sim, '', $tid);

                  if (empty($ret)) {

                      $simswap->comment       = (empty($ret['error_code']) ? '' : '[' . $ret['error_code'] . '] ') . $ret['error_msg'];
                      $simswap->status        = empty($ret['error_code']) ? 'S' : 'F';
                      $simswap->save();

                      $batch->comment = 'Unknown Error.';
                      $batch->for_sim_swap_status = 'F';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. Unknown Error. [' . $batch->mdn . ']', $batch->comment);
                  }

                  $simswap->comment       = (empty($ret['error_code']) ? '' : '[' . $ret['error_code'] . '] ') . $ret['error_msg'];
                  $simswap->status        = empty($ret['error_code']) ? 'S' : 'F';
                  $simswap->update();

                  if (!empty($ret['error_code'])) {

                      $batch->comment = $simswap->comment;
                      $batch->for_sim_swap_status = 'F';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                  }

                  if ($simswap->status == 'S') {
                      $batch->comment = 'Sim swap success.';
                      $batch->for_sim_swap_status = 'S';
                      $batch->update();

                      Transaction::create_batch($account->id, 'Batch', $batch->mdn, $batch->sim, 1, $batch_fee->for_sim_swap);
                  } else {
                      $batch->comment = $simswap->comment;
                      $batch->for_sim_swap_status = 'F';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                  }
              }

              if ($batch->for_plan_change == 'Y' && $batch->for_plan_change_status == 'N') {
                  sleep(2);

                  $balance = Helper::get_limit($account->id);
                  if ($balance < $batch_fee->for_plan_change) {
                      $batch->comment = 'No enough balance.';
                      $batch->for_sim_swap_status = 'F';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                  }

                  $changeplan = new ChangePlan();
                  $changeplan->account_id = $account->id;
                  $changeplan->carrier    = $product->carrier;
                  $changeplan->mdn        = $batch->mdn;
                  $changeplan->plan       = $denom->denom;
                  $changeplan->status     = 'N';
                  $changeplan->created_by = 'system';
                  $changeplan->cdate      = Carbon::now();
                  $changeplan->save();

                  ### SWAP SIM ###
                  // UpgradePlan($trans_id, $pid, $phone, $tid)
                  $ret = gss::UpgradePlan($changeplan->id, $vendor_denom->act_pid, $batch->mdn, $tid);

                  if (empty($ret)) {

                      $batch->comment = 'Unknown Error.';
                      $batch->for_plan_change_status  = 'F';
                      $batch->status = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                  }

                  $changeplan->comment       = (empty($ret['error_code']) ? '' : '[' . $ret['error_code'] . '] ') . $ret['error_msg'];
                  $changeplan->status        = empty($ret['error_code']) ? 'S' : 'F';
                  $changeplan->save();

                  if (!empty($ret['error_code'])) {
                      $batch->comment = $changeplan->comment;
                      $batch->for_plan_change_status  = 'F';
                      $batch->status = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                  }

                  if ($changeplan->status == 'S') {
                      $batch->comment = 'Change plan success';
                      $batch->for_plan_change_status  = 'S';
                      $batch->update();

                      Transaction::create_batch($account->id, 'Batch', $batch->mdn, '', 2, $batch_fee->for_plan_change);
                  } else {

                      $batch->comment = 'Change plan failed !!';
                      $batch->for_plan_change_status  = 'F';
                      $batch->status = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment );
                  }
              }

              if ($batch->for_rtr == 'Y' && $batch->for_rtr_status == 'N') {

                  sleep(2);

                  $product_id = 'WATTR';

                  $product = Product::find($product_id);
                  if (empty($product) || $product->status != 'A') {
                      $batch->comment = 'The product is not available.';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. The product is not available. [' . $batch->mdn . ']', $batch->comment);
                      return;
                  }

                  $denom = Denom::where('product_id', $product_id)->where('denom', $batch->plan)->first();
                  if (empty($denom)) {
                      $batch->comment = 'Invalid denomination provided.';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. Invalid denomination provided. [' . $batch->mdn . ']', $batch->comment);
                      return;
                  }

                  $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
                    ->where('product_id', $denom->product_id)
                    ->where('denom_id', $denom->id)
                    ->where('status', 'A')
                    ->first();
                  if (empty($vendor_denom)) {
                      $batch->comment = 'Vendor configuration incomplete.';
                      $batch->status  = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. Vendor configuration incomplete. [' . $batch->mdn . ']', $batch->comment);
                      return;
                  }
                  $fee = $vendor_denom->fee;
                  $pm_fee = $vendor_denom->pm_fee;

                  $collection_amt = $batch->plan;
                  $net_revenue = 0;
                  if ($collection_amt > 0) {
                      $ret = PaymentProcessor::check_limit($account->id, $denom->id, $collection_amt, $fee + $pm_fee, true);

                      if (!empty($ret['error_msg'])) {
                          $batch->comment = $ret['error_msg'];
                          $batch->for_rtr_status  = 'F';
                          $batch->status = 'F';
                          $batch->update();

                          Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                            '. [' . $batch->mdn . ']', $batch->comment);
                          return;
                      }

                      $net_revenue = $ret['net_revenue'];
                  }

                  ### now create order ###
                  $trans = new Transaction;
                  $trans->type        = 'S';
                  $trans->account_id  = $account->id;
                  $trans->product_id  = $product->id;
                  $trans->action      = 'RTR';
                  $trans->denom       = $batch->plan;
                  $trans->phone       = $batch->mdn;
                  $trans->status      = 'I';
                  $trans->cdate       = Carbon::now();
                  $trans->created_by  = 'system';
                  $trans->api         = 'Y';
                  $trans->collection_amt = $collection_amt;
                  $trans->rtr_month   = 1;
                  $trans->net_revenue = $net_revenue;
                  $trans->fee         = $fee;
                  $trans->pm_fee      = $pm_fee;
                  $trans->save();

                  ### process vendor API - first month ###
                  $vendor_tx_id = '';
                  $ret = gss::rtr($trans->id, $vendor_denom->rtr_pid, $trans->phone, $trans->denom);
                  $vendor_tx_id = isset($ret['tx_id']) ? $ret['tx_id'] : '';

                  if (!empty($ret['error_msg'])) {
                      $trans->status = 'F';
                      $trans->note = $ret['error_msg'] . ' [' . $ret['error_code'] . ']';
                      $trans->save();

                      $batch->comment = $trans->note;
                      $batch->for_rtr_status  = 'F';
                      $batch->status = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                      return;
                  }

                  if (empty($vendor_tx_id)) {
                      $trans->status = 'F';
                      $trans->note = 'Unable to retrieve vendor Tx.ID';
                      $trans->save();

                      $batch->comment = $trans->note;
                      $batch->for_rtr_status  = 'F';
                      $batch->status = 'F';
                      $batch->update();

                      Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                        '. [' . $batch->mdn . ']', $batch->comment);
                      return;
                  }

                  ### add rtr-q for first month just for show up ###
                  $error_msg = RTRProcessor::applyRTR(
                    1,
                    'Refill',
                    $trans->id,
                    'Refill',
                    $trans->phone,
                    $trans->product_id,
                    $vendor_denom->vendor_code,
                    $vendor_denom->rtr_pid,
                    $denom->denom,
                    'system',
                    false,
                    null,
                    1,
                    $vendor_denom->fee,
                    $trans->rtr_month
                  );

                  if (!empty($error_msg)) {
                      $msg = ' - trans ID : ' . $trans->id . '<br/>';
                      $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                      $msg .= ' - product : ' . $product->id . '<br/>';
                      $msg .= ' - denom : ' . $denom->denom . '<br/>';
                      $msg .= ' - error : ' . $error_msg;
                      Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] Domestic RTR - applyRTR 1st month failed', $msg);
                  }

                  ### commission ###
                  if ($collection_amt > 0) {
                      $ret = CommissionProcessor::create($trans->id);
                      if (!empty($ret['error_msg'])) {
                          $msg = ' - trans ID : ' . $trans->id . '<br/>';
                          $msg .= ' - vendor : ' . $product->vendor_code . '<br/>';
                          $msg .= ' - product : ' . $product->id . '<br/>';
                          $msg .= ' - denom : ' . $denom->denom . '<br/>';
                          $msg .= ' - error : ' . $ret['error_msg'] . ' [' . $ret['error_code'] . ']';

                          $batch->comment = $msg;
                          $batch->for_rtr_status  = 'F';
                          $batch->status = 'F';
                          $batch->update();

                          Helper::send_mail('it@jjonbp.com', '[PM][' .getenv('APP_ENV') . '] ATT Batch Process. ' .
                            '. [' . $batch->mdn . ']', $batch->comment);
                          return;
                      }
                  }

                  ### mark as success ###
                  $trans->status = 'C';
                  $trans->vendor_tx_id = $vendor_tx_id;
                  $trans->mdate = Carbon::now();
                  $trans->modified_by = 'system';
                  $trans->save();

                  $batch->for_rtr_status  = 'S';
                  $batch->status = 'S';
                  $batch->update();

                  Transaction::create_batch($account->id, 'Batch', $batch->mdn, '', 3, $batch_fee->for_rtr);
              }

          } catch (\Exception $ex) {
              $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
              Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] ATT Batch Process Failure', $msg);
          }

          sleep(10);
      }
    } catch (\Exception $ex) {
      $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
      Helper::send_mail('it@jjonbp.com', '[PM][' . getenv('APP_ENV') . '] ATT Batch Process Failure', $msg);
    }
  }
}
