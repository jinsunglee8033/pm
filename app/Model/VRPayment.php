<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VRPayment extends Model
{
    protected $table = 'vr_payment';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = true;
}
