<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VendorDenom extends Model
{
    protected $table = 'vendor_denom';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getActPidAttribute() {
        $act_pid = $this->attributes['act_pid'];
        if (empty($act_pid)) {
            return '';
        }

        return $act_pid;
    }

    public function getRtrPidAttribute() {
        $rtr_pid = $this->attributes['rtr_pid'];
        if (empty($rtr_pid)) {
            return '';
        }

        return $rtr_pid;
    }

    public function getPinPidAttribute() {
        $pin_pid = $this->attributes['pin_pid'];
        if (empty($pin_pid)) {
            return '';
        }

        return $pin_pid;
    }

    public function getLastUpdatedAttribute() {
        return empty($this->attributes['mdate']) ?
            ( $this->attributes['cdate'] . ' ( ' . $this->attributes['created_by'] . ' )') :
            ( $this->attributes['mdate'] . ' ( ' . $this->attributes['modified_by'] . ' )');
    }

    public $appends = ['last_updated', 'act_pid', 'rtr_pid', 'pin_pid'];
}
