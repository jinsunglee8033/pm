<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 12/5/18
 * Time: 10:40 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountActivationLimit extends Model
{
    protected $table = 'account_activation_limit';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
