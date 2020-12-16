<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CommissionUpload extends Model
{
    protected $table = 'commission_upload';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
