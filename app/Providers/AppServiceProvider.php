<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Validator;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //附加验证规则
        require_once __DIR__.'/' . '../Validations.php';
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
    }
}
