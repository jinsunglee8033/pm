<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ReupRTR extends Model
{
    protected $table = 'reup_rtr';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'rok_id';

    public $incrementing = false;

    public function getAccountTypeAttribute() {
        $account_id = $this->attributes['account_id'];
        $account = Account::find($account_id);
        if (!empty($account)) {
            return $account->type;
        }

        return '';
    }

    public function getAccountNameAttribute() {
        $account_id = $this->attributes['account_id'];
        $account = Account::find($account_id);
        if (!empty($account)) {
            return $account->name;
        }

        return '';
    }
}
