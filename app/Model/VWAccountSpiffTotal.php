<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 4/19/19
 * Time: 10:12 AM
 */


namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VWAccountSpiffTotal extends Model
{
    protected $table = 'vw_account_spiff_total';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}