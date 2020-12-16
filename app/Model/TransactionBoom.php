<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TransactionBoom extends Model
{
    protected $table = 'transaction_boom';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
