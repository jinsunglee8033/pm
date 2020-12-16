<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ConsignmentVendor extends Model
{
    protected $table = 'consignment_vendor';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getTypeNameAttribute() {
        $type = $this->attributes['type'];
        switch ($type) {
            case 'C':
                return 'Add';
            case 'D':
                return 'Reduce';
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

    public static function get_balance($account_id) {
        $cb = ConsignmentVendor::where('account_id', $account_id)->where('type', 'C')->sum('amt');
        $db = ConsignmentVendor::where('account_id', $account_id)->where('type', 'D')->sum('amt');

        return $cb - $db;
    }

    protected $appends = ['type_name'];
}
