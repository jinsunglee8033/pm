<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NewsAccountType extends Model
{
    protected $table = 'news_account_type';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'news_id,account_type';

    public $incrementing = false;
}
