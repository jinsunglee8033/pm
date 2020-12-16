<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/26/19
 * Time: 10:01 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BonusException extends Model
{
    protected $table = 'bonus_exception';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
