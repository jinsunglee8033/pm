<?php

namespace App\Exceptions;

use App\Lib\Helper;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use MongoDB\Driver\Query;
use Psy\Exception\FatalErrorException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $e)
    {
        //return parent::render($request, $e);

        if ((getenv('APP_ENV') == 'production' || !env('APP_DEBUG')) && (!$this->isHttpException($e) || $e instanceof QueryException || $e instanceof \PDOException)) {

            if (!($e instanceof TokenMismatchException) && !($e instanceof BroadcastException)) {
                $msg = ' - error : ' . $e->getMessage() . ' [' . $e->getCode() . '] : ' . $e->getTraceAsString();

                $pos = strpos($msg, 'BroadcastServiceProvider');
                if ($pos === false) {
                    Helper::send_mail('it@perfectmobileinc.com', '[PM][' . $request->ip() . '][' . getenv('APP_ENV') . '] Exception', $msg);
                }
            }

            return response()->view('errors.500', [
                'e' => $e
            ], 500);
        }

        return parent::render($request, $e);
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        return redirect()->guest('login');
    }
}
