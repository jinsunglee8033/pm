<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PermissionPath extends Model
{
    protected $table = 'permission_path';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getActionsQtyAttribute() {

        $actions = PermissionAction::where('path_id', $this->attributes['id'])->get();
        if (empty($actions)) {
            return 0;
        }

        return count($actions);
    }
}
