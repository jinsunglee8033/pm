<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PmModelSimLookup extends Model
{
    protected $table = 'pm_model_sim_lookup';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public $incrementing = false;
}
