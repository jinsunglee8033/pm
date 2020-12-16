<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ATTTIDLog extends Model
{
    protected $table = 'att_tid_log';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
