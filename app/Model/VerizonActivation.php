<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VerizonActivation extends Model
{
    protected $table = 'verizon_activation';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'account_number';

    public $incrementing = false;
}
