<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockPin extends Model
{
    protected $table = 'stock_pin';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function void_pin($trans_id) {

        try{

            ### make void sim charge ###
            $cnt = StockPin::where('used_trans_id', $trans_id)
                ->where('status', 'S')
                ->count();

            if ($cnt < 1) {
                return [
                    'error_code' => '',
                    'error_msg' => ''
                ];
            }

            $stock_pin = StockPin::where('used_trans_id', $trans_id)->where('status', 'S')->first();
            $stock_pin->status = 'V';
            $stock_pin->save();

        } catch (\Exception $ex) {
            \App\Lib\Helper::log('### VOID PIN # EXCEPTION ###', [
                'error_code' => $ex->getCode(),
                'error_msg' => $ex->getMessage(),
                'error_trace' => $ex->getTraceAsString()
            ]);
        }
    }
}
