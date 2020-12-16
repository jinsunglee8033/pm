<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Billing extends Model
{
    protected $table = 'billing';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getPaidTotalAttribute() {
        $deposit_paid_amt = $this->attributes['deposit_paid_amt'];
        $ach_paid_amt = $this->attributes['ach_paid_amt'];
        $dist_paid_amt = $this->attributes['dist_paid_amt'];

        return $deposit_paid_amt + $ach_paid_amt + $dist_paid_amt;
    }

    public function getPayableAttribute() {
        $starting_balance = $this->attributes['starting_balance'];
        $net_revenue = $this->attributes['net_revenue'];
        $fee = $this->attributes['fee'];
        $consignment = $this->attributes['consignment'];

        return $starting_balance + $net_revenue + $fee + $consignment;
    }

    public function getExtraAttribute() {
        $spiff_credit = $this->attributes['spiff_credit'];
        $spiff_debit = $this->attributes['spiff_debit'];
        $rebate_credit = $this->attributes['rebate_credit'];
        $rebate_debit = $this->attributes['rebate_debit'];
        $residual = $this->attributes['residual'];
        $adjustment = $this->attributes['adjustment'];
        $promotion = $this->attributes['promotion'];

        return $spiff_credit - $spiff_debit + $rebate_credit - $rebate_debit + $residual + $adjustment + $promotion;
    }

    public $appends = ['paid_total', 'payable', 'extra'];
}
