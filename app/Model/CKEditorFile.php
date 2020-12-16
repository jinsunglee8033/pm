<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CKEditorFile extends Model
{
    protected $table = 'ckeditor_files';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';
}
