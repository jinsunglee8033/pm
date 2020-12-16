<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RTRQueue extends Model
{
    protected $table = 'rtr_queue';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getProductAttribute() {

        // For WVZR (Verizon open plan)
        if($this->attributes['vendor_code'] == 'EMD' && $this->attributes['vendor_pid'] == '8810500'){
            $this->attributes['amt'] = 0;
        }

        $tran = Transaction::find($this->attributes['trans_id']);
        if (empty($tran)) {
            return '';
        }

        $product = Product::where('id', $tran->product_id)->first();
        if (empty($product)) {
            return '';
        }

        return $product->name;
    }

    public function getSimAttribute() {
        $trans = Transaction::find($this->attributes['trans_id']);
        if (empty($trans)) {
            return '';
        }

        return $trans->sim;
    }

    public function getCarrierAttribute() {

        $tran = Transaction::find($this->attributes['trans_id']);
        if (empty($tran)) {
            return '';
        }

        $product = Product::where('id', $tran->product_id)->first();
        if (empty($product)) {
            return '';
        }

        return $product->carrier;
    }

    public function getSimTypeAttribute() {
        $trans = Transaction::find($this->attributes['trans_id']);
        if (empty($trans)) {
            return '';
        }

        $sim_obj = StockSim::where('sim_serial', $trans->sim)->where('product', $trans->product_id)->first();

        if (empty($sim_obj)) {
            return '';
        }

        return $sim_obj->type_name;
    }

    public function getResultNameAttribute() {
        switch ($this->attributes['result']) {
            case 'N':
                return 'Waiting';
            case 'S':
                return 'Success';
            case 'F':
                return 'Failed';
            case 'P':
                return 'Processing';
            case 'C':
                return 'Canceled';
            default:
                return $this->attributes['result'];
        }
    }

    public function getAccountIdAttribute() {
        $trans = Transaction::find($this->attributes['trans_id']);
        if (empty($trans)) {
            return '';
        }

        return $trans->account_id;
    }

    public function getAccountTypeAttribute() {
        $trans = Transaction::find($this->attributes['trans_id']);
        if (empty($trans)) {
            return '';
        }

        $account = Account::find($trans->account_id);
        if (empty($account)) {
            return '';
        }

        return $account->type;
    }

    public function getAccountNameAttribute() {
        $trans = Transaction::find($this->attributes['trans_id']);
        if (empty($trans)) {
            return '';
        }

        $account = Account::find($trans->account_id);
        if (empty($account)) {
            return '';
        }

        return $account->name;
    }
}
