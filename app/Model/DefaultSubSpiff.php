<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DefaultSubSpiff extends Model
{
    protected $table = 'default_sub_spiff';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
