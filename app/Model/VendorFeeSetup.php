<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VendorFeeSetup extends Model
{
    protected $table = 'vendor_fee_setup';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
