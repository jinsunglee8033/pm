<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DealerCode extends Model
{
    protected $table = 'dealer_code';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'dealer_code';

    public $incrementing = false;
}
