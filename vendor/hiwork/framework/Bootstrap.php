<?php namespace hw;

// use \hw\Route;

class Bootstrap
{
    public function run()
    {
        $this->init();//全局初始化
        \hw\HwException::exc();//错误器注册
        
        exit ((new \hw\Route)->parse());//路由解析分发
    }

    // 全局初始化
    public function init()
    {
        // web根目录
        \define('DOC_ROOT', $_SERVER['DOCUMENT_ROOT'].'/..');
        
        // 项目根目录
        \define('APP_ROOT', DOC_ROOT.'/app/'.\env('APP_NAME'));

        //时区
        \date_default_timezone_set(\env('APP_TIMEZONE'));
    }


}
