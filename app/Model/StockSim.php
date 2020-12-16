<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

use App\Lib\Helper;

class StockSim extends Model
{
    protected $table = 'stock_sim';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;

//    public function getStatusNameAttribute() {
//        switch ($this->attributes['status']) {
//            case 'A':
//                return 'Active';
//            case 'H':
//                return 'On-Hold';
//            case 'S':
//                return 'Suspended';
//            case 'U':
//                return 'Used';
//        }
//
//        return $this->attributes['status'];
//    }

//    public function getTypeNameAttribute() {
//        switch ($this->attributes['type']) {
//            case 'B':
//                return 'Bundled';
//            case 'P':
//                return 'Wallet';
//            case 'R':
//                return 'Regular';
//            case 'C':
//                return 'Consignment';
//        }
//
//        return $this->attributes['type'];
//    }

//    public function getProductNameAttribute() {
//        $product = Product::find($this->product);
//        return $product->name;
//    }


    public static function get_spiff_month($esn, $sim, $product, $sim_group=null) {
        $esn = trim($esn);
        $sim = trim($sim);

        if (empty($sim)) {
            if (empty($esn)) {
                return '0';
            }

            $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();

            if (empty($esn_obj)) {
                return '1|2|3'; // BYOD => R
            }

            return $esn_obj->spiff_month;

        } else {

            if(!empty($sim_group)) {
                $sim_obj = StockSim::where('sim_serial', $sim)->where('sim_group', $sim_group)->first();
            }else{
                $sim_obj = StockSim::where('sim_serial', $sim)->where('product', $product)->first();
            }

            if (empty($sim_obj)) {
                return '1|2|3'; // BYOD => R
            }

            return $sim_obj->spiff_month;
        }
    }

    public static function get_sim_charge_amt($esn_obj, $sim_obj, $account_type) {

        $charge_sim = 0;
        $charge_esn = 0;

        if (!empty($sim_obj)) {
            switch ($account_type) {
                case 'S':
                    $charge_sim = empty($sim_obj->charge_amount_r) ? 0 : $sim_obj->charge_amount_r;
                    break;
                case 'D':
                    $charge_sim = empty($sim_obj->charge_amount_d) ? 0 : $sim_obj->charge_amount_d;
                    break;
                case 'M':
                    $charge_sim = empty($sim_obj->charge_amount_m) ? 0 : $sim_obj->charge_amount_m;
                    break;
            }
        }

        if (!empty($esn_obj)) {
            switch ($account_type) {
                case 'S':
                    $charge_esn = empty($esn_obj->charge_amount_r) ? 0 : $esn_obj->charge_amount_r;
                    break;
                case 'D':
                    $charge_esn = empty($esn_obj->charge_amount_d) ? 0 : $esn_obj->charge_amount_d;
                    break;
                case 'M':
                    $charge_esn = empty($esn_obj->charge_amount_m) ? 0 : $esn_obj->charge_amount_m;
                    break;
            }
        }

        return $charge_sim + $charge_esn;

    }

    public static function get_sim_type($esn, $sim, $product) {

        $esn = trim($esn);
        $sim = trim($sim);

        if (empty($sim)) {

            if (empty($esn)) {
                return 'X';
            }

            $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();

            if (empty($esn_obj)) {
                return 'R'; // BYOD => R
            }

            return $esn_obj->type;

        } else {

            $sim_obj = StockSim::where('sim_serial', $sim)->where('product', $product)->first();

            if (empty($sim_obj)) {
                return 'R'; // BYOD => R
            }

            return $sim_obj->type;

        }

    }

    public static function get_override_amt($account_type, $sim, $esn, $month, $product, $sim_group = null) {

        Helper::log('### get_override_amt ###', [
            'account_type' => $account_type,
            'sim' => $sim,
            'esn' => $esn,
            'month' => $month
        ]);

        if (empty($sim)) {
            $esn_obj = StockESN::where('esn', $esn)->where('product', $product)->first();

            if (empty($esn_obj)) {
                return null;
            }

            $spiff_month_array = explode("|", $esn_obj->spiff_month);
            if (!in_array($month, $spiff_month_array)) {
                return 0;
            }

            switch ($account_type) {
                case 'M':
                    return $esn_obj->spiff_override_m;
                case 'D':
                    return $esn_obj->spiff_override_d;
                case 'S':
                    return $esn_obj->spiff_override_r;
            }

        } else {

            if(!empty($sim_group)) {
                $sim_obj = StockSim::where('sim_serial', $sim)->where('sim_group', $sim_group)->first();
            }else{
                $sim_obj = StockSim::where('sim_serial', $sim)->where('product', $product)->first();
            }

            if (empty($sim_obj)) {
                return null;
            }

            if (empty($sim_obj->spiff_month) || $sim_obj->spiff_month == 0) {
                return 0;
            }

            $spiff_month_array = explode("|", $sim_obj->spiff_month);
            if (!in_array($month, $spiff_month_array)) {
                return 0;
            }

            switch ($account_type) {
                case 'M':
                    return $sim_obj->spiff_override_m;
                case 'D':
                    return $sim_obj->spiff_override_d;
                case 'S':
                    return $sim_obj->spiff_override_r;
            }

        }

        return null;
    }

    public static function get_spiff_2_amt($account_type, $sim_obj) {

        if (empty($sim_obj)) return 0;

        $amt = 0;

        switch ($account_type) {
            case 'M':
                if (!empty($sim_obj->spiff_override_m)) return 0;
                $amt = $sim_obj->spiff_2_m;
                break;
            case 'D':
                if (!empty($sim_obj->spiff_override_d)) return 0;
                $amt = $sim_obj->spiff_2_d;
                break;
            case 'S':
                if (!empty($sim_obj->spiff_override_r)) return 0;
                $amt = $sim_obj->spiff_2_r;
                break;
        }

        $amt = empty($amt) ? 0 : $amt;

        return $amt;
    }

    public static function upload_byos($sim, $afcode, $product, $sub_carrier, $sim_group='', $account_id='', $account_name='') {
        $sim_obj = new StockSim();
        $sim_obj->sim_serial = $sim;
        $sim_obj->afcode = $afcode;
        $sim_obj->product = $product;
        $sim_obj->sub_carrier = $sub_carrier;
        $sim_obj->sim_group = $sim_group;
        $sim_obj->type = 'R';
        $sim_obj->rtr_month = null;
        $sim_obj->spiff_month = '1|2|3';
        $sim_obj->status = 'A';
        $sim_obj->is_byos = 'Y';
        $sim_obj->buyer_name = $account_name.' ('. $account_id .')';

//        $acct_id = getenv('APP_ENV') == 'production' ? 100573 : 100139;
//        if($account_id == $acct_id){
//            $sim_obj->c_store_id = $acct_id;
//        }

        $sim_obj->upload_date = \Carbon\Carbon::now();
        $sim_obj->save();

        return $sim_obj;
    }
}
