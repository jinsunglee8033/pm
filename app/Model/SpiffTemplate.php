<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 2/6/19
 * Time: 5:49 PM
 */

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SpiffTemplate extends Model
{
    protected $table = 'spiff_template';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function get_template_name($id) {
        $template = SpiffTemplate::find($id);

        if (empty($template)) return $id;

        return $template->account_type . ' ' . $template->template;
    }
}
