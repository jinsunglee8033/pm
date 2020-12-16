<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/5/19
 * Time: 4:22 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VWAccountSpiffFreeup extends Model
{
    protected $table = 'vw_account_spiff_freeup_tree';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}