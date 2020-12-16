<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/20/19
 * Time: 5:03 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Zip extends Model
{
    protected $table = 'zip';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
