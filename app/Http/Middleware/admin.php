<?php

namespace App\Http\Middleware;

use App\Lib\Helper;
use App\Lib\Permission;
use Closure;
use Illuminate\Support\Facades\Auth;
use App\Model\Account;

class admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if (empty($user)) {
            return redirect('/login')->withErrors([
                'exception' => 'Login required to access'
            ]);
        }

        $account = Account::find($user->account_id);
        if (empty($account)) {
            return redirect('/login')->withErrors([
                'exception' => 'Invalid account ID provided'
            ]);
        }

        if ($account->type == 'S') {
            return redirect('/login')->withErrors([
                'exception' => 'Only partner user can access the URL'
            ]);
        }

        if (!Permission::can($request->path(), 'view')) {

            Helper::log('### PATH ###', [
                'path' => $request->path(),
                'can_view' => Permission::can($request->path(), 'view')
            ]);
            return redirect('/admin/error')->with([
                'error_msg' => 'You are not authorized to view this page.'
            ]);
        }

        return $next($request);
    }
}
