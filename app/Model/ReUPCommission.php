<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReUPCommission extends Model
{
    protected $table = 'reup_commission';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getDealerM2PaidAttribute() {
        $act_trans_id = $this->attributes['act_trans_id'];
        $account_id = $this->attributes['account_id'];

        $dealer_m2_paid = SpiffTrans::where('trans_id', $act_trans_id)
            ->where('spiff_month', 2)
            ->where('account_id', $account_id)
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        return $dealer_m2_paid;
    }

    public function getDealerM3PaidAttribute() {
        $act_trans_id = $this->attributes['act_trans_id'];
        $account_id = $this->attributes['account_id'];

        $dealer_m2_paid = SpiffTrans::where('trans_id', $act_trans_id)
            ->where('spiff_month', 3)
            ->where('account_id', $account_id)
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        return $dealer_m2_paid;
    }

    public function getDealerResidualPaidAttribute() {
        $act_trans_id = $this->attributes['act_trans_id'];
        $account_id = $this->attributes['account_id'];

        $dealer_residual = Residual::where('trans_id', $act_trans_id)
            ->where('account_id', $account_id)
            ->sum(DB::raw("if(type = 'S', amt, -amt)"));

        return $dealer_residual;
    }

    public function getTotalDealerPaidAttribute() {
        return $this->dealer_m2_paid + $this->dealer_m3_paid + $this->dealer_residual_paid;
    }
}
