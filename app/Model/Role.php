<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = 'role';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getAccountTypeNameAttribute() {
        $account_type = $this->attributes['account_type'];
        switch ($account_type) {
            case 'L':
                return 'Root';
            case 'M':
                return 'Master';
            case 'D':
                return 'Distributor';
            case 'S':
                return 'Sub-Agent';
            default:
                return $account_type;
        }
    }

    public function getTypeNameAttribute() {
        $type = $this->attributes['type'];
        switch ($type) {
            case 'M':
                return 'Manager';
            case 'S':
                return 'Staff';
            case 'A':
                return 'AT&T Report';
            default:
                return $type;
        }
    }
}
