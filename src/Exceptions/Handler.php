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
     * Determine if the exception should be reported.
     *
     * @param  \Throwable  $e
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        if ($e instanceof Error) {
            return $e->shouldReport();
        }

        return parent::shouldReport($e);
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
            // graphql会把错误包装一层抛出,所以获取原异常信息处理
            if ($e instanceof GraphQLError) {
                return $this->prepareJsonResponse($request, $e->getPrevious());
            }
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
                $msg  = '填写数据有误';
                $data = $e->validator->errors();
                $code = 1001;
                break;

            case $e instanceof Error:
                $data = $e->getData();
                $code = $e->getCode();
                break;

            case $e instanceof AuthenticationException:
                $code = 401;
                $msg  = "用户认证失败,请重新登录";
                break;

            case $e instanceof NotFoundHttpException:
            case $e instanceof ModelNotFoundException:
                $msg  = !isDevelopEnv() ? "访问的数据不存在" : $msg;
                $code = 404;
                break;

            case $e instanceof MethodNotAllowedHttpException:
                $msg  = "http方法不存在";
                $code = 405;
                break;

            case $e instanceof ThrottleRequestsException:
                $msg = '访问过于频繁，请稍后再试';
                break;

            case $e instanceof PostTooLargeException:
                $msg  = "上传文件大小超过" . ini_get('post_max_size');
                $code = 413;
                break;

            default:
                // 除了特定错误,其他错误信息在正式环境屏蔽
                if (!isDevelopEnv()) {
                    $msg = "系统繁忙";
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
