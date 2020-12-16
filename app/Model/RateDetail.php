<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RateDetail extends Model
{
    protected $table = 'rate_detail';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getProductNameAttribute() {
        $denom_id = $this->attributes['denom_id'];
        $denom = Denom::find($denom_id);
        if (empty($denom)) {
            return '';
        }

        $product = Product::find($denom->product_id);
        if (empty($product)) {
            return '';
        }

        return $product->name;
    }
}
