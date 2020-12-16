<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/13/19
 * Time: 2:24 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SpiffTemplateOwner extends Model
{
    protected $table = 'spiff_template_owner';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
