<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 12/24/18
 * Time: 9:31 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PaypalLog extends Model
{
    protected $table = 'paypal_log';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
