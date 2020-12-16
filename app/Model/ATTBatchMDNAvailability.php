<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 10/18/18
 * Time: 11:02 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ATTBatchMDNAvailability extends Model
{
    protected $table = 'att_batch_mdn_availability';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function create_availability($account_id, $mdn, $sdate, $month) {
        $ava = ATTBatchMDNAvailability::where('mdn', $mdn)->first();

        $edate = $sdate->addDays($month * 30);

        if (empty($ava)) {
            $ava = new ATTBatchMDNAvailability();
            $ava->account_id = $account_id;
            $ava->mdn       = $mdn;
            $ava->sdate     = $sdate;
            $ava->edate     = $edate;
            $ava->save();
        } else {
            if ($ava->sdate > $sdate) {
                $ava->sdate     = $sdate;
            }

            if ($ava->edate < $edate) {
                $ava->edate     = $edate;
            }
            $ava->update();
        }
    }
}
