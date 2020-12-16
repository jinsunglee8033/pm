<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Residual extends Model
{
    protected $table = 'residual';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
