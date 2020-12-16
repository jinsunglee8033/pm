<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountShipFee extends Model
{
    protected $table = 'account_ship_fee';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
