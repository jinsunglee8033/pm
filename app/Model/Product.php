<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public function getDenominationsAttribute() {

        return Denom::where('product_id', $this->id)
            ->where('status', 'A')->get();

    }

    public function getActivationDenominationsAttribute() {
        return Denom::join('vendor_denom', function($join) {
                $join->on('denomination.product_id', 'vendor_denom.product_id');
                $join->on('denomination.denom', 'vendor_denom.denom');

            })
            ->where('denomination.product_id', $this->id)
            ->where('vendor_denom.vendor_code', $this->vendor_code)
            ->where('denomination.status', 'A')
            ->where('vendor_denom.status', 'A')
            ->whereRaw("ifnull(vendor_denom.act_pid, '') != ''")
            ->selectRaw("denomination.*")
            ->get();
    }


    public function getStatusNameAttribute() {
        switch($this->attributes['status']) {
            case 'A':
                return 'Active';
            case 'H':
                return 'On-Hold';
            case 'C':
                return 'Closed';
            default:
                return $this->attributes['status'];
        }
    }

    public function getVendorAttribute() {
        $vendor = Vendor::find($this->attributes['vendor_code']);
        if (empty($vendor)) {
            return '';
        }

        return $vendor->name;
    }

    public function getActivationNameAttribute() {
        switch ($this->attributes['activation']) {
            case 'Y':
                return 'Yes';
            case 'N':
                return '-';
            default:
                return $this->attributes['activation'];
        }
    }

    public function getLastUpdatedAttribute() {
        return empty($this->attributes['mdate']) ?
            ( $this->attributes['cdate'] . ' ( ' . $this->attributes['created_by'] . ' )') :
            ( $this->attributes['mdate'] . ' ( ' . $this->attributes['modified_by'] . ' )');
    }

    public $appends = ['status_name', 'vendor', 'last_updated', 'activation_name'];
}
