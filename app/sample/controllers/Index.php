<?php namespace app\sample\controllers;

/* 主控制器 */

class Index
{
    public function index()
    {
        return '[控制器路由模式，主页面]，当前方法：'.__METHOD__;
    }

    public function ceshi()
    {
        logger()->debug('被你发现了,嘿嘿', ['timestamp'=>time()]);

        return '欢迎来到 HiWork, 您所看到是【'.\env('APP_NAME').'项目】的普通测试页面';
    }

    public static function ceshiStatic()
    {
        return '欢迎来到 HiWork, 您所看到是【'.\env('APP_NAME').'项目】的"静态"测试页面';
    }

}
