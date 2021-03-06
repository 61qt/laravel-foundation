<?php

namespace QT\Foundation\Exceptions;

use Throwable;
use Illuminate\Http\Request;
use UnexpectedValueException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use GraphQL\Error\Error as GraphQLError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use QT\Import\Exceptions\ValidationException as ImportValidationException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        ValidationException::class,
        UnauthorizedException::class,
        UnexpectedValueException::class,
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    protected function shouldntReport(Throwable $e)
    {
        if ($e instanceof GraphQLError && $e->getPrevious() !== null) {
            return $this->shouldntReport($e->getPrevious());
        }

        if ($e instanceof Error) {
            return !$e->shouldReport();
        }

        return parent::shouldntReport($e);
    }

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable('report_exception');

        $this->renderable(function (Throwable $e, Request $request) {
            // graphql??????????????????????????????,?????????????????????????????????
            if ($e instanceof GraphQLError && $e->getPrevious() !== null) {
                $e = $e->getPrevious();
            }

            return $this->prepareJsonResponse($request, $e);
        });
    }

    /**
     * Prepare a JSON response for the given exception.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\JsonResponse
     */
    protected function prepareJsonResponse($request, Throwable $e)
    {
        return new JsonResponse($this->convertExceptionToArray($e));
    }

    /**
     * Convert the given exception to an array.
     *
     * @param  \Throwable  $e
     * @return array
     */
    protected function convertExceptionToArray(Throwable $e)
    {
        $msg  = $e->getMessage();
        $data = [];
        $code = Error::DEFAULT_ERR_CODE;

        switch ($e) {
            case $e instanceof ValidationException:
                $msg  = '??????????????????';
                $data = $e->validator->errors();
                $code = 1001;
                break;

            case $e instanceof Error:
                $data = $e->getData();
                $code = $e->getCode();
                break;

            case $e instanceof AuthenticationException:
                $code = 401;
                $msg  = "??????????????????,???????????????";
                break;

            case $e instanceof NotFoundHttpException:
            case $e instanceof ModelNotFoundException:
                $msg  = !isDevelopEnv() ? "????????????????????????" : $msg;
                $code = 404;
                break;

            case $e instanceof MethodNotAllowedHttpException:
                $msg  = "http???????????????";
                $code = 405;
                break;

            case $e instanceof ThrottleRequestsException:
                $msg = '????????????????????????????????????';
                break;

            case $e instanceof PostTooLargeException:
                $msg  = "????????????????????????" . ini_get('post_max_size');
                $code = 413;
                break;

            case $e instanceof ImportValidationException:
                $code = 400;
                $msg  = $e->getMessage();
                break;

            default:
                // ??????????????????,???????????????????????????????????????
                if (!isDevelopEnv()) {
                    $msg = "????????????";
                }
                Log::error($e->getMessage());
                break;
        }

        return [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];
    }
}
