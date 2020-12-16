<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/25/19
 * Time: 11:26 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    protected $table = 'bonus';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
