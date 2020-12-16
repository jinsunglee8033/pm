<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 10/18/18
 * Time: 2:19 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ATTBatchFeeBase extends Model
{
    protected $table = 'att_batch_fee_base';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
