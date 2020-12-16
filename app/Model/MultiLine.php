<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class MultiLine extends Model
{
    protected $table = 'h2o_multi_line';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
