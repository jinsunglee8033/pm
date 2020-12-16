<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use App\User;

class VirtualRep extends Model
{
    protected $table = 'virtual_rep';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'id';

    public function getCategoryNameAttribute() {
        switch ($this->attributes['category']) {
            case 'M':
                return 'Marketing Materials';
            case 'E':
                return 'Equipment Ordering';
            case 'T':
                return 'Technical Issues';
            case 'C':
                return 'Comments';
        }

        return $this->attributes['category'];
    }

    public function getStatusNameAttribute() {

        switch ($this->attributes['status']) {
            case 'N':
                $html = 'New';
                break;
            case 'R':
                $html = 'Rejected';
                break;
            case 'P':
                $html = 'Pending';
                break;
            case 'W':
                $html = 'Processing';
                break;
            case 'C':
                $html = '<span style="color:green;font-weight:bold;">Completed</span>';
                break;
            default:
                $html = $this->attributes['status'];
                break;
        }

        return $html;
    }

    public function getLastModifiedAttribute() {
        if (!empty($this->attributes['mdate'])) {
            return $this->attributes['mdate'] . ' (' . $this->attributes['modified_by'] . ')';
        }

        return $this->attributes['cdate'] . ' (' . $this->attributes['created_by'] . ')';
    }

    public function getAccountTypeAttribute() {
        $user = User::find($this->attributes['created_by']);
        if (empty($user)) {
            return '';
        }
        $account = Account::find($user->account_id);
        if (empty($account)) {
            return '';
        }
        return $account->type;
    }

    public function getAccountIdAttribute() {
        $user = User::find($this->attributes['created_by']);
        if (empty($user)) {
            return '';
        }
        return $user->account_id;
    }

    public function getAccountNameAttribute() {
        $user = User::find($this->attributes['created_by']);
        if (empty($user)) {
            return '';
        }
        $account = Account::find($user->account_id);
        if (empty($account)) {
            return '';
        }
        return $account->name;
    }

    public function getAccountPhoneAttribute() {
        $user = User::find($this->attributes['created_by']);
        if (empty($user)) {
            return '';
        }
        $account = Account::find($user->account_id);
        if (empty($account)) {
            return '';
        }

        return $account->office_number;
    }

    public function getAccountEmailAttribute() {
        $user = User::find($this->attributes['created_by']);
        if (empty($user)) {
            return '';
        }
        $account = Account::find($user->account_id);
        if (empty($account)) {
            return '';
        }

        return $account->email;
    }
}
