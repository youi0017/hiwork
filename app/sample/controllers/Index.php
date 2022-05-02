<?php namespace app\sample\controllers;

/* 主控制器 */

class Index
{
    public function ceshi()
    {
        var_dump('ceshi page');exit;
        logger()->info('被你发现了， 嘿嘿');

        return '欢迎来到 HiWork, 您所看到是【'.\env('APP_NAME').'项目】的普通测试页面';
    }

    public static function ceshiStatic()
    {
        return '欢迎来到 HiWork, 您所看到是【'.\env('APP_NAME').'项目】的"静态"测试页面';
    }

    // 使用中间件：模似车辆限行
    public function cheliangxianxing($req, $code, $hour)
    {
        var_dump("执行路由到控制器@方法，并使用中间件：", $req, $code, $hour);
        
    }

}
