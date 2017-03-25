<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Intervention\Image\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        return parent::report($e);
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
        if($e instanceof NotFoundException || $e instanceof NotFoundHttpException)
        {
            return new JsonResponse(['code'=>404,'message'=>$e->getMessage()],200);
        }elseif ($e instanceof MethodNotAllowedException || $e instanceof MethodNotAllowedHttpException){
            return new JsonResponse(['code'=>405,'message'=>'method not allowed'],200);
        }elseif($e instanceof UnauthorizedHttpException){
            return new JsonResponse(['code'=>401,'message'=>$e->getMessage()],200);
        }elseif($e instanceof Exception){
            $errorCode = $e->getCode() ? :500;
            if(env('APP_DEBUG')){
                return new JsonResponse(['code'=>$errorCode,'message'=>myException($e)],200);
            }else{
                return new JsonResponse(['code'=>$errorCode,'message'=>$e->getMessage()],200);
            }
        }
        return parent::render($request, $e);
    }
}
