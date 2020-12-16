<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ChangePlan extends Model
{
    protected $table = 'change_plan';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
