<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountFile extends Model
{
    protected $table = 'account_files';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public static function types() {
        return [
            'FILE_STORE_FRONT' => 'Store Photo - Front',
            'FILE_STORE_INSIDE' => 'Store Photo - Inside',
            'FILE_W_9' => 'W9 Form *',
            'FILE_PR_SALES_TAX' => 'Puerto Rico Sales Tax Exemption Form',
            'FILE_USUC' => 'Uniform Sales and Use Certificate',
            'FILE_TAX_ID' => 'Tax ID Form',
            'FILE_BUSINESS_CERTIFICATION' => 'Business Certification',
            'FILE_DEALER_AGREEMENT' => 'Dealer Agreement *',
            'FILE_DRIVER_LICENSE' => 'Driver License',
            'FILE_VOID_CHECK' => 'Void Check',
            'FILE_ACH_DOC' => 'ACH Doc',
            'FILE_BANK_REFERENCE' => 'Bank Reference',
//            'FILE_H2O_DEALER_FORM' => 'H2O Dealer Registration',
//            'FILE_H2O_ACH'  => 'H2O ACH Authorization'
        ];
    }

    public static function h2o_types() {
        return [
            'FILE_H2O_DEALER_FORM' => 'H2O Dealer Registration',
            'FILE_H2O_ACH'  => 'H2O ACH Authorization'
        ];
    }
}
