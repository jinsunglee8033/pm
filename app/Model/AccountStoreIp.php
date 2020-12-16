<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountStoreIp extends Model
{
    protected $table = 'account_store_ip';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
