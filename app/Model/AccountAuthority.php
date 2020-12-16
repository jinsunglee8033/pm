<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountAuthority extends Model
{
    protected $table = 'account_authority';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
