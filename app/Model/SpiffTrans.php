<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SpiffTrans extends Model
{
    protected $table = 'spiff_trans';

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

    public function getProductAttribute() {
        $product = Product::find($this->attributes['product_id']);
        if (empty($product)) {
            return '';
        }

        return $product->name;
    }

    public function getSpiffAccountTypeAttribute() {
        return $this->attributes['account_type'];
    }

    public static function getSpiffTotal($account_id) {
        $total_spiff = SpiffTrans::where('account_id', $account_id)
            ->where('cdate', '>=', Carbon::today()->addDays(-30)->format('Y-m-d') . ' 00:00:00')
            ->where('cdate', '<', Carbon::today()->format('Y-m-d') . ' 23:59:59')
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));

        if (empty($total_spiff)) {
            return 0;
        }

        return $total_spiff;
    }
}
