<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VRQuickSearch extends Model
{
    protected $table = 'vr_quick_search';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;

    public static function get_source() {
    	return VRQuickSearch::orderBy('sorting', 'asc')->get();
    }
}
