<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class FreeUPSim extends Model
{
    protected $table = 'freeup_sims';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'sim_serial';

    public $incrementing = false;

    public function getProductAttribute() {

        $vendor_denom = VendorDenom::where('vendor_code', 'LYC')
            ->where('act_pid', $this->attributes['vendor_pid'])
            ->where('denom', $this->attributes['amount'])
            ->first();
        if (empty($vendor_denom)) {
            return '';
        }

        $product = Product::find($vendor_denom->product_id);
        if (empty($product)) {
            return '';
        }

        return $product->name;
    }

    public function getStatusNameAttribute() {
        switch ($this->attributes['status']) {
            case 'A':
                return 'Active';
            case 'H':
                return 'On-Hold';
            case 'S':
                return 'Suspended';
            case 'U':
                return 'Used';
        }

        return $this->attributes['status'];
    }

    public function getTypeNameAttribute() {
        switch ($this->attributes['type']) {
            case 'B':
                return 'Bundled';
            case 'P':
                return 'Wallet';
            case 'R':
                return 'Regular';
        }

        return $this->attributes['type'];
    }
}
