<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SpiffSetup extends Model
{
    protected $table = 'spiff_setup';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getLastUpdatedAttribute() {
        return empty($this->attributes['mdate']) ?
            ( $this->attributes['cdate'] . ' (' . $this->attributes['created_by'] . ')') :
            ( $this->attributes['mdate'] . ' (' . $this->attributes['modified_by'] . ')');
    }

    public function getAccountTypeNameAttribute() {
        switch ($this->attributes['account_type']) {
            case 'M':
                return 'Master';
            case 'D':
                return 'Distributor';
            case 'S':
                return 'Sub-Agent';
            default:
                return $this->attributes['account_type'];
        }
    }

    public function getCarrierAttribute() {
        $product = Product::find($this->attributes['product_id']);
        if (empty($product)) {
            return '';
        }

        return $product->carrier;
    }

    public $appends = ['last_updated', 'carrier'];
}
