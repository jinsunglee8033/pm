<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 3/1/19
 * Time: 5:06 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class GenPinTransaction extends Model
{
    protected $table = 'gen_pin_transaction';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

}
