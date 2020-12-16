<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payment';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getTypeNameAttribute() {
        switch ($this->attributes['type']) {
            case 'P':
                return 'Prepay';
            case 'A':
                return 'Post Pay';
            case 'W':
                return 'Weekday ACH';
            default:
                return $this->attributes['type'];
        }
    }

    public function getMethodNameAttribute() {
        switch ($this->attributes['method']) {
            case 'P':
                return 'PayPal';
            case 'D':
                return 'Direct Deposit';
            case 'C':
                return 'Credit Card';
            case 'A':
                return 'ACH';
            case 'H':
                return 'Cash Pickup';
            default:
                return $this->attributes['method'];
        }
    }

    public $appends = ['type_name', 'method_name'];
}
