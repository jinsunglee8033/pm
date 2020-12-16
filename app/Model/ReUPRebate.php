<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReUPRebate extends Model
{
    protected $table = 'reup_rebate';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getPaidAttribute() {
        $act_trans_id = $this->attributes['act_trans_id'];
        $account_id = $this->attributes['account_id'];

        $paid = RebateTrans::where('trans_id', $act_trans_id)
            ->where('account_id', $account_id)
            ->sum(DB::raw("if(type = 'S', rebate_amt, -rebate_amt)"));

        return $paid;
    }
}
