<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RTRPayment extends Model
{
    protected $table = 'cstore_rtr_payment';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
