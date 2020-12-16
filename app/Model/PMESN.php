<?php

namespace App\Model;

use App\Lib\Helper;
use Illuminate\Database\Eloquent\Model;

class PMESN extends Model
{
    protected $table = 'pm_esn';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'esn';

    public $incrementing = false;

    public function getProductAttribute() {
        return '';
    }

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

    public static function get_esn_type($esn) {
        if (empty($esn)) {
            return 'X';
        }

        $esn_obj = PMESN::find($esn);
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

    public static function get_override_amt($account_type, $esn) {

        if (empty($esn)) {
            return null;
        }

        $esn_obj = PMESN::find($esn);
        if (empty($esn_obj)) {
            return null;
        }

        $rebate_month_array = explode("|", $esn_obj->rebate_month);
        if (!in_array(1, $rebate_month_array)) {
            Helper::log("### Rebate Month ###", $rebate_month_array);
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

    public static function get_rebate_month($esn) {
        if (empty($esn)) {
            return '1|2|3';
        }

        $esn_obj = PMESN::find($esn);
        if (empty($esn_obj)) {
            return '1|2|3';
        }

        return $esn_obj->rebate_month;
    }
}
