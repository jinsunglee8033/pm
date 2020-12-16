<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Model\Account;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            $url = '/';
            $user = Auth::user();
            $account = Account::find($user->account_id);
            switch ($account->type) {
                case 'L':
                case 'M':
                case 'D':
                case 'A':
                    $url = '/admin';
                    break;
                case 'S':
                    $url = '/sub-agent';
                    break;
            }

            return redirect($url);
        }

        return $next($request);
    }
}
