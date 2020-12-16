<?php

namespace App\Model;

use function Couchbase\defaultDecoder;
use Illuminate\Database\Eloquent\Model;
use Auth;

class Transaction extends Model
{
    protected $table = 'transaction';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

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

    public function carrier() {
        $product = Product::find($this->product_id);
        if (empty($product)) {
            return null;
        }

        return $product->carrier;
    }

    public function product_name() {
        $product = Product::find($this->product_id);
        if (empty($product)) {
            return null;
        }

        return $product->name;
    }

    public function product() {
        $product = Product::find($this->product_id);
        if (empty($product)) {
            return null;
        }
        return $product;
    }

    public function status_name() {
        $type = $this->attributes['type'];
        switch ($this->status) {
            case 'I':
                return 'Initiating';
            case 'N':
                return 'New';
            case 'P':
                return 'Processing';
            case 'C':
                if ($type == 'S') {
                    return '<span style="color:green; font-weight:bold;">Completed</span>';
                }

                return 'Completed';
            case 'R':
                return '<span style="background-color: red; color:white; font-weight:bold;padding:2px 4px;">Action Required</span>';
            case 'F':
                return '<span style="color:red; font-weight:bold;">Failed</span>';
            case 'Q':
                return 'Port-In Requested';
            case 'V':
                return '<span style="color:red; font-weight:bold;">Voided</span>';
        }
    }

    public function getLastUpdatedAttribute() {
        return empty($this->attributes['mdate']) ? $this->attributes['cdate'] : $this->attributes['mdate'];
    }

    public function getSimTypeNameAttribute() {
        $sim_obj = StockSim::where('sim_serial', $this->attributes['sim'])->where('product', $this->attributes['product_id'])->first();
        if (!empty($sim_obj)) {
            return $sim_obj->type_name;
        } else {
            return $this->carrier();
        }
    }

    public function getSeqAttribute() {
        $q = RTRQueue::where('trans_id', $this->attributes['id'])->first();
        if (empty($q)) {
            return '';
        }

        return $q->seq;
    }

    public function getSpiffAttribute() {
        $spiff = SpiffTrans::where('trans_id', $this->attributes['id'])
            ->sum(DB::raw("if(type = 'S', spiff_amt, -spiff_amt)"));
        return $spiff;
    }

    public function getCollectionAmtAttribute() {
        $collection_amt = $this->attributes['collection_amt'];
        if (empty($collection_amt)) {
            $collection_amt = 0;
        }

        return number_format($collection_amt, 2);
    }

    public static function create_batch($account_id, $action, $mdn, $sim, $plan, $pm_fee) {
        try{
            $trans = new Transaction;
            $trans->account_id  = $account_id;
            $trans->product_id  = 'WATTBATCH';
            $trans->action      = $action;
            $trans->denom       = $plan;
            $trans->sim         = $sim;
            $trans->phone       = $mdn;
            $trans->esn         = '';
            $trans->zip         = '';
            $trans->npa         = '';
            $trans->created_by  = 'admin';
            $trans->cdate       = \Carbon\Carbon::now();
            $trans->status      = 'S';
            $trans->dc          = '';
            $trans->dp          = '';
            $trans->phone_type  = '';
            $trans->collection_amt = 0;
            $trans->rtr_month   = 1;
            $trans->fee         = 0;
            $trans->pm_fee      = $pm_fee;
            $trans->net_revenue = 0;
            $trans->vendor_code = 'ATT';
            $trans->save();

            return [
              'code' => '0',
              'msg'  => ''
            ];
        } catch (\Exception $ex) {
            $msg = ' - error : ' . $ex->getMessage() . ' [' . $ex->getCode() . '] : ' . $ex->getTraceAsString();
            return [
                'code' => '-9',
                'msg'  => $msg
            ];
        }
    }
}
