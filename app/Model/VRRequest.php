<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;

class VRRequest extends Model
{
    protected $table = 'vr_request';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = true;


    public static function get_status_name($status) {

        switch ($status) {
            case 'CT':
                $html = 'In Cart';
                break;
            case 'RQ':
                $html = 'Requested';
                break;
            case 'CP':
                $html = 'Confirmed Price';
                break;
            case 'PC':
                $html = 'Paid';
                break;
            case 'CO':
                $html = 'COD collected';
                break;
            case 'SH':
                $html = 'Shipped';
                break;
            case 'C':
                $html = 'Completed';
                break;
            case 'R':
                $html = 'Rejected';
                break;
            case 'CC':
                $html = 'Canceled';
                break;
            default:
                $html = $status;
                break;
        }

        return $html;
    }

    public function getCategoryNameAttribute() {
        switch ($this->attributes['category']) {
            case 'O':
                return 'Order';
            case 'C':
                return 'General Request';
        }

        return $this->attributes['category'];
    }

    public function status_name() {

        switch ($this->attributes['status']) {
            case 'CT':
                $html = 'In Cart';
                break;
            case 'RQ':
                $html = 'Requested';
                break;
            case 'CP':
                $html = 'Confirmed Price';
                break;
            case 'PC':
                $html = 'Paid';
                break;
            case 'CO':
                $html = '<span style="color:green;font-weight:bold;">COD collected</span>';
                break;
            case 'SH':
                $html = 'Shipped';
                break;
            case 'C':
                $html = '<span style="color:green;font-weight:bold;">Completed</span>';
                break;
            case 'R':
                $html = 'Rejected';
                break;
            case 'CC':
                $html = '<span style="color:red;font-weight:bold;">Canceled</span>';
                break;
            default:
                $html = $this->attributes['status'];
                break;
        }

        return $html;
    }

    public function getLastModifiedAttribute() {
        if (!empty($this->attributes['mdate'])) {
            return $this->attributes['mdate'] . ' (' . $this->attributes['modified_by'] . ')';
        }

        return $this->attributes['cdate'] . ' (' . $this->attributes['created_by'] . ')';
    }

    public function getAccountPhoneAttribute() {

        $account = Account::find($this->attributes['account_id']);
        if (empty($account)) {
            return '';
        }

        return $account->office_number;
    }

    public function getAccountEmailAttribute() {
        
        $account = Account::find($this->attributes['account_id']);
        if (empty($account)) {
            return '';
        }

        return $account->email;
    }
}
