<?php namespace app\sample;



class App
{
    use \hw\traits\Singlet;

    private function __construct(){}

    /**
     * 当前应用启动时要执行的内容
     * 20210102180207
     */
    public function boot()
    {
        // 模版公共数据
        $this->loadViewShareData();
    }

    // 注入视图共享数据 20210104120428
    private function loadViewShareData()
    {
        view()::share('chy', '宇航老师');
    }

    /* 
     * 配置所有路由路径
     * @return array 路由路径数组，如下：
     * 
     * return [
            \DOC_ROOT.'/routes/web.php',//公共路由
            \APP_ROOT.'/router/appRouter1.php',//项目定义的路由1
            // \APP_ROOT.'/router/appRouter2.php',//项目定义的路由2
        ];
     * 
     * 注意：当 getAppRouters()返回[]时，表示不使用路由 20210305125040
     * chy 20210104114856
     */
    public function getAppRouters() :array
    {
        return [
            \DOC_ROOT.'/app/route.php',//公共路由
            \APP_ROOT.'/routes/myroute.php',//项目路由
        ];
    }




}

