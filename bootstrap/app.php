<?php

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| The first thing we will do is create a new Laravel application instance
| which serves as the "glue" for all the components of Laravel, and is
| the IoC container for the system binding all of the various parts.
|
*/

$app = new Illuminate\Foundation\Application(
    realpath(__DIR__.'/../')
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
|
| Next, we need to bind some important interfaces into the container so
| we will be able to resolve them when needed. The kernels serve the
| incoming requests to this application from both the web and CLI.
|
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Log Mange
|--------------------------------------------------------------------------
| "DEBUG" => 100
| "INFO" => 200
| "NOTICE" => 250
| "WARNING" => 300
| "ERROR" => 400
| "CRITICAL" => 500
| "ALERT" => 550
| "EMERGENCY" => 600
|
*/

$app->configureMonologUsing(function($monolog) use($app){
    //记录日志到本地
    if(config('log-manage.log_local')){
        $monolog->pushHandler(new Monolog\Handler\StreamHandler(storage_path().'/logs/sunallies-agile.log',Monolog\Logger::DEBUG));
    }

    //记录日志到logStash,将异常信息发送邮件
    if($app->environment() == 'production' && config('log-manage.log_production')){
        $monolog->pushProcessor(function ($record){
            $logManager = new \App\Service\LogManageService($record);
            //非debug的日志记录到logStash
            $level = $record['level'];
            if($level>=200){
                $logManager->logToRMQ();
            }

            //ERROR 以上的日志信息发送至邮件
            if($level >= 400){
                $logManager->sendEmail();
            }
            return $record;
        });
    }
});


/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
|
| This script returns the application instance. The instance is given to
| the calling script so we can separate the building of the instances
| from the actual running of the application and sending responses.
|
*/

return $app;
