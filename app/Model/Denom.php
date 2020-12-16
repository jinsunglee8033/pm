<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Denom extends Model
{
    protected $table = 'denomination';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getLastUpdatedAttribute() {
        return empty($this->attributes['mdate']) ?
            ( $this->attributes['cdate'] . ' ( ' . $this->attributes['created_by'] . ' )') :
            ( $this->attributes['mdate'] . ' ( ' . $this->attributes['modified_by'] . ' )');
    }

    public $appends = ['last_updated'];
}
