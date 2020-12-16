<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Model\VRProduct;
use DB;

class VRRequestProduct extends Model
{
    protected $table = 'vr_request_product';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public function getSalesTypeAttribute() {
        switch ($this->attributes['sales_type']) {
            case 'S':
                return 'Sales';
            case 'V':
                return 'Void';
        }

        return $this->attributes['sales_type'];
    }

    public static function get_buy_num($account_id, $vr_prod_id){

        $num_of_month = DB::select("
                    select sum(vrp.qty) as qty_sum 
                      from vr_request vr 
                           inner join vr_request_product vrp on vrp.vr_id = vr.id
                     where vr.account_id = :account_id
                       and vrp.prod_id = :prod_id
                       and vr.status not in ('R', 'CC', 'CT') ;
                ", [
            'account_id'    => $account_id,
            'prod_id'       => $vr_prod_id
        ]);

        return !empty($num_of_month[0]->qty_sum) ? $num_of_month[0]->qty_sum : '0';
    }
}
