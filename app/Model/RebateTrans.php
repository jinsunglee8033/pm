<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RebateTrans extends Model
{
    protected $table = 'rebate_trans';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getAccountTypeAttribute() {
        $account = Account::find($this->attributes['account_id']);
        if (empty($account)) {
            return '';
        }

        return $account->type;
    }

    public function getAccountNameAttribute() {
        $account = Account::find($this->attributes['account_id']);
        if (empty($account)) {
            return '';
        }

        return $account->name;
    }

    public function getTypeNameAttribute() {
        switch ($this->attributes['type']) {
            case 'S':
                return 'Sales';
            case 'V':
                return 'Void';
            default:
                return $this->attributes['type'];
        }
    }

    public function getRebateTypeNameAttribute() {
        switch ($this->attributes['rebate_type']) {
            case 'B':
                return 'BYOD';
            case 'R':
                return 'Regular';
            default:
                return $this->attributes['rebate_type'];
        }
    }

    public function getProductAttribute() {
        $product = Product::find($this->attributes['product_id']);
        if (empty($product)) {
            return '';
        }

        return $product->name;
    }

    public function getRebateAccountTypeAttribute() {
        return $this->attributes['account_type'];
    }
}
