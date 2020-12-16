<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 10/29/18
 * Time: 9:43 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;
use App\Model\VRProduct;

class VWNonInstanceSpiff extends Model
{
    protected $table = 'vw_noninstance_spiff';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = true;

    public static function get_current($account_id) {
        $current = VWNonInstanceSpiff::where('account_id', $account_id)->orderBy('pdate', 'desc')->first();

        if (empty($current)) {
            return 0;
        }

        return $current->total_spiff;
    }
}
