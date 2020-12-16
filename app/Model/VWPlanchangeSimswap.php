<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 1/31/19
 * Time: 11:54 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VWPlanchangeSimswap extends Model
{
    protected $table = 'vw_planchange_simswap';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}