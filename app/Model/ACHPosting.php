<?php

namespace App\Model;

use App\Lib\PaymentProcessor;
use Illuminate\Database\Eloquent\Model;

class ACHPosting extends Model
{
    protected $table = 'ach_posting';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getTypeNameAttribute() {
        switch ($this->attributes['type']) {
            case 'B':
                return 'Billing';
            case 'P':
                return 'Prepay - Cash';
            case 'W':
                return 'Weekday ACH';
            default:
                return $this->attributes['type'];
        }
    }

    public function getAccountAttribute() {
        $account = Account::find($this->attributes['account_id']);
        return $account;
    }

    public function getBounceMsgAttribute() {
        $code = ACHCode::find($this->attributes['bounce_code']);
        if (empty($code)) {
            return '';
        }

        return $code->msg;
    }

    public function getBounceFeeAttribute() {
        return PaymentProcessor::$bounce_fee;
    }
}
