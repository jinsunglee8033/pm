<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StoreType extends Model
{
    protected $table = 'store_type';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
