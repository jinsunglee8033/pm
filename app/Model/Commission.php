<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    protected $table = 'commission';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
