<?php namespace app\sample\middlewares;

use \Closure;

/* 后置中间件示例 */

class MyBeforeMiddleware2 extends \hw\Middleware
{
    public function handler(Closure $next, $request, $params)
    {
        // 车牌号
        $code = $request->param('code') ?? $request->input('code') ?? 0;
        if(!$code) exit('请传入车牌号！');

        // day日期
        $day = date('d');


        // echo '根据`单日单号，双日双号`通行规则：<br/>';
        if($code%2 == date('d')%2){
            echo"
                前置中间件2： 
                你的车牌号码`{$code}`, 在今天`{$day}`日，
                可以通行！<br/>
            ";
        }
        else{
            exit("
                前置中间件2： 
                你的车牌号码`{$code}`, 在今天`{$day}`日，
                禁止通行！！！
            ");
        }

        return call_user_func_array($next, $params);
    }

}
