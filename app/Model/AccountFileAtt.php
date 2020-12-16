<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountFileAtt extends Model
{
    protected $table = 'account_files_att';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function types() {
        return [
            'FILE_ATT_AGREEMENT' => 'Retailer Application and Agreement',
            'FILE_ATT_DRIVER_LICENSE' => 'Driver License',
            'FILE_ATT_BUSINESS_CERTIFICATION' => 'Business Certification',
            'FILE_ATT_VOID_CHECK' => 'Void Check'
        ];
    }
}
