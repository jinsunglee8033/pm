<?php
/**
 * Created by PhpStorm.
 * User: Jin
 * Date: 2/25/20
 * Time: 9:47 AM
 */

namespace App\Model;

use App\Lib\Helper;
use Illuminate\Database\Eloquent\Model;

class AccountFee extends Model
{
    protected $table = 'account_fee';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function get_total_fee($product_id, $account_id, $include_d_fee = 'Y') {

        Helper::log('### get_total_fee() ###', [
            'req' => $product_id,
            'res' => $account_id
        ]);

        $account = Account::find($account_id);
        if (empty($account)) return 0;

        // Sub //
        $af = AccountFee::where('account_id', $account_id)->where('prod_id', $product_id)->first();

        if(empty($af)) {
            Helper::log('### call_recur() ###', [
                'refeat' => $account->parent_id
            ]);

            if($account_id != 100000) {
                return self::get_total_fee($product_id, $account->parent_id, $include_d_fee);
            }else{
                return 0;
            }
        }else {

            Helper::log('### value() ###', [
                'value' => $af->r_fee + $af->m_fee + $af->d_fee + $af->s_fee
            ]);

            if($include_d_fee == 'Y'){
                return $af->r_fee + $af->m_fee + $af->d_fee + $af->s_fee;
            }else{
                return $af->r_fee + $af->m_fee + $af->s_fee;
            }

        }

    }

    public static function get_each_fee($product_id, $account_id) {

        $account = Account::find($account_id);
        if (empty($account)) return 0;

        // Sub //
        $af = AccountFee::where('account_id', $account_id)->where('prod_id', $product_id)->first();

        if(empty($af)) {
            Helper::log('### call_recur() ###', [
                'refeat' => $account->parent_id
            ]);
            if($account_id != 100000) {
                return self::get_each_fee($product_id, $account->parent_id);
            }else{
                return [
                    'r_fee' => 0,
                    'm_fee' => 0,
                    'd_fee' => 0,
                    's_fee' => 0
                    ];
            }
        }else {

            Helper::log('### value() ###', [
                'value' => $af->r_fee + $af->m_fee + $af->d_fee + $af->s_fee
            ]);

            return [
                'r_fee' => $af->r_fee ,
                'm_fee' => $af->m_fee ,
                'd_fee' => $af->d_fee ,
                's_fee' => $af->s_fee
                ];
        }
    }

    public static function pay_acct_fee($product_id, $account_id, $trans_id, $month) {

        $array_fee = self::get_each_fee($product_id, $account_id);
        $account = Account::find($account_id);

        $s_fee = $array_fee['s_fee'] * $month;
        $d_fee = $array_fee['d_fee'] * $month;
        $m_fee = $array_fee['m_fee'] * $month;
        $r_fee = $array_fee['r_fee'] * $month;

        if($s_fee > 0) {
            Promotion::create('C', 5, $account_id, $s_fee, $trans_id, 'Order:' . $trans_id);
        }
        if($account->parent_id == $account->master_id){
            if ($m_fee > 0) {
                Promotion::create('C', 5, $account->master_id, $m_fee, $trans_id, 'Order:' . $trans_id);
            }
        }else{
            if ($m_fee > 0) {
                Promotion::create('C', 5, $account->master_id, $m_fee, $trans_id, 'Order:' . $trans_id);
            }
            if ($d_fee > 0) {
                Promotion::create('C', 5, $account->parent_id, $d_fee, $trans_id, 'Order:' . $trans_id);
            }
        }
        if($r_fee > 0 ){
            Promotion::create('C', 5, 100000, $r_fee, $trans_id, 'Order:' . $trans_id);
        }
    }

}
