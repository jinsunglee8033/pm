<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockPinTransaction extends Model
{
    protected $table = 'stock_pin_transaction';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

}
