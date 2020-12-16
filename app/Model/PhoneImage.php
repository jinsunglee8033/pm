<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PhoneImage extends Model
{
    protected $table = 'phone_image';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
