<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Phones extends Model
{
    protected $table = 'phones';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
