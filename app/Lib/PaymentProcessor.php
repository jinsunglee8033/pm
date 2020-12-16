<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 9/14/17
 * Time: 4:34 PM
 */

namespace App\Lib;


use App\Model\Account;
use App\Model\ACHPosting;
use App\Model\Billing;
use App\Model\Commission;
use App\Model\ConsignmentCharge;
use App\Model\Credit;
use App\Model\Payment;
use App\Model\Promotion;
use App\Model\RateDetail;
use App\Model\RebateTrans;
use App\Model\Residual;
use App\Model\SpiffTrans;
use App\Model\Transaction;
use App\Model\Product;
use App\Model\Denom;
use App\Model\VendorDenom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentProcessor
{
    private static $ach_started = true;
    public static $bounce_fee = 35;

    public static function check_limit($account_id, $denom_id, $collection_amt, $fee, $is_rtr = true, $action = 'RTR') {

        ### 1. find account ###
        $account = Account::find($account_id);
        if (empty($account)) {
            return [
                'error_msg' => 'Unable to find account: ' . $account_id
            ];
        }

        ### 2. find rate detail ###
        if ($is_rtr) {
            $rate_detail = RateDetail::where('rate_plan_id', $account->rate_plan_id)
                ->where('denom_id', $denom_id)
                ->where('action', $action)
                ->first();
            if (empty($rate_detail)) {
                return [
                    'error_msg' => 'You don\'t have rate detail assigned for this product. Please ask customer support.'
                ];
            }
        }

        ### 3. get net revenue ###
        $margin = 0;
        if ($is_rtr) {
            $margin = $collection_amt * $rate_detail->rates / 100;
        }

        $net_revenue = $collection_amt - $margin;

        ### 4. get account balance ###
        $balance = self::get_limit($account_id);
        $sub_agent_blance = $balance;

        ### 5. get distributor balance ###
        $dist = Account::where('id', $account->id)
            ->where('type', 'D')
            ->first();

        $dist_balance = 0;
        $dist_lower_balance = 'N';
        if (!empty($dist)) {
            $dist_balance = self::get_dist_limit($dist->id);
            if ($dist_balance < $balance) {
                $balance = $dist_balance;
                $dist_lower_balance = 'Y';
            }
        }

        ### 6. get master balance ###
        $master_lower_balance = 'N';
        $master_balance = self::get_master_limit($account->master_id);
        if ($master_balance < $balance) {
            $balance = $master_balance;
            $master_lower_balance = 'Y';
        }

        Helper::log('### check_balance() result ###', [
            'net_revenue' => $net_revenue,
            'balance' => $balance,
            'sub_agent_balance' => $sub_agent_blance,
            'dist_balance' => $dist_balance,
            'master_balance' => $master_balance
        ]);

        if ($net_revenue + $fee > $balance) {
            if( $dist_lower_balance == 'Y') {
                return [
                    'error_msg' => 'Insufficient Sales Limit of your distributor. Please contact your distributor.'
                ];
            }else if ( $master_lower_balance == 'Y') {
                return [
                    'error_msg' => 'Insufficient Sales Limit of your master. Please contact your master.'
                ];
            }else {
                return [
                    'error_msg' => 'Insufficient balance. Please make a payment first.'
                ];
            }

        }

        return [
            'error_msg' => '',
            'net_revenue' => $net_revenue
        ];
    }

    public static function get_rtr_discount_amount($account, $product_id, $denom_amt, $rtr_month) {
        if ($rtr_month < 1) {
            return [
                'fee' => 0,
                'pm_fee' => 0,
                'discount' => 0
            ];
        }

        ### check product ###
        $product = Product::find($product_id);
        if (empty($product) || $product->status != 'A') {
            return [
                'fee' => 0,
                'pm_fee' => 0,
                'discount' => 0
            ];
        }

        ### check denom ###
        $demon_obj = Denom::where('product_id', $product_id)->where('denom', $denom_amt)->first();
        if (empty($demon_obj) || $demon_obj->status != 'A') {
            return [
                'fee' => 0,
                'pm_fee' => 0,
                'discount' => 0
            ];
        }

        ### check vendor denom
        $vendor_denom = VendorDenom::where('vendor_code', $product->vendor_code)
            ->where('product_id', $product->id)
            ->where('denom_id', $demon_obj->id)
            ->where('status', 'A')
            ->first();
        if (empty($vendor_denom)) {
            return [
                'fee' => 0,
                'pm_fee' => 0,
                'discount' => 0
            ];
        }

        $fee = $vendor_denom->fee * $rtr_month;
        $pm_fee = $vendor_denom->pm_fee * $rtr_month;

        ### find rate detail ###
        $rate_detail = RateDetail::where('rate_plan_id', $account->rate_plan_id)
            ->where('denom_id', $demon_obj->id)
            ->where('action', 'RTR')
            ->first();
        if (empty($rate_detail)) {
            return [
                'fee' => $fee,
                'pm_fee' => $pm_fee,
                'discount' => 0
            ];
        }

        ### discount amount ###
        $discount = $denom_amt * $rtr_month * $rate_detail->rates / 100;
        return [
            'fee' => $fee,
            'pm_fee' => $pm_fee,
            'discount' => $discount
        ];
    }

    public static function get_root_balance($account_id, $bill_date, $period_from, $period_to) {
        ### last week's billing check ###
        # - starting balance
        # - starting deposit
        $last_bill = Billing::where('account_id', $account_id)
            ->where('bill_date', $bill_date->copy()->subDays(7))
            ->first();

        $starting_balance = 0;
        $starting_deposit = 0;

        if (!empty($last_bill)) {
            $starting_balance = $last_bill->ending_balance;
            $starting_deposit = $last_bill->ending_deposit;
        }

        ### last week's bill ACH bounce should be added ###
        # TODO : when ACH, check last week's bill bounce
        $bill_bounce_amt = 0;
        $starting_balance += $bill_bounce_amt;

        ### new deposit ###
        $new_deposit = Payment::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $new_deposit = round($new_deposit, 2);

        ### deposit bounced amt ###
        # TODO : when ACH, check deposit ACH bounce amount
        $deposit_bounced_amt = 0;

        ### deposit total ###
        $deposit_total = $starting_deposit + $new_deposit - $deposit_bounced_amt;

        ### sales ###
        $sales = 0;

        ### sales margin ###
        $sales_margin = Commission::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('type', 'S')
            ->sum('comm_amt');

        $sales_margin = round($sales_margin, 2);

        ### void ###
        $void = 0;

        ### void margin ###
        $void_margin = Commission::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('type', 'V')
            ->sum('comm_amt');

        $void_margin = round($void_margin, 2);

        ### gross ###
        $gross = $sales - $void;

        ### net margin ###
        $net_margin = $sales_margin - $void_margin;

        ### net revenue ###
        $net_revenue = $gross - $net_margin;

        ### fee ###
        $fee = 0;

        ### pm_fee ###
        $pm_fee = 0;

        ### children paid amt ###
        $children_paid_amt = 0;

        ### spiff ###
        $spiff_credit = 0;
        $spiff_debit = SpiffTrans::where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        $spiff_debit = round($spiff_debit, 2);

        ### rebate ###
        $rebate_credit = 0;
        $rebate_debit = RebateTrans::where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

        $rebate_debit = round($rebate_debit, 2);

        ### residual ###
        $residual = 0;

        ### adjustment ###
        $adjustment = 0;

        ### promotion ###
        $promotion = 0;

        ### consignment ###
        $consignment = 0;

        ### bill amt ###
        $bill_amt = $net_revenue
            + $fee
            + $pm_fee
            + $children_paid_amt
            - $spiff_credit
            + $spiff_debit  # + means we give them
            - $rebate_credit
            + $rebate_debit
            - $residual     # + means we give them
            - $adjustment   # + means we give them
            - $promotion    # + means we give them
            + $consignment  # + means we charge them
            + $starting_balance;

        $ending_balance = $bill_amt;

        ### deposit paid amt ###
        $deposit_paid_amt = 0;
        if ($deposit_total > 0 && $ending_balance > 0) {
            $deposit_paid_amt = ($deposit_total >= $ending_balance) ? $ending_balance : $deposit_total;
        }
        $ending_balance -= $deposit_paid_amt;

        ### ach paid amt ###
        $ach_paid_amt = 0; # TODO : no ACH as of now 09/18/2017
        $account = Account::find($account_id);
        if ( isset($account) &&
             ($account->no_ach != 'Y') &&
             ( ($account->min_ach_amt == 0) || ($ending_balance >0 ) || ( $account->min_ach_amt <  abs($ending_balance) ) )
            ) {
            $ach_paid_amt = $ending_balance;
        }

        $ending_balance -= $ach_paid_amt;

        ### dist paid amt ###
        # - when there is bill bounce last week
        # TODO : when ACH, dist paid amt = $ending_balance
        $dist_paid_amt = 0;
        $ending_balance -= $dist_paid_amt;

        ### ending deposit ###
        $ending_deposit = $deposit_total - $deposit_paid_amt;

        return [
            'starting_balance' => $starting_balance,
            'starting_deposit' => $starting_deposit,
            'new_deposit' => $new_deposit,
            'deposit_total' => $deposit_total,
            'sales' => $sales,
            'sales_margin' => $sales_margin,
            'void' => $void,
            'void_margin' => $void_margin,
            'gross' => $gross,
            'net_margin' => $net_margin,
            'net_revenue' => $net_revenue,
            'fee' => $fee,
            'pm_fee' => $pm_fee,
            'children_paid_amt' => $children_paid_amt,
            'spiff_credit' => $spiff_credit,
            'spiff_debit' => $spiff_debit,
            'rebate_credit' => $rebate_credit,
            'rebate_debit' => $rebate_debit,
            'residual' => $residual,
            'adjustment' => $adjustment,
            'promotion' => $promotion,
            'consignment' => $consignment,
            'bill_amt' => $bill_amt,
            'dist_paid_amt' => $dist_paid_amt,
            'deposit_paid_amt' => $deposit_paid_amt,
            'ach_paid_amt' => $ach_paid_amt,
            'ending_balance' => $ending_balance,
            'ending_deposit' => $ending_deposit
        ];
    }

    public static function get_master_balance($master_id, $bill_date, $period_from, $period_to) {
        ### last week's billing check ###
        # - starting balance
        # - starting deposit
        $last_bill = Billing::where('account_id', $master_id)
            ->where('bill_date', $bill_date->copy()->subDays(7))
            ->first();

        $starting_balance = 0;
        $starting_deposit = 0;

        if (!empty($last_bill)) {
            $starting_balance = $last_bill->ending_balance;
            $starting_deposit = $last_bill->ending_deposit;
        }

        ### last week's bill ACH bounce should be added ###
        # TODO : when ACH, check last week's bill bounce
        $ach_bounce_amt = ACHPosting::where('account_id', $master_id)
            ->where('type', 'B')
            ->where('bounce_date', '>=', $period_from)
            ->where('bounce_date', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $ach_bounce_amt = round($ach_bounce_amt, 2);

        if (!self::$ach_started) {
            $ach_bounce_amt = 0;
        }

        $ach_bounce_fee = ACHPosting::where('account_id', $master_id)
                ->where('type', 'B')
                ->where('bounce_date', '>=', $period_from)
                ->where('bounce_date', '<', $period_to->copy()->addDay())
                ->count() * self::$bounce_fee;

        $ach_bounce_fee = round($ach_bounce_fee, 2);

        if (!self::$ach_started) {
            $ach_bounce_fee = 0;
        }

        ### new deposit ###
        $new_deposit = Payment::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $new_deposit = round($new_deposit, 2);

        ### deposit bounced amt ###
        # TODO : when ACH, check deposit ACH bounce amount
        $deposit_bounced_amt = 0;

        ### deposit total ###
        $deposit_total = $starting_deposit + $new_deposit - $deposit_bounced_amt;

        ### sales ###
        $sales = 0;

        ### sales margin ###
        $sales_margin = Commission::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('type', 'S')
            ->sum('comm_amt');

        $sales_margin = round($sales_margin, 2);

        ### void ###
        $void = 0;

        ### void margin ###
        $void_margin = Commission::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('type', 'V')
            ->sum('comm_amt');

        $void_margin = round($void_margin, 2);

        ### gross ###
        $gross = $sales - $void;

        ### net margin ###
        $net_margin = round($sales_margin, 2) - round($void_margin, 2);

        ### net revenue ###
        $net_revenue = $gross - $net_margin;

        $fee = 0;
        $pm_fee = 0;

        ### children paid amt ###
        $children_paid_amt = Account::join('billing', 'billing.account_id', 'accounts.id')
            ->where('accounts.parent_id', $master_id)
            ->whereIn('accounts.type', ['S', 'D'])
            ->where('billing.bill_date', $bill_date)
            ->where('billing.dist_paid_amt', '>', 0)
            ->sum('billing.dist_paid_amt');

        $children_paid_amt = round($children_paid_amt, 2);

        ### spiff ###
        # TODO : spiff logic needed
        $spiff_debit = 0;
        $spiff_credit = SpiffTrans::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        $spiff_credit = round($spiff_credit, 2);

        ### rebate ###
        $rebate_credit = RebateTrans::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

        $rebate_credit = round($rebate_credit, 2);

        $rebate_debit = 0;

        ### residual ###
        # TODO : residual logic needed
        $residual = 0;

        ### adjustment ###
        $adjustment = Credit::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'C', amt, -amt)"));

        $adjustment = round($adjustment, 2);

        ### promotion ###
        $promotion = Promotion::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'C', amount, -amount)"));

        $promotion = round($promotion, 2);

        ### consignment ###
        $consignment = ConsignmentCharge::where('account_id', $master_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', amt, -amt)"));

        $consignment = round($consignment, 2);

        ### bill amt ###
        $bill_amt = $net_revenue
            + $ach_bounce_amt
            + $ach_bounce_fee
            + $fee
            + $pm_fee
            + $children_paid_amt
            - $spiff_credit
            + $spiff_debit  # + means we give them
            - $rebate_credit
            + $rebate_debit
            - $residual     # + means we give them
            - $adjustment   # + means we give them
            - $promotion    # + means we give them
            + $consignment
            + $starting_balance;

        $ending_balance = $bill_amt;

        ### deposit paid amt ###
        $deposit_paid_amt = 0;
        if ($deposit_total > 0 && $ending_balance > 0) {
            $deposit_paid_amt = ($deposit_total >= $ending_balance) ? $ending_balance : $deposit_total;
        }
        $ending_balance -= $deposit_paid_amt;

        ### ach paid amt ###
        $ach_paid_amt = 0; #
        $account = Account::find($master_id);
        if ($account->pay_method == 'C' &&
           ($account->no_ach != 'Y') &&
           ( ($account->min_ach_amt == 0) || ($ending_balance >0 ) || ( $account->min_ach_amt <  abs($ending_balance) ) ) &&
            self::$ach_started) {
            $ach_paid_amt = $ending_balance;
        }

        $ending_balance -= $ach_paid_amt;

        ### dist paid amt ###
        $dist_paid_amt = 0;
        $ending_balance -= $dist_paid_amt;

        ### ending deposit ###
        $ending_deposit = $deposit_total - $deposit_paid_amt;

        return [
            'starting_balance' => $starting_balance,
            'starting_deposit' => $starting_deposit,
            'ach_bounce_amt' => $ach_bounce_amt,
            'ach_bounce_fee' => $ach_bounce_fee,
            'new_deposit' => $new_deposit,
            'deposit_total' => $deposit_total,
            'sales' => $sales,
            'sales_margin' => $sales_margin,
            'void' => $void,
            'void_margin' => $void_margin,
            'gross' => $gross,
            'net_margin' => $net_margin,
            'net_revenue' => $net_revenue,
            'fee' => $fee,
            'pm_fee' => $pm_fee,
            'children_paid_amt' => $children_paid_amt,
            'spiff_credit' => $spiff_credit,
            'spiff_debit' => $spiff_debit,
            'rebate_credit' => $rebate_credit,
            'rebate_debit' => $rebate_debit,
            'residual' => $residual,
            'adjustment' => $adjustment,
            'promotion' => $promotion,
            'consignment' => $consignment,
            'bill_amt' => $bill_amt,
            'dist_paid_amt' => $dist_paid_amt,
            'deposit_paid_amt' => $deposit_paid_amt,
            'ach_paid_amt' => $ach_paid_amt,
            'ending_balance' => $ending_balance,
            'ending_deposit' => $ending_deposit
        ];
    }

    public static function get_master_limit($account_id) {
        $account = Account::where('id', $account_id)
            ->where('type', 'M')
            ->first();

        if (empty($account)) {
            return 0;
        }

        if ($account->status != 'A') {
            return 0;
        }

        $bill_date = Carbon::today()->startOfWeek()->copy()->addDays(7);
        $period_from = Carbon::today()->startOfWeek();
        $period_to = $bill_date->copy()->subDay();
        /*$ret = self::get_master_balance($account->id, $bill_date, $period_from, $period_to);
        Helper::log(' ### get_master_balance() result ###', $ret);
        $balance = $ret['deposit_total'] + $account->credit_limit - $ret['bill_amt'];

        return $balance;*/

        $gross = Transaction::join('accounts', 'accounts.id', '=', 'transaction.account_id')
            ->where('accounts.path', 'like', $account->path . '%')
            ->where('transaction.status', 'C')
            ->where('transaction.cdate', '>=', $period_from)
            ->where('transaction.cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt + transaction.fee + transaction.pm_fee, -(transaction.collection_amt + transaction.fee + transaction.pm_fee))"));

        return $account->credit_limit - $gross;
    }

    public static function get_distributor_balance($dist_id, $bill_date, $period_from, $period_to) {
        ### last week's billing check ###
        # - starting balance
        # - starting deposit
        $last_bill = Billing::where('account_id', $dist_id)
            ->where('bill_date', $bill_date->copy()->subDays(7))
            ->first();

        $starting_balance = 0;
        $starting_deposit = 0;

        if (!empty($last_bill)) {
            $starting_balance = $last_bill->ending_balance;
            $starting_deposit = $last_bill->ending_deposit;
        }

        ### last week's bill ACH bounce should be added ###
        $ach_bounce_amt = ACHPosting::where('account_id', $dist_id)
            ->where('type', 'B')
            ->where('bounce_date', '>=', $period_from)
            ->where('bounce_date', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $ach_bounce_amt = round($ach_bounce_amt, 2);

        if (!self::$ach_started) {
            $ach_bounce_amt = 0;
        }

        $ach_bounce_fee = ACHPosting::where('account_id', $dist_id)
                ->where('type', 'B')
                ->where('bounce_date', '>=', $period_from)
                ->where('bounce_date', '<', $period_to->copy()->addDay())
                ->count() * self::$bounce_fee;

        $ach_bounce_fee = round($ach_bounce_fee, 2);

        if (!self::$ach_started) {
            $ach_bounce_fee = 0;
        }

        ### new deposit ###
        $new_deposit = Payment::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $new_deposit = round($new_deposit, 2);

        ### deposit bounced amt ###
        # TODO : when ACH, check deposit ACH bounce amount
        $deposit_bounced_amt = 0;

        ### deposit total ###
        $deposit_total = $starting_deposit + $new_deposit - $deposit_bounced_amt;

        ### sales ###
        $sales = 0;

        ### sales margin ###
        $sales_margin = Commission::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('type', 'S')
            ->sum('comm_amt');

        $sales_margin = round($sales_margin, 2);

        ### void ###
        $void = 0;

        ### void margin ###
        $void_margin = Commission::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('type', 'V')
            ->sum('comm_amt');

        $void_margin = round($void_margin, 2);

        ### gross ###
        $gross = $sales - $void;

        ### net margin ###
        $net_margin = $sales_margin - $void_margin;

        ### net revenue ###
        $net_revenue = $gross - $net_margin;

        $fee = 0;
        $pm_fee = 0;

        ### children paid amt : only Master is responsible ###
        $children_paid_amt = Account::join('billing', 'billing.account_id', 'accounts.id')
            ->where('accounts.parent_id', $dist_id)
            ->where('accounts.type', 'S')
            ->where('billing.bill_date', $bill_date)
            ->where('billing.dist_paid_amt', '>', 0)
            ->sum('billing.dist_paid_amt');

        $children_paid_amt = round($children_paid_amt, 2);

        ### spiff ###
        $spiff_credit = SpiffTrans::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));
        $spiff_debit = 0;

        ### rebate ###
        $rebate_credit = RebateTrans::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));
        $rebate_debit = 0;

        ### residual ###
        # TODO : residual logic needed
        $residual = 0;

        ### adjustment ###
        # TODO : adjustment logic & UI needed
        $adjustment = Credit::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'C', amt, -amt)"));

        $adjustment = round($adjustment, 2);

        ### promotion ###
        $promotion = Promotion::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'C', amount, -amount)"));

        $promotion = round($promotion, 2);

        ### consignment ###
        $consignment = ConsignmentCharge::where('account_id', $dist_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', amt, -amt)"));

        $consignment = round($consignment, 2);

        ### bill amt ###
        $bill_amt = $net_revenue
            + $ach_bounce_amt
            + $ach_bounce_fee
            + $fee
            + $pm_fee
            + $children_paid_amt
            - $spiff_credit         # + means we give them
            + $spiff_debit
            - $rebate_credit
            + $rebate_debit
            - $residual             # + means we give them
            - $adjustment           # + means we give them
            - $promotion            # + means we give them
            + $consignment
            + $starting_balance;

        $ending_balance = $bill_amt;

        ### deposit paid amt ###
        $deposit_paid_amt = 0;
        if ($deposit_total > 0 && $ending_balance > 0) {
            $deposit_paid_amt = ($deposit_total >= $ending_balance) ? $ending_balance : $deposit_total;
        }
        $ending_balance -= $deposit_paid_amt;

        ### ach paid amt ###
        $ach_paid_amt = 0; #
        $account = Account::find($dist_id);
        if ( $account->pay_method == 'C' &&
             ($account->no_ach != 'Y') &&
             ( ($account->min_ach_amt == 0) || ($ending_balance >0 ) || ( $account->min_ach_amt <  abs($ending_balance) ) ) &&
            $ach_bounce_amt == 0 &&
            $ach_bounce_fee == 0 &&
            self::$ach_started) {
            $ach_paid_amt = $ending_balance;
        }

        $ending_balance -= $ach_paid_amt;

        ### master paid amt ###
        $dist_paid_amt = 0;
        switch ($account->pay_method) {
            case 'P':
                if ($ending_balance > 0 && self::$ach_started) {
                    $dist_paid_amt = $ending_balance;
                }
                break;
            case 'C':
                if ($ach_bounce_amt > 0 && self::$ach_started) {
                    $dist_paid_amt = $ending_balance;
                }
                break;
        }

        $ending_balance -= $dist_paid_amt;

        ### ending deposit ###
        $ending_deposit = $deposit_total - $deposit_paid_amt;

        return [
            'starting_balance' => $starting_balance,
            'starting_deposit' => $starting_deposit,
            'ach_bounce_amt' => $ach_bounce_amt,
            'ach_bounce_fee' => $ach_bounce_fee,
            'new_deposit' => $new_deposit,
            'deposit_total' => $deposit_total,
            'sales' => $sales,
            'sales_margin' => $sales_margin,
            'void' => $void,
            'void_margin' => $void_margin,
            'gross' => $gross,
            'net_margin' => $net_margin,
            'net_revenue' => $net_revenue,
            'fee' => $fee,
            'pm_fee' => $pm_fee,
            'children_paid_amt' => $children_paid_amt,
            'spiff_credit' => $spiff_credit,
            'spiff_debit' => $spiff_debit,
            'rebate_credit' => $rebate_credit,
            'rebate_debit' => $rebate_debit,
            'residual' => $residual,
            'adjustment' => $adjustment,
            'promotion' => $promotion,
            'consignment' => $consignment,
            'bill_amt' => $bill_amt,
            'dist_paid_amt' => $dist_paid_amt,
            'deposit_paid_amt' => $deposit_paid_amt,
            'ach_paid_amt' => $ach_paid_amt,
            'ending_balance' => $ending_balance,
            'ending_deposit' => $ending_deposit
        ];
    }

    public static function get_dist_limit($account_id) {
        $account = Account::where('id', $account_id)
            ->where('type', 'D')
            ->first();

        if (empty($account)) {
            return 0;
        }

        if ($account->status != 'A') {
            return 0;
        }

        ### check master ###
        $master = Account::find($account->master_id);
        if (empty($master)) {
            return 0;
        }

        if ($master->status != 'A') {
            return 0;
        }

        $bill_date = Carbon::today()->startOfWeek()->copy()->addDays(7);
        $period_from = Carbon::today()->startOfWeek();
        $period_to = $bill_date->copy()->subDay();


        /*$ret = self::get_distributor_balance($account->id, $bill_date, $period_from, $period_to);

        Helper::log(' ### get_distributor_balance() result ###', $ret);

        $balance = $ret['deposit_total'] + $account->credit_limit - $ret['bill_amt'];*/

        $gross = Transaction::join('accounts', 'accounts.id', '=', 'transaction.account_id')
            ->where('accounts.path', 'like', $account->path . '%')
            ->where('transaction.status', 'C')
            ->where('transaction.cdate', '>=', $period_from)
            ->where('transaction.cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(transaction.type = 'S', transaction.collection_amt + transaction.fee + transaction.pm_fee, -(transaction.collection_amt + transaction.fee + transaction.pm_fee))"));

        return $account->credit_limit - $gross;

    }

    public static function get_limit_string($account_id) {
        ### check account ###
        $account = Account::find($account_id);
        if (empty($account)) {
            return '$<span style="color:red;" title="Invalid account ID">0.00</span>';
        }

        $balance = self::get_sub_agent_balance_current($account); // $ret['deposit_total'] + $account->credit_limit - $ret['bill_amt'];

        if ($account->status != 'A') {
            return '$<span style="color:red;" title="Your account is not active">' .number_format($balance, 2) . '</span>';
        }

        if ($account->type != 'S') {
            return '$<span style="color:red;" title="Your account is not sub-agent">' . number_format($balance, 2) . '</span>';
        }

        ### check parent ###
        $parent = Account::find($account->parent_id);
        if (empty($parent)) {
            return '$<span style="color:red;" title="Unable to find your parent account">' . number_format($balance, 2) . '</span>';
        }

        if ($parent->status != 'A') {
            return '$<span style="color:red;" title="Your parent account is not active">' . number_format($balance, 2) . '</span>';;
        }

        ### check master ###
        $master = Account::find($account->master_id);
        if (empty($master)) {
            return '$<span style="color:red;" title="Unable to find your master account">' . number_format($balance, 2) . '</span>';;
        }

        if ($master->status != 'A') {
            return '$<span style="color:red;" title="Your master account is not active">' . number_format($balance, 2) . '</span>';;
        }


        return '$<span style="color:green;">' . number_format($balance, 2) . '</span>';;
    }

    public static function get_limit($account_id) {

        ### check account ###
        $account = Account::find($account_id);
        if (empty($account)) {
            return 0;
        }

        if ($account->status != 'A') {
            return 0;
        }

        if ($account->type != 'S') {
            return 0;
        }

        ### check parent ###
        $parent = Account::find($account->parent_id);
        if (empty($parent)) {
            return 0;
        }

        if ($parent->status != 'A') {
            return 0;
        }

        ### check master ###
        $master = Account::find($account->master_id);
        if (empty($master)) {
            return 0;
        }

        if ($master->status != 'A') {
            return 0;
        }

        $balance = self::get_sub_agent_balance_current($account);

        return $balance;
    }

    public static function get_sub_agent_balance(
        $account_id, $bill_date, $period_from, $period_to
    ) {
        ### last week's billing check ###
        # - starting balance
        # - starting deposit
        $last_bill = Billing::where('account_id', $account_id)
            ->where('bill_date', $bill_date->copy()->subDays(7))
            ->first();

//        if (empty($last_bill)) { ##In case of 0~4 AM Monday, it reference one week before.
//            $last_bill = Billing::where('account_id', $account_id)
//                ->where('bill_date', $bill_date->copy()->subDays(14))
//                ->first();
//        }

        $starting_balance = 0;
        $starting_deposit = 0;

        if (!empty($last_bill)) {
            $starting_balance = $last_bill->ending_balance;
            $starting_deposit = $last_bill->ending_deposit;
        }

        ### last week's bill ACH bounce should be added (ONLY for Monday billing)###
        $ach_bounce_amt = ACHPosting::where('account_id', $account_id)
            ->where('type', 'B')
            ->where('bounce_date', '>=', $period_from)
            ->where('bounce_date', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $ach_bounce_amt = round($ach_bounce_amt, 2);

        if (!self::$ach_started) {
            $ach_bounce_amt = 0;
        }

        $ach_bounce_fee = ACHPosting::where('account_id', $account_id)
            ->where('type', 'B')
            ->where('bounce_date', '>=', $period_from)
            ->where('bounce_date', '<', $period_to->copy()->addDay())
            ->count() * self::$bounce_fee;

        $ach_bounce_fee = round($ach_bounce_fee, 2);

        if (!self::$ach_started) {
            $ach_bounce_fee = 0;
        }

        ### new deposit ###
        $new_deposit = Payment::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $new_deposit = round($new_deposit, 2);

//        $a = round(78.78098943, 2);
//        Helper::log('### new_deposit ###', [
//            'new_deposit' => $new_deposit,
//            'round(2)' => round($new_deposit, 2),
//            'round(78.78098943, 2) again' => $a
//        ]);

        ### deposit bounced amt ###
        $deposit_bounced_amt = Payment::join('ach_posting', 'payment.ach_posting_id', '=', 'ach_posting.id')
            ->where('payment.account_id', $account_id)
            ->where('ach_posting.type', '!=', 'B')  ### for non-billing ACH, W : weekday & P : Prepay
            ->where('ach_posting.bounce_date', '>=', $period_from)
            ->where('ach_posting.bounce_date', '<', $period_to->copy()->addDay())
            ->sum('payment.amt');

        $deposit_bounced_amt = round($deposit_bounced_amt, 2);

        ### deposit total ###
        $deposit_total = $starting_deposit + $new_deposit - $deposit_bounced_amt;

        ### sales ###
        $sales = Transaction::where('account_id', $account_id)
            ->where('type', 'S')
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('status', '!=', 'F')
            ->sum('collection_amt');

        $sales = round($sales, 2);

        ### sales margin ###
        $sales_margin = Commission::join('transaction', 'transaction.id', 'commission.trans_id')
            ->where('commission.account_id', $account_id)
            ->where('transaction.cdate', '>=', $period_from)
            ->where('transaction.cdate', '<', $period_to->copy()->addDay())
            ->where('commission.type', 'S')
            ->sum('commission.comm_amt');

        $sales_margin = round($sales_margin, 2);

        ### void ###
        $void = Transaction::where('account_id', $account_id)
            ->where('type', 'V')
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('status', '!=', 'F')
            ->sum('collection_amt');

        $void = round($void, 2);

        ### void margin ###
        $void_margin = Commission::join('transaction', 'transaction.id', 'commission.trans_id')
            ->where('commission.account_id', $account_id)
            ->where('transaction.cdate', '>=', $period_from)
            ->where('transaction.cdate', '<', $period_to->copy()->addDay())
            ->where('commission.type', 'V')
            ->sum('commission.comm_amt');

        $void_margin = round($void_margin, 2);

        ### gross ###
        $gross = $sales - $void;

        ### net margin ###
        $net_margin = $sales_margin - $void_margin;

        ### net revenue ###
        $net_revenue = $gross - $net_margin;

        ### fee ###
        $fee = Transaction::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('status', '!=', 'F')
            ->sum(DB::raw("if(type = 'S', fee, -fee)"));

        $fee = round($fee, 2);

        ### pm_fee ###
        $pm_fee = Transaction::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->where('status', '!=', 'F')
            ->sum(DB::raw("if(type = 'S', pm_fee, -pm_fee)"));

        $pm_fee = round($pm_fee, 2);

        ### children paid amt ###
        # always 0 for sub agent
        $children_paid_amt = 0;

        ### spiff ###
        # TODO : spiff logic needed
        $spiff_credit = SpiffTrans::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        $spiff_credit = round($spiff_credit, 2);

        $spiff_debit = 0;

        ### rebate ###
        $rebate_credit = RebateTrans::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

        $rebate_credit = round($rebate_credit, 2);

        $rebate_debit = 0;

        ### residual ###
        # TODO : residual logic needed
        $residual = Residual::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum('amt');

        $residual = round($residual, 2);

        ### adjustment ###
        # TODO : adjustment logic & UI needed
        $adjustment = Credit::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'C', amt, -amt)"));

        $adjustment = round($adjustment, 2);

        ### promotion ###
        $promotion = Promotion::where('account_id', $account_id)
            ->where('cdate', '>=', $period_from)
            ->where('cdate', '<', $period_to->copy()->addDay())
            ->sum(DB::raw("if(type = 'C', amount, -amount)"));

        $promotion = round($promotion, 2);

        ### consignment ###
        # Sub agent consignment charge is applied to transaction - collection amount - already.
        $consignment = 0;

        ### bill amt ###
        $bill_amt = $starting_balance
            + $ach_bounce_amt
            + $ach_bounce_fee
            + $net_revenue
            + $children_paid_amt
            + $fee
            + $pm_fee
            - $spiff_credit # + means we give them
            + $spiff_debit
            - $rebate_credit
            + $rebate_debit
            - $residual     # + means we give them
            - $adjustment   # + means we give them
            - $promotion   # + means we give them
            + $consignment;

        $ending_balance = $bill_amt;

        ### deposit paid amt ###
        $deposit_paid_amt = 0;
        if ($deposit_total > 0 && $ending_balance > 0) {
            $deposit_paid_amt = ($deposit_total >= $ending_balance) ? $ending_balance : $deposit_total;
        }
        $ending_balance -= $deposit_paid_amt;

        ### ach paid amt ###
        $ach_paid_amt = 0;
        $account = Account::find($account_id);
        if ( ($account->pay_method == 'C') &&
             ($ending_balance > 0) &&
             ($ach_bounce_amt == 0) &&
             ($ach_bounce_fee == 0 ) &&
             ($account->no_ach != 'Y' ) &&
            ( ($ending_balance >0 ) || ( $account->min_ach_amt <  abs($ending_balance) ) ) &&
             self::$ach_started) {
            $ach_paid_amt = $ending_balance;
        }

        $ending_balance -= $ach_paid_amt;

        ### master paid amt ###
        $dist_paid_amt = 0;
        switch ($account->pay_method) {
            case 'P':
                if ($ending_balance > 0 && self::$ach_started) {
                    $dist_paid_amt = $ending_balance;
                }
                break;
            case 'C':
                if ($ach_bounce_amt > 0 && self::$ach_started) {
                    $dist_paid_amt = $ending_balance;
                }
                break;
        }

        $ending_balance -= $dist_paid_amt;

        ### ending deposit ###
        $ending_deposit = $deposit_total - $deposit_paid_amt;

        return [
            'starting_balance' => $starting_balance,
            'starting_deposit' => $starting_deposit,
            'ach_bounce_amt' => $ach_bounce_amt,
            'ach_bounce_fee' => $ach_bounce_fee,
            'new_deposit' => $new_deposit,
            'deposit_total' => $deposit_total,
            'sales' => $sales,
            'sales_margin' => $sales_margin,
            'void' => $void,
            'void_margin' => $void_margin,
            'gross' => $gross,
            'net_margin' => $net_margin,
            'net_revenue' => $net_revenue,
            'children_paid_amt' => $children_paid_amt,
            'fee' => $fee,
            'pm_fee' => $pm_fee,
            'spiff_credit' => $spiff_credit,
            'spiff_debit' => $spiff_debit,
            'rebate_credit' => $rebate_credit,
            'rebate_debit' => $rebate_debit,
            'residual' => $residual,
            'adjustment' => $adjustment,
            'promotion' => $promotion,
            'consignment' => $consignment,
            'bill_amt' => $bill_amt,
            'dist_paid_amt' => $dist_paid_amt,
            'deposit_paid_amt' => $deposit_paid_amt,
            'ach_paid_amt' => $ach_paid_amt,
            'ending_balance' => $ending_balance,
            'ending_deposit' => $ending_deposit
        ];
    }


    public static function get_sub_agent_balance_current($account) {
        ### last week's billing check ###
        # - starting balance
        # - starting deposit
        $last_bill = Billing::where('account_id', $account->id)
            ->orderBy('bill_date', 'desc')
            ->first();

        $starting_balance = 0;
        $starting_deposit = 0;

        $period_from = '2000-01-01';

        if (!empty($last_bill)) {
            $period_from = $last_bill->bill_date;  //Reset period_from as the latest bill_date
            $starting_balance = $last_bill->ending_balance;
            $starting_deposit = $last_bill->ending_deposit;
        }

        ### last week's bill ACH bounce should be added ###
        $ach_bounce_amt = ACHPosting::where('account_id', $account->id)
            ->where('type', 'B')
            ->where('bounce_date', '>=', $period_from)
            ->sum('amt');

        $ach_bounce_amt = round($ach_bounce_amt, 2);

        if (!self::$ach_started) {
            $ach_bounce_amt = 0;
        }

        $ach_bounce_fee = ACHPosting::where('account_id', $account->id)
                ->where('type', 'B')
                ->where('bounce_date', '>=', $period_from)
                ->count() * self::$bounce_fee;

        $ach_bounce_fee = round($ach_bounce_fee, 2);

        if (!self::$ach_started) {
            $ach_bounce_fee = 0;
        }

        ### new deposit ###
        $new_deposit = Payment::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->sum('amt');

        $new_deposit = round($new_deposit, 2);

//       Hmm... round function seem not to work.. it returns the full digits.
//        $a = round(78.78098943, 2);
//        Helper::log('### new_deposit ###', [
//            'new_deposit' => $new_deposit,
//            'round(2)' => round($new_deposit, 2),
//            'round(78.78098943, 2) again' => $a
//        ]);

        ### deposit bounced amt ###
        $deposit_bounced_amt = Payment::join('ach_posting', 'payment.ach_posting_id', '=', 'ach_posting.id')
            ->where('payment.account_id', $account->id)
            ->where('ach_posting.type', '!=', 'B')  ### for non-billing ACH, W : weekday & P : Prepay
            ->where('ach_posting.bounce_date', '>=', $period_from)
            ->sum('payment.amt');

        $deposit_bounced_amt = round($deposit_bounced_amt, 2);

        ### deposit total ###
        $deposit_total = $starting_deposit + $new_deposit - $deposit_bounced_amt;

        ### sales ###
        $sales = Transaction::where('account_id', $account->id)
            ->where('type', 'S')
            ->where('cdate', '>=', $period_from)
            ->where('status', '!=', 'F')
            ->sum('collection_amt');

        $sales = round($sales, 2);

        ### sales margin ###
        $sales_margin = Commission::join('transaction', 'transaction.id', 'commission.trans_id')
            ->where('commission.account_id', $account->id)
            ->where('transaction.cdate', '>=', $period_from)
            ->where('commission.type', 'S')
            ->sum('commission.comm_amt');

        $sales_margin = round($sales_margin, 2);

        ### void ###
        $void = Transaction::where('account_id', $account->id)
            ->where('type', 'V')
            ->where('cdate', '>=', $period_from)
            ->where('status', '!=', 'F')
            ->sum('collection_amt');

        $void = round($void, 2);

        ### void margin ###
        $void_margin = Commission::join('transaction', 'transaction.id', 'commission.trans_id')
            ->where('commission.account_id', $account->id)
            ->where('transaction.cdate', '>=', $period_from)
            ->where('commission.type', 'V')
            ->sum('commission.comm_amt');

        $void_margin = round($void_margin, 2);

        ### gross ###
        $gross = $sales - $void;

        ### net margin ###
        $net_margin = $sales_margin - $void_margin;

        ### net revenue ###
        $net_revenue = $gross - $net_margin;

        ### fee ###
        $fee = Transaction::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->where('status', '!=', 'F')
            ->sum(DB::raw("if(type = 'S', fee, -fee)"));

        $fee = round($fee, 2);

        ### pm_fee ###
        $pm_fee = Transaction::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->where('status', '!=', 'F')
            ->sum(DB::raw("if(type = 'S', pm_fee, -pm_fee)"));

        $pm_fee = round($pm_fee, 2);

        ### children paid amt ###
        # always 0 for sub agent
        $children_paid_amt = 0;

        ### spiff ###
        # TODO : spiff logic needed
        $spiff_credit = SpiffTrans::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        $spiff_credit = round($spiff_credit, 2);

        $spiff_debit = 0;

        ### rebate ###
        $rebate_credit = RebateTrans::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

        $rebate_credit = round($rebate_credit, 2);

        $rebate_debit = 0;

        ### residual ###
        # TODO : residual logic needed
        $residual = Residual::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->sum('amt');

        $residual = round($residual, 2);

        ### adjustment ###
        # TODO : adjustment logic & UI needed
        $adjustment = Credit::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->sum(DB::raw("if(type = 'C', amt, -amt)"));

        $adjustment = round($adjustment, 2);

        ### promotion ###
        $promotion = Promotion::where('account_id', $account->id)
            ->where('cdate', '>=', $period_from)
            ->sum(DB::raw("if(type = 'C', amount, -amount)"));

        $promotion = round($promotion, 2);

        ### consignment ###
        # Sub agent consignment charge is applied to transaction - collection amount - already.
        $consignment = 0;

        ### bill amt ###
        $bill_amt = $starting_balance
            + $ach_bounce_amt
            + $ach_bounce_fee
            + $net_revenue
            + $children_paid_amt
            + $fee
            + $pm_fee
            - $spiff_credit # + means we give them
            + $spiff_debit
            - $rebate_credit
            + $rebate_debit
            - $residual     # + means we give them
            - $adjustment   # + means we give them
            - $promotion   # + means we give them
            + $consignment;

        $balance = $deposit_total + $account->credit_limit - $bill_amt;

        return $balance;
    }
}