<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class H2OManualRTR extends Model
{
    protected $table = 'h2o_manual_rtr';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getStatusNameAttribute() {
        switch ($this->attributes['status']) {
            case 'N':
                return 'Waiting';
            case 'S':
                return 'Success';
        }

        return $this->attributes['status'];
    }

    public function getLastUpdatedAttribute() {
        return empty($this->attributes['mdate']) ? $this->attributes['cdate'] . ' ( ' . $this->attributes['created_by'] . ' )' : $this->attributes['mdate'] . ' ( ' . $this->attributes['modified_by'] . ' )';
    }

    public function getRtrStatusAttribute() {
        $r = RTRQueue::find($this->attributes['rtr_id']);
        if (empty($r)) {
            return '';
        }

        switch ($r->result) {
            case 'N':
                return 'Waiting';
            case 'S':
                return 'Success';
            case 'F':
                return 'Failed';
            case 'P':
                return 'Processing';
            default:
                return $r-result;
        }
    }

    public function getRtrMessageAttribute() {
        $r = RTRQueue::find($this->attributes['rtr_id']);
        if (empty($r)) {
            return '';
        }

        return $r->result_msg;
    }

    public function getRanAtAttribute() {
        $r = RTRQueue::find($this->attributes['rtr_id']);
        if (empty($r)) {
            return '';
        }

        return $r->result_date;
    }
}
