<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ROKSimSwap extends Model
{
    protected $table = 'rok_simswap';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
