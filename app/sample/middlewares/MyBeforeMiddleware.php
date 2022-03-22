<?php namespace app\sample\middlewares;

use \Closure;

/* 前置中间件示例 */

class MyBeforeMiddleware extends \hw\Middleware
{
    public function handler(Closure $next, $request, $params)
    {
        // var_dump($request, $params);exit;

        $hour = $request->param('hour') ?? $request->input('hour') ?? 7;

        if($hour>7 && $hour<19){
            exit('前置中间件1：早晚7点间所有车辆禁止通行！！！');
        }

        echo '前置中间件1：`早晚7点以外`为正常时段，请通行！<br/>';

        // var_dump($params);
        // return $next($request, $params);
        // array_unshift($params, $request);
        // var_dump($params);exit;


        return call_user_func_array($next, $params);
    }

}
