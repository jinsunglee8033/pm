<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 1/31/19
 * Time: 5:22 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VWActivationRefill extends Model
{
    protected $table = 'vw_activation_refill';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}