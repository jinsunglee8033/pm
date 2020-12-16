<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockESN extends Model
{
    protected $table = 'stock_esn';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    // public $incrementing = false;

    public function getStatusNameAttribute() {
        switch ($this->attributes['status']) {
            case 'A':
                return 'Active';
            case 'H':
                return 'On-Hold';
            case 'S':
                return 'Suspended';
            case 'U':
                return 'Used';
        }

        return $this->attributes['status'];
    }

    public function getTypeNameAttribute() {
        switch ($this->attributes['type']) {
            case 'B':
                return 'Bundled';
            case 'P':
                return 'Wallet';
            case 'R':
                return 'Regular';
            case 'C':
                return 'Consignment';
        }

        return $this->attributes['type'];
    }

    public function getProductNameAttribute() {
        $product = Product::find($this->product);
        return $product->name;
    }

    public static function get_esn_type($esn, $product) {
        if (empty($esn)) {
            return 'X';
        }

        $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();
        if (empty($esn_obj)) {
            return 'B'; // BYOD
        }

        if ($esn_obj->no_rebate == 'Y') {
            return 'X';
        }

        if ($esn_obj->type == 'P') {
            return $esn_obj->type . $esn_obj->rtr_month;
        }

        return $esn_obj->type;
    }

    public static function get_override_amt($account_type, $esn, $product) {
        if (empty($esn)) {
            return null;
        }

        $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();
        if (empty($esn_obj)) {
            return null;
        }

        if (empty($esn_obj->rebate_month) || $esn_obj->rebate_month == 0) {
            return [
              'override_amt' => 0,
              'override_msg' => ''
            ];
        }

        $rebate_month_array = explode("|", $esn_obj->rebate_month);
        if (!in_array(1, $rebate_month_array)) {
            return [
                'override_amt' => 0,
                'override_msg' => ''
            ];
        }

        switch ($account_type) {
            case 'M':
                return [
                    'override_amt' => $esn_obj->rebate_override_m,
                    'override_msg' => ''
                ];
            case 'D':
                return [
                    'override_amt' => $esn_obj->rebate_override_d,
                    'override_msg' => ''
                ];
            case 'S':
                return [
                    'override_amt' => $esn_obj->rebate_override_r,
                    'override_msg' => ''
                ];
        }

        return null;
    }

    public static function get_rebate_month($esn, $product) {
        if (empty($esn)) {
            return '1|2|3';
        }

        $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();
        if (empty($esn_obj)) {
            return '1|2|3';
        }

        return $esn_obj->rebate_month;
    }

    public static function upload_byod($esn, $product, $sub_carrier, $account_id='', $account_name='') {
        $esn_obj = new StockESN();
        $esn_obj->esn       = $esn;
        $esn_obj->product   = $product;
        $esn_obj->sub_carrier = $sub_carrier;
        $esn_obj->type      = 'R';
        $esn_obj->rtr_month = null;
        $esn_obj->spiff_month = '1|2|3';
        $esn_obj->status    = 'A';
        $esn_obj->is_byod   = 'Y';
        $esn_obj->buyer_name = $account_name . ' (' . $account_id . ')';

//        $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
//        if($account_id == $acct_id){
//            $esn_obj->c_store_id = $acct_id;
//        }
        $esn_obj->upload_date = \Carbon\Carbon::now();
        $esn_obj->save();

        return $esn_obj;
    }
}
