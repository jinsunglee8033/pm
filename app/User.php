<?php

namespace App;

use App\Model\Role;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Model\Account;
use App\Model\AccountAuthority;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $table = 'users';

    public $timestamps = false;

    protected $dateFormat = 'U';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected function getAccountNameAttribute() {
        $account = Account::find($this->account_id);
        return $account->name;
    }

    protected function getAccountTypeAttribute() {
        $account = Account::find($this->account_id);
        return $account->type;
    }

    protected function getPayMethodAttribute() {
        $account = Account::find($this->account_id);
        return $account->pay_method;
    }

    protected function getCreditLimitAttribute() {
        $account = Account::find($this->account_id);
        return $account->credit_limit;
    }

    protected function getLastLoginAttribute() {
        return is_null($this->attributes['last_login']) ? '' : $this->attributes['last_login'];
    }

    protected function getRoleNameAttribute() {
        $account = Account::find($this->account_id);
        if (empty($account)) {
            return $this->attributes['role'];
        }

        $role = Role::where('account_type', $account->type)
            ->where('type', $this->attributes['role'])
            ->first();

        if (empty($role)) {
            return $this->attributes['role'];
        }

        return $role->name;
    }

    protected function getStatusNameAttribute() {
        switch ($this->attributes['status']) {
            case 'A':
                return 'Active';
            case 'H':
                return 'On-Hold';
            case 'C':
                return 'Closed';
        }

        return $this->attributes['status'];
    }

    public function getAuthorityAttribute() {
        $authority = AccountAuthority::where('account_id', $this->account_id)->first();
        if (empty($authority)) {
            $authority = new AccountAuthority();
            $authority->account_id = $this->account_id;
            $authority->auth_batch_rtr = 'N';
            $authority->auth_batch_sim_swap = 'N';
            $authority->auth_batch_plan_change = 'N';
            $authority->for_rtr_daily = 20;
            $authority->for_rtr_weekly = 100;
            $authority->for_rtr_monthly = 300;
            $authority->for_sim_swap_daily = 20;
            $authority->for_sim_swap_weekly = 100;
            $authority->for_sim_swap_monthly = 300;
            $authority->for_plan_change_daily = 20;
            $authority->for_plan_change_weekly = 100;
            $authority->for_plan_change_monthly = 300;
            $authority->cdate = \Carbon\Carbon::now();
            $authority->save();
        }
        return $authority;
    }
}
