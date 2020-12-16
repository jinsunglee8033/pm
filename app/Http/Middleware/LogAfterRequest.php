<?php
/**
 * Created by PhpStorm.
 * User: yongj
 * Date: 5/17/16
 * Time: 10:41 AM
 */

namespace App\Http\Middleware;

use App\Lib\Helper;
use Closure;
use Illuminate\Support\Facades\Log;


class LogAfterRequest {

    public function handle($request, \Closure  $next)
    {
        return $next($request);
    }

    public function terminate($request, $response)
    {
        Helper::log('### APP.REQUESTS ###', [
            'REQUEST' => $request->except('image', 'photo'),
            'METHOD' => $request->getMethod()//,
            //'RESPONSE' => $response->getContent(),
        ]);
    }

}
