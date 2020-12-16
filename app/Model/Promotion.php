<?php

namespace App\Model;

use App\Lib\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Promotion extends Model
{
    protected $table = 'promotion';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public function getTypeNameAttribute() {
        switch ($this->attributes['type']) {
            case 'C':
                return 'Credit';
            case 'D':
                return 'Debit';
            default:
                return $this->attributes['type'];
        }
    }

    public function getCategoryNameAttribute() {
        $category = PromotionCategory::find($this->attributes['category_id']);
        if (empty($category)) {
            return '';
        }

        return $category->name;
    }

    public static function create($type, $category_id, $account_id, $amount, $trans_id, $notes = '') {
        try {
            if ($amount != 0) {
                $promotion = new Promotion();
                $promotion->type = $type;
                $promotion->category_id = $category_id;
                $promotion->account_id  = $account_id;
                $promotion->trans_id    = $trans_id;
                $promotion->amount = $amount;
                $promotion->notes = $notes;
                $promotion->cdate = \Carbon\Carbon::now();
                $promotion->created_by = 'system';
                $promotion->save();
            }

        } catch (\Exception $ex) {  

            \App\Lib\Helper::log('### CREATE # EXCEPTION ###', [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ]); 
        }
    }

    public static function create_by_order($sim_obj, $account, $trans_id) {

        Helper::log('### create_by_order ###', [
            'sim_obj' => $sim_obj,
            'account' => $account
        ]);

        try {
            if (empty($sim_obj)) return;
            if (empty($account)) return;

            ### SIM Charge
            if (!empty($sim_obj->sim_charge)) {
                self::create('D', 2, $account->id, $sim_obj->sim_charge, $trans_id);

                $tran = Transaction::where('id', $trans_id)->first();
                $product_id = $tran->product_id;
                if($product_id == "WGENA" || $product_id == "WLBTA") {
                    StockSim::where('id', $sim_obj->id)->update([
                        'sim_charge' => NULL,
                        'comments' => DB::raw('CONCAT(COALESCE(comments, ""), " [SIM Charge $' . $sim_obj->sim_charge . ' removed by TX_ID : ' . $trans_id . ' ' . Carbon::now()->format('Y/m/d') . '] ")')
                    ]);
                }
            }

            ### SIM Rebate
            if (!empty($sim_obj->sim_rebate)) {
                self::create('C', 3, $account->id, $sim_obj->sim_rebate, $trans_id);

                $tran = Transaction::where('id', $trans_id)->first();
                $product_id = $tran->product_id;
                if($product_id == "WGENA" || $product_id == "WLBTA") {
                    StockSim::where('id', $sim_obj->id)->update([
                        'sim_rebate' => NULL,
                        'comments' => DB::raw('CONCAT(COALESCE(comments, ""), " [SIM Rebate $' . $sim_obj->sim_rebate . ' removed by TX_ID : ' . $trans_id . ' ' . Carbon::now()->format('Y/m/d') . '] ")')
                    ]);
                }
            }
        } catch (\Exception $ex) {  
            \App\Lib\Helper::log('### CREATE BY ORDER # EXCEPTION ###', [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ]);  
        }
    }

    public static function create_by_order_esn($esn_obj, $account, $trans_id) {

        Helper::log('### create_by_order_esn ###', [
            'esn_obj' => $esn_obj,
            'account' => $account
        ]);

        try {
            if (empty($esn_obj)) return;
            if (empty($account)) return;

            ### ESN Charge
            if (!empty($esn_obj->esn_charge)) {
                self::create('D', 12, $account->id, $esn_obj->esn_charge, $trans_id);

                StockESN::where('id', $esn_obj->id)->update([
                        'esn_charge' => NULL,
                        'comments' => DB::raw('CONCAT(COALESCE(comments, ""), " [ESN Charge $'. $esn_obj->esn_charge .' removed by TX_ID : '. $trans_id .' ' . Carbon::now()->format('Y/m/d') . '] ")')
                    ]);
            }

            ### ESN Rebate
            if (!empty($esn_obj->esn_rebate)) {
                self::create('C', 13, $account->id, $esn_obj->esn_rebate, $trans_id);

                StockESN::where('id', $esn_obj->id)
                    ->update([
                        'esn_rebate' => NULL,
                        'comments' => DB::raw('CONCAT(COALESCE(comments, ""), " [ESN Rebate $'. $esn_obj->esn_rebate .' removed by TX_ID : '. $trans_id .' ' . Carbon::now()->format('Y/m/d') . '] ")')
                    ]);
            }

        } catch (\Exception $ex) {
            \App\Lib\Helper::log('### CREATE BY ORDER # EXCEPTION ###', [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ]);
        }
    }

    public static function void_sim($trans_id) {
        try{

            ### make void sim charge ###
            $cnt = Promotion::where('trans_id', $trans_id)
                ->where('type', 'D')
                ->whereNull('void_date')
                ->count();

            if ($cnt < 1) {
                return [
                    'error_code' => '',
                    'error_msg' => ''
                ];
            }

            if ($cnt > 0) {

                $ret = DB::statement("
                insert into promotion (
                    type, category_id, account_id, trans_id, amount, notes, cdate, created_by, orig_promotion_id
                )
                select
                    'D' as type,
                    14 as category_id,
                    account_id,
                    trans_id,
                    amount,
                    'Void' as notes,
                    current_timestamp,
                    :user_id as created_by,
                    id
                from promotion
                where trans_id = :trans_id
                and type = 'C'
                and void_date is null
            ", [
                    'trans_id' => $trans_id,
                    'user_id' => Auth::user()->user_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add void sim charge promotion record');
                }

                $ret = DB::statement("
                update promotion
                set void_date = current_timestamp
                where trans_id = :trans_id
                and type = 'D'
                and notes != 'Void'
                and void_date is null
            ", [
                    'trans_id' => $trans_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add sim charge promotion record');
                }

            }

            ### make void sim rebate ###
            $cnt = Promotion::where('trans_id', $trans_id)
                ->where('type', 'C')
                ->whereNull('void_date')
                ->count();

            if ($cnt > 0) {

                $ret = DB::statement("
                    insert into promotion (
                        type, category_id, account_id, trans_id, amount, notes, cdate, created_by, orig_promotion_id
                    )
                    select
                        'D' as type, 
                        15 as category_id,
                        account_id,
                        trans_id,
                        amount,
                        'Void' as notes,
                        current_timestamp,
                        :user_id as created_by,
                        id
                    from promotion
                    where trans_id = :trans_id
                    and type = 'C'
                    and void_date is null
                ", [
                    'trans_id' => $trans_id,
                    'user_id' => Auth::user()->user_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add void sim rebate promotion record');
                }

                $ret = DB::statement("
                    update promotion
                    set void_date = current_timestamp
                    where trans_id = :trans_id
                    and type = 'C'
                    and notes != 'Void'
                    and void_date is null
                ", [
                    'trans_id' => $trans_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add sim rebate promotion record');
                }
            }

        } catch (\Exception $ex) {
            \App\Lib\Helper::log('### VOID SIM # EXCEPTION ###', [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ]);
        }
    }

    public static function void_esn($trans_id) {
        try{
            ### make void esn charge ###
            $cnt = Promotion::where('trans_id', $trans_id)
                ->where('type', 'D')
                ->whereNull('void_date')
                ->count();

            if ($cnt < 1) {
                return [
                    'error_code' => '',
                    'error_msg' => ''
                ];
            }

            if ($cnt > 0) {

                $ret = DB::statement("
                insert into promotion (
                    type, category_id, account_id, trans_id, amount, notes, cdate, created_by, orig_promotion_id
                )
                select
                    'D' as type,
                    16 as category_id,
                    account_id,
                    trans_id,
                    amount,
                    'Void' as notes,
                    current_timestamp,
                    :user_id as created_by,
                    id
                from promotion
                where trans_id = :trans_id
                and type = 'C'
                and void_date is null
            ", [
                    'trans_id' => $trans_id,
                    'user_id' => Auth::user()->user_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add void esn charge promotion record');
                }

                $ret = DB::statement("
                update promotion
                set void_date = current_timestamp
                where trans_id = :trans_id
                and type = 'D'
                and notes != 'Void'
                and void_date is null
            ", [
                    'trans_id' => $trans_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add esn charge promotion record');
                }

            }

            ### make void esn rebate ###
            $cnt = Promotion::where('trans_id', $trans_id)
                ->where('type', 'C')
                ->whereNull('void_date')
                ->count();

            if ($cnt > 0) {

                $ret = DB::statement("
                    insert into promotion (
                        type, category_id, account_id, trans_id, amount, notes, cdate, created_by, orig_promotion_id
                    )
                    select
                        'D' as type, 
                        17 as category_id,
                        account_id,
                        trans_id,
                        amount,
                        'Void' as notes,
                        current_timestamp,
                        :user_id as created_by,
                        id
                    from promotion
                    where trans_id = :trans_id
                    and type = 'C'
                    and void_date is null
                ", [
                    'trans_id' => $trans_id,
                    'user_id' => Auth::user()->user_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add void esn rebate promotion record');
                }

                $ret = DB::statement("
                    update promotion
                    set void_date = current_timestamp
                    where trans_id = :trans_id
                    and type = 'C'
                    and notes != 'Void'
                    and void_date is null
                ", [
                    'trans_id' => $trans_id
                ]);

                if ($ret < 1) {
                    throw new \Exception('Failed to add esn rebate promotion record');
                }
            }

        } catch (\Exception $ex) {
            \App\Lib\Helper::log('### VOID ESN # EXCEPTION ###', [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ]);
        }
    }

}
