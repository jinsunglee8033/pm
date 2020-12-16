<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CommissionUploadTemp extends Model
{
    protected $table = 'commission_upload_temp';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
