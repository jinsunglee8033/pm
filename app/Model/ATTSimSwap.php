<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ATTSimSwap extends Model
{
    protected $table = 'att_simswap';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
