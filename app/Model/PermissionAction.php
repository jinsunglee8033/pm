<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PermissionAction extends Model
{
    protected $table = 'permission_action';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getPathAttribute() {
        $path = PermissionPath::find($this->attributes['path_id']);
        if (!empty($path)) {
            return $path->path;
        }

        return '';
    }

    protected $appends = ['path'];
}
