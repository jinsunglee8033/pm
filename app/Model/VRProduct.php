<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VRProduct extends Model
{
    protected $table = 'vr_product';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

//    public $incrementing = false;
}
