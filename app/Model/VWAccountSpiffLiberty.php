<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/5/19
 * Time: 4:23 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VWAccountSpiffLiberty extends Model
{
    protected $table = 'vw_account_spiff_liberty_tree';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}