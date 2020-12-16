<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NewsAccountId extends Model
{
    protected $table = 'news_account_id';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
