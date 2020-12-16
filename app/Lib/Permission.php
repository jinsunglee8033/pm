<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 12/12/17
 * Time: 11:46 AM
 */

namespace App\Lib;

use App\Model\Account;
use App\Model\PermissionAction;
use App\Model\PermissionPath;
use App\Model\Role;
use App\Model\RolePermission;
use Illuminate\Support\Facades\Auth;

class Permission
{
    public static function can($path, $action) {
        $user = Auth::user();
        if (empty($user)) {
            Helper::log('### can_view ###', [
                'msg' => 'No user logged-In'
            ]);
            return false;
        }

        $account = Account::find($user->account_id);
        if (empty($account)) {
            Helper::log('### can_view ###', [
                'msg' => 'No user account found'
            ]);
            return false;
        }

        $role = Role::where('account_type', $account->type)
            ->where('type', $user->role)
            ->first();

        if (empty($role)) {
            Helper::log('### can_view ###', [
                'msg' => 'No user role found : ' . $user->role
            ]);

            return false;
        }

        $pp = PermissionPath::whereRaw("? like concat(path, '%')", [$path])->first();
        if (empty($pp)) {
            return true;
        }

        $pa = PermissionAction::where('path_id', $pp->id)
            ->where('action', $action)
            ->first();
        if (empty($pa)) {
            return true;
        }

        $rp = RolePermission::where('action_id', $pa->id)
            ->where('role_id', $role->id)
            ->first();
        if (empty($rp)) {
            Helper::log('### can_view ###', [
                'msg' => 'No role permission found'
            ]);
            return false;
        }

        return $rp->has_permission == 'Y';
    }
}