<?php
const CTL_SPACE = '\app\sample\controllers';

//= 基础路由 =============================
// 解析到成员方法
$route->get('/cs.html', CTL_SPACE.'\Index@ceshi');

// 解析到静态方法
$route->get('/csst[\.html]', CTL_SPACE.'\Index::ceshiStatic');

// 绑定数据
$route->get('/user/{id:\d{2,5}}', function($id){
    var_dump('当前用户: id='.$id);
});

// 视图
$route->get('/view/{id:\d{2,5}}', function($id){

    // 视图位置 /resources/views/sample/sharedata.phtml
    // sharedata.phtml中使用了公共视图数据$chy
    return view()->assign('id', $id)->display('sample/sharedata.phtml');

});




//= 中间件 =============================
// 中间件+闭包 示例：模似车辆限行cheliangxianxing
$route->get('/clxx/{code}/{hour}', [
    'middleware'=>[
        '\app\sample\middlewares\MyBeforeMiddleware',
        '\app\sample\middlewares\MyBeforeMiddleware2',
        '\app\sample\middlewares\MyAfterMiddleware',
    ],
    // $ctlSapce('Index@index')
    function($request, $code, $hour){
        $r = '最终调用方法：';
        $r .= var_export([$request, $code, $hour], true);
        return $r.'<br/>';
    }
]);


// 中间件+控制器成员方法 示例：模似车输限行
$route->get('/clxx2/{code:\d+}/{hour:\d{1,2}}', [
    'middleware'=>[
        '\app\sample\middlewares\MyBeforeMiddleware',
        '\app\sample\middlewares\MyBeforeMiddleware2',
        '\app\sample\middlewares\MyAfterMiddleware',
    ],
    CTL_SPACE.'\Index@cheliangxianxing'
]);

