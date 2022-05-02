<?php
const CTL_SPACE = '\app\sample\controllers';

// 自定义正则类型
$router->addMatchTypes([
    'id28'=>'\d{2,8}',//2到8位数字
    '_html'=>'(\.html)?',//.html后缀
]);

//= 基础路由 =============================
// 解析到成员方法
$router->map('get', '/cs[.html]?', CTL_SPACE.'\Index@ceshi', 'cs-page');
$router->map('get', '/cs2[_html]', CTL_SPACE.'\Index@ceshi', 'c2-page');//效果同上

//绑定参数（数字字母）到$catagory变量
$router->map('get','/books/[a:catagory][_html]', function($catagory){
    var_dump('您查看的图书类别是：'.$catagory);
});

//绑定指定参数（只能是yunwen|shuxue）到$kemu变量
$router->map('get','/kemus/[yunwen|shuxue:kemu][_html]', function($kemu){
    var_dump('当前的科目是：'.$kemu);
});


// 自定义规则，绑定数据
$router->map('get','/user/[id28:id]', function($id){
    var_dump('当前用户: id='.$id);
});

$router->map('get','/students/[\d{2,5}:id]', function($id){
    var_dump('当前学生 id : '.$id);
});


//== 动态路由 ==============================
// 使用数字参数，并约束长度
$router->map('get', '/goods/[\d{2}:id][.html]?', function($id){
    var_dump('您当前查看的商品 id 为 :'.$id);
});

// 解析到静态方法
$router->map('get','/csst[/|.html]?', CTL_SPACE.'\Index::ceshiStatic');


//== 路由组 ============================
$router->group('/some', [
    ['GET', '[/]?', '__getUri'],
    ['GET', '/abc[/]?', '__getUri'],
    ['GET', '/abc/xxx[/|.html]?', '__getUri'],
]);


function __getUri(){
    var_dump('当前访问uri: '. $_SERVER['REQUEST_URI'], time());
}




//== 视图 ============================
$router->map('get','/view/[id28:id]', function($id){

    // 视图位置 /resources/views/sample/sharedata.phtml
    // sharedata.phtml中使用了公共视图数据$chy
    return view()->assign('id', $id)->display('sample/sharedata.phtml');

});


// 测试跌幅
$router->map('get','/bug', function(){

    // 错误页面办理出
    // var_dump(unknowfun());

    // 抛出异常
    error('抛出一个错误');

});





/* 
// 20220502110810 移除对中间件的支持

//= 中间件 =============================
// 中间件+闭包 示例：模似车辆限行cheliangxianxing
$router->map('get','/clxx/{code}/{hour}', [
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
$router->map('get','/clxx2/{code:\d+}/{hour:\d{1,2}}', [
    'middleware'=>[
        '\app\sample\middlewares\MyBeforeMiddleware',
        '\app\sample\middlewares\MyBeforeMiddleware2',
        '\app\sample\middlewares\MyAfterMiddleware',
    ],
    CTL_SPACE.'\Index@cheliangxianxing'
]);

 */