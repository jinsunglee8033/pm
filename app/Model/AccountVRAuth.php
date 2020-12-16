<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 6/11/19
 * Time: 3:44 AM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountVRAuth extends Model
{
    protected $table = 'account_vr_auth';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
