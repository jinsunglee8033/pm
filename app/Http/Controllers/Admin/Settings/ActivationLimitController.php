<?php
/**
 * Created by PhpStorm.
 * User: royce
 * Date: 12/5/18
 * Time: 10:38 AM
 */

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Model\Account;
use App\Model\AccountActivationLimit;

use App\Lib\Helper;

use Maatwebsite\Excel\Facades\Excel;

use Carbon\Carbon;
use Symfony\Component\Yaml\Tests\A;

class ActivationLimitController extends Controller
{
    public function show(Request $request)
    {

        if (Auth::user()->account_type !== 'L' || (!in_array(Auth::user()->user_id, ['thomas', 'system',
              'admin']) || !getenv('APP_ENV') == 'local')) {
            return redirect('/admin');
        }

        if (!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $default_limit = AccountActivationLimit::where('account_id', 100000)->first();

        $account_ids = Array();
        $account_ids[] = 100000;

        $intime = $request->intime;
        if (empty($intime)) $intime = 5;

        $threshold_activations = Helper::check_threshold_activations_by_account($intime);
        if (!empty($threshold_activations)) {
            foreach ($threshold_activations as $t) {
                $account_ids[] = $t->account_id;

                $t->limit = Account::leftJoin('account_activation_limit', 'accounts.id', '=', 'account_activation_limit.account_id')
                    ->where('accounts.id', $t->account_id)
                    ->first([
                        DB::raw('accounts.id as account_id'),
                        DB::raw('accounts.name as account_name'),
                        'account_activation_limit.hourly_preload',
                        'account_activation_limit.hourly_regular',
                        'account_activation_limit.hourly_byos',
                        'account_activation_limit.hourly_total',
                        'account_activation_limit.daily_preload',
                        'account_activation_limit.daily_regular',
                        'account_activation_limit.daily_byos',
                        'account_activation_limit.daily_total',
                        'account_activation_limit.weekly_preload',
                        'account_activation_limit.weekly_regular',
                        'account_activation_limit.weekly_byos',
                        'account_activation_limit.weekly_total',
                        'account_activation_limit.monthly_preload',
                        'account_activation_limit.monthly_regular',
                        'account_activation_limit.monthly_byos',
                        'account_activation_limit.monthly_total',
                        'account_activation_limit.allow_activation_over_max',
                        'account_activation_limit.cdate',
                        'account_activation_limit.mdate'
                    ]);

                if (empty($t->limit->cdate)) {
                    $t->limit->hourly_preload = $default_limit->hourly_preload;
                    $t->limit->hourly_regular = $default_limit->hourly_regular;
                    $t->limit->hourly_byos = $default_limit->hourly_byos;
                    $t->limit->hourly_total = $default_limit->hourly_total;
                    $t->limit->daily_preload = $default_limit->daily_preload;
                    $t->limit->daily_regular = $default_limit->daily_regular;
                    $t->limit->daily_byos = $default_limit->daily_byos;
                    $t->limit->daily_total = $default_limit->daily_total;
                    $t->limit->weekly_preload = $default_limit->weekly_preload;
                    $t->limit->weekly_regular = $default_limit->weekly_regular;
                    $t->limit->weekly_byos = $default_limit->weekly_byos;
                    $t->limit->weekly_total = $default_limit->weekly_total;
                    $t->limit->monthly_preload = $default_limit->monthly_preload;
                    $t->limit->monthly_regular = $default_limit->monthly_regular;
                    $t->limit->monthly_byos = $default_limit->monthly_byos;
                    $t->limit->monthly_total = $default_limit->monthly_total;
                    $t->limit->allow_activation_over_max = $default_limit->allow_activation_over_max;
                }
            }
        }

        $query = Account::leftJoin('account_activation_limit', 'accounts.id', '=', 'account_activation_limit.account_id')
          ->where('accounts.type', '=', 'S');

        if (!empty($request->account) && $request->account != 100000) {
            $query = $query->whereRaw("(accounts.id = '" . $request->account . "' or lower(accounts.name) like '%" . strtolower($request->account) . "%')");
        }

        $limits = $query->orderBy('accounts.path')
            ->paginate(20, [
                DB::raw('accounts.id as account_id'),
                DB::raw('accounts.name as account_name'),
                'account_activation_limit.hourly_preload',
                'account_activation_limit.hourly_regular',
                'account_activation_limit.hourly_byos',
                'account_activation_limit.hourly_total',
                'account_activation_limit.daily_preload',
                'account_activation_limit.daily_regular',
                'account_activation_limit.daily_byos',
                'account_activation_limit.daily_total',
                'account_activation_limit.weekly_preload',
                'account_activation_limit.weekly_regular',
                'account_activation_limit.weekly_byos',
                'account_activation_limit.weekly_total',
                'account_activation_limit.monthly_preload',
                'account_activation_limit.monthly_regular',
                'account_activation_limit.monthly_byos',
                'account_activation_limit.monthly_total',
                'account_activation_limit.allow_activation_over_max',
                'account_activation_limit.cdate',
                'account_activation_limit.mdate'
            ]);

        return view('admin.settings.activation_limit', [
            'account' => $request->account,
            'intime' => $request->intime,
            'account_ids' => $account_ids,
            'threshold_activations' => $threshold_activations,
            'limits' => $limits,
            'default_limit' => $default_limit
        ]);
    }

    public function update(Request $request)
    {

        if (!Auth::check() || Auth::user()->account_type != 'L') {
            return redirect('/admin/dashboard');
        }

        $limit = AccountActivationLimit::where('account_id', $request->account_id)->first();

        if (empty($limit)) {
            $limit = new AccountActivationLimit();
            $limit->account_id = $request->account_id;
            $limit->cdate = \Carbon\Carbon::now();
        } else {
            $limit->mdate = \Carbon\Carbon::now();
        }

        $limit->hourly_preload = $request->hourly_preload;
        $limit->hourly_regular = $request->hourly_regular;
        $limit->hourly_byos = $request->hourly_byos;
        $limit->daily_preload = $request->daily_preload;
        $limit->daily_regular = $request->daily_regular;
        $limit->daily_byos = $request->daily_byos;
        $limit->weekly_preload = $request->weekly_preload;
        $limit->weekly_regular = $request->weekly_regular;
        $limit->weekly_byos = $request->weekly_byos;
        $limit->monthly_preload = $request->monthly_preload;
        $limit->monthly_regular = $request->monthly_regular;
        $limit->monthly_byos = $request->monthly_byos;
        $limit->allow_activation_over_max = $request->allow_activation_over_max;
        $limit->save();

        return redirect('/admin/settings/activation-limit?account_id=' . $request->account_id);
    }
}