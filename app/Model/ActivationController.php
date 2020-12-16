<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 5/19/19
 * Time: 7:01 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ActivationController extends Model
{
    protected $table = 'activation_controller';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
