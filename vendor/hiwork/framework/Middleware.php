<?php namespace hw;

use \Closure;
use \hw\Request;

abstract class Middleware
{

    // 中间件调用入口 20201231102924
    public function closure(closure $next)
    {
         /* 
          * 说明：
          * ...$params 的第一位必须是 \hw\Request型，这个在\hw\Route::execute()中处理
          *  20210102113516
          */
        return function(...$params) use($next){

            // 取数据的说明：
            // 中间件： request
            // 最终调用执行的方法：request + route-params
            return \call_user_func_array([$this, 'handler'], [$next, $params[0], $params]);

        };
    }

    /* 
     * 中间件必需要实现的方法
     * 注：为方便开发者编写中间件，实际的闭包已被 self::closure 封装
     * $next 闭包函数
     * $request \hw\Request [必] 请求对象 
     * $params array [必] 路由参数，如[ param1, ...]
     * $next 必 闭包函数
     * 
     * chy 20201231103107
     */
    abstract public function handler(closure $next, closure $request,  array $params);



}

