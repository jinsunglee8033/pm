<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountStoreType extends Model
{
    protected $table = 'account_store_type';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'account_id,store_type_id';

    public $incrementing = false;
}
