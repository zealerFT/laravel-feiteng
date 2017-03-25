<?php

namespace App\Http\Middleware;

use App\DataServer\Hybrid\UserService;
use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CheckUserToken
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array
     */
    protected $except = [
        'user/login',
        'user/logout',
        'user/register',
        'user/captcha',
        'user/captcha/*',
        'user/register/*',
        'user/resetPwd/*',
        'user/resetPwd',
        'purchase/paymentRedirect/*',
        'contract/prod/*',
        'contract/prodTransfer/*',
        'activity/springFestival2017/investmentRankTime',
        'activity/springFestival2017/luckydrawTime',
        'activity/homeActivityInfo/springFestival2017Info',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if(!$this->shouldPassThrough($request)){
            $userApi = new UserService();
            $userInfo = $userApi->getUserInfo();
            if(!sizeof($userInfo)){
                throw new UnauthorizedHttpException('Basic realm="My Realm"','need Token');
            }
        }
        return $next($request);
    }

    /**
     * Determine if the request has a URI that should pass through the verification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldPassThrough($request)
    {
        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            if ($request->is($except)) {
                return true;
            }
        }
        return false;
    }
}
