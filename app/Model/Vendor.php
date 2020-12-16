<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $table = 'vendor';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'code';

    public $incrementing = false;
}
