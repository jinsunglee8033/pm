<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountUserHours extends Model
{
    protected $table = 'account_user_hours';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
