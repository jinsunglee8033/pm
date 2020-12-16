<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VRProduct2 extends Model
{
    protected $table = 'vr_product_2';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
