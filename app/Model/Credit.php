<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Credit extends Model
{
    protected $table = 'credit';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getTypeNameAttribute() {
        $type = $this->attributes['type'];
        switch ($type) {
            case 'C':
                return 'Credit';
            case 'D':
                return 'Debit';
            default:
                return $type;
        }
    }

    public function getAccountTypeAttribute() {
        $account_id = $this->attributes['account_id'];
        $account = Account::find($account_id);
        if (empty($account)) {
            return '';
        }

        return $account->type;
    }

    public function getAccountNameAttribute() {
        $account_id = $this->attributes['account_id'];
        $account = Account::find($account_id);
        if (empty($account)) {
            return '';
        }

        return $account->name;
    }

    protected $appends = ['type_name'];
}
