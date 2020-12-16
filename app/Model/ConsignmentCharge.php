<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ConsignmentCharge extends Model
{
    protected $table = 'consignment_charge';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getAccountNameAttribute() {
        $account = Account::find($this->attributes['account_id']);
        if (empty($account)) {
            return '';
        }

        return $account->name;
    }
}
