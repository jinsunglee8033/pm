<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VWPayment extends Model
{
    protected $table = 'vw_payment';

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
            case 'B':
                return 'Weekly Billing';
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
                return 'Weekday ACH';
            case 'B':
                return 'Weekly Bill';
            case 'H':
                return 'Cash Pickup';
            default:
                return $this->attributes['method'];
        }
    }

    public $appends = ['type_name', 'method_name'];
}
