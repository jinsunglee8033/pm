<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VerizonChargeback extends Model
{
    protected $table = 'verizon_chargeback';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'account_number';

    public $incrementing = false;
}
