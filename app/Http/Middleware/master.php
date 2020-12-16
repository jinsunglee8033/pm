<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Model\Account;

class master
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

        if ($account->type != 'M') {
            return redirect('/login')->withErrors([
                'exception' => 'Only master user can access the URL'
            ]);
        }

        return $next($request);
    }
}
