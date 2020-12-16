<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RatePlan extends Model
{
    protected $table = 'rate_plan';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getLastUpdatedAttribute() {
        if (empty($this->attributes['mdate'])) {
            return $this->attributes['cdate'] . ' ( ' . $this->attributes['created_by'] . ' )';
        }

        return $this->attributes['mdate'] . ' ( ' . $this->attributes['modified_by'] . ' )';
    }

    public function getAssignedQtyAttribute() {
        $ret = DB::select("
            select count(*) as assigned_qty
            from accounts 
            where rate_plan_id = :rate_plan_id
        ", [
            'rate_plan_id' => $this->attributes['id']
        ]);

        if (count($ret) < 1) {
            return 0;
        }

        return $ret[0]->assigned_qty;
    }

    public $appends = ['last_updated', 'assigned_qty'];
}
