<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockMapping extends Model
{
    protected $table = 'stock_mapping';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';


    public function getProductNameAttribute() {
        $product = Product::find($this->product);
        return $product->name;
    }
}
