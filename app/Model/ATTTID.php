<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ATTTID extends Model
{
    protected $table = 'att_tid';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
