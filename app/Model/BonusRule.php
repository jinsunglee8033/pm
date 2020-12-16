<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/25/19
 * Time: 11:27 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BonusRule extends Model
{
    protected $table = 'bonus_rule';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
