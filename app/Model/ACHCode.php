<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ACHCode extends Model
{
    protected $table = 'ach_code';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'code';

    public $incrementing = false;
}
