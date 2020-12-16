<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GSSProduct extends Model
{
    protected $table = 'gss_product';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function init_product() {
    	$ret = \App\Lib\gss::ProductInfo();
    	$products = $ret['products'];

        if (is_array($products) || is_object($products)) {

            GSSProduct::truncate();

            foreach ($products as $r) {
                foreach ($r->Product as $p) {
                    foreach ($p->Denom as $d) {
                        $product = new GSSProduct();
                        $product->name = $p->Name;
                        $product->type = $r->Name;
                        $product->denom_name = $d->Name;
                        $product->denom_retail = $d->Retail;
                        $product->denom_alpha = $d->Alpha;
                        $product->denom_numeric = $d->Numeric;
                        $product->denom_country = $d->Country;
                        $product->cdate = \Carbon\Carbon::now();
                        $product->save();
                    }
                }
            }
        }

    }
}
