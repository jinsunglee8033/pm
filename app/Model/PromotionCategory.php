<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PromotionCategory extends Model
{
    protected $table = 'promotion_category';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
