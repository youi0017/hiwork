<?php namespace app\sample\middlewares;

use \Closure;

/* 后置中间件示例 */

class MyAfterMiddleware extends \hw\Middleware
{
    public function handler(Closure $next, $request, $params)
    {
        $r = call_user_func_array($next, $params);

        echo '后置中间件：您已通过，欢迎您的下次光临！<br/>';

        return $r;
    }

}
