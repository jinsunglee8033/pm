<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountUserIp extends Model
{
    protected $table = 'account_user_ip';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
