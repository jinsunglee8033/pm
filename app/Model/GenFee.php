<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 3/6/19
 * Time: 9:47 AM
 */

namespace App\Model;

use App\Lib\Helper;
use Illuminate\Database\Eloquent\Model;

class GenFee extends Model
{
    protected $table = 'gen_fee';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function get_fee($account, $fee_type, $account_type = null) {
        if (empty($account)) return 0;
        if (empty($account_type)) $account_type = $account->type;

        $gf = GenFee::where('account_id', $account->id)->where('account_type', $account_type)->where('fee_type', $fee_type)->first();

        if (empty($gf)) {
            $p_account = Account::find($account->parent_id);
            return self::get_fee($p_account, $fee_type, $account_type);
        }

        Helper::log('##### FEE AMOUNT ####', $gf);

        return $gf->fee_amount;
    }

    public static function get_total_fee($account_id, $fee_type, $fee_total = 0) {
        $account = Account::find($account_id);
        if (empty($account)) return $fee_total;

        $fee_amount = self::get_fee($account, $fee_type, $account->type);

        $p_account = Account::find($account->parent_id);
        $p_fee_amount = self::get_fee($account, $fee_type, $p_account->type);
        $fee_amount += $p_fee_amount;

        if ($p_account->type == 'D') {
            $m_fee_amount = self::get_fee($account, $fee_type, 'M');
            $fee_amount += $m_fee_amount;
        }

        $r_fee_amount = self::get_fee($account, $fee_type, 'L');
        $fee_amount += $r_fee_amount;
        
        return $fee_amount;
    }

    public static function pay_fee($account_id, $fee_type, $trans_id, $sub_agent_account) {
        $account = Account::find($account_id);
        if (empty($account)) return;

        $fee_amount = self::get_fee($sub_agent_account, $fee_type, $account->type);
        $category_id = $fee_type == 'A' ? 6 : 5;

        Promotion::create('C', $category_id, $account->id, $fee_amount, 'Order:' . $trans_id);

        if ($account_id == 100000) {
            return;
        }

        return self::pay_fee($account->parent_id, $fee_type, $trans_id, $sub_agent_account);
    }

    public static function default_fee($account_id, $fee_type, $account_type = null) {
        $account = Account::find($account_id);

        if (empty($account)) return 0;
        if (empty($account_type)) $account_type = $account->type;

        $p_account = Account::find($account->parent_id);
        return self::get_fee($p_account, $fee_type, $account_type);
    }
}
