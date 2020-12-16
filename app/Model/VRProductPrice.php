<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class VRProductPrice extends Model
{
    protected $table = 'vr_product_price';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public static function get_price_by_account($account_id, $vr_prod_id) {

        $account = Account::find($account_id);

        $acct_type = $account->type;

        if($acct_type == 'M'){
            $result = VRProductPrice::where('account_id', $account_id)->where('vr_prod_id', $vr_prod_id)->first();
        }elseif ($acct_type == 'D'){
            $result = VRProductPrice::where('account_id', $account_id)->where('vr_prod_id', $vr_prod_id)->first();
            if(is_null($result)) {
                $master_id = $account->parent_id;
                $result = VRProductPrice::where('account_id', $master_id)->where('vr_prod_id', $vr_prod_id)->first();
            }
        }elseif ($acct_type == 'S'){
            $result = VRProductPrice::where('account_id', $account_id)->where('vr_prod_id', $vr_prod_id)->first();
            if(is_null($result)){
                $parent_id = $account->parent_id;
                $master_id = $account->master_id;
                $result = VRProductPrice::where('account_id', $parent_id)->where('vr_prod_id', $vr_prod_id)->first();
                if(is_null($result)){
                    if($parent_id != $master_id){
                        $result = VRProductPrice::where('account_id', $master_id)->where('vr_prod_id', $vr_prod_id)->first();
                    }
                }
            }
        }

        if (is_null($result)){
            $result = '';
        }

        return $result;
    }

    public static function get_buy_num_of_month($account_id, $vr_prod_id){

        $num_of_month = DB::select("
                    select sum(vrp.qty) as qty_sum 
                      from vr_request vr 
                           inner join vr_request_product vrp on vrp.vr_id = vr.id
                     where vr.account_id = :account_id
                       and vrp.prod_id = :prod_id
                       and vr.status not in ('R', 'CC', 'CT')
                       and (vr.cdate > last_day(now() - interval 1 month) + interval 1 day ) ;
                ", [
            'account_id'    => $account_id,
            'prod_id'       => $vr_prod_id
        ]);

        return !empty($num_of_month[0]->qty_sum) ? $num_of_month[0]->qty_sum : '0';
    }
}
