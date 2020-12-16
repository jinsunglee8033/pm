<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountStoreHours extends Model
{
    protected $table = 'account_store_hours';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
