<?php namespace hw;

use \hw\Request;

/* 
 * 路由解析与处理
 * chy 20201231112516
 * 
 * [更新-chy-20210404115053] 
    parseRoute()中env('path', array)更改为env('path', string)

 */

class Route
{
    // 取得当前App配置
    private function getApp()
    {
        $app =  '\app\\'.\env("APP_NAME").'\App';
        return $app::mine();
    }

    // 阻止option请求 20210205161033
    private function preventOption()
    {
        if( $_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
            header("Access-Control-Allow-Origin: *");
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization");
            // Rtn::setHttpCode(200);
            // var_dump('REQUEST_METHOD=OPTIONS');
            exit('OPTIONS-EXIT-200');
        }
    }

    // 解析路由
    public function parse()
    {
        //0. 阻止option请求
        $this->preventOption();
        // 取得路由配置文件（如无则为控制器路由模式）
        $routeFiles = $this->getApp()->getAppRouters();

        // 有路由配置则解析路由，否则为解析uri
        return empty($routeFiles)
            ? $this->parseUri()
            : $this->parseRoute($routeFiles);
    }

    // 解析uri 20210221164747
    public function parseUri()
    {
        $uri = $_SERVER['REQUEST_URI'];

        //1. 只取路径部份过滤掉参数部分（?之后的所有内容）        
        if (false !== $pos = strpos($uri, '?')) 
            $uri = substr($uri, 0, $pos);

        //2. 过滤掉后缀部分（.之后的所有内容）
        if(substr($uri, -5)=='.html')
            $uri = substr($uri, 0, -5);  

        //3. 解析为uriPath
        $uriArr = explode('/', \rawurldecode($uri));
        // \env('path', $uriArr);


        if(empty($uriArr[1])){
            $ctl = 'Index';
            $act = 'index';
        }
        else{
            $ctl = \ucfirst($uriArr[1]);
            if($ctl=='Index')
                throw new \Exception('默认控制器不能使用[Index]');

            if( empty($uriArr[2]) ) 
                $act='index';
            else{
                if($uriArr[2]=='index')
                    throw new \Exception('默认执行器不能使用[index]');
            }
        }

        // var_dump($uri, $uriArr, $ctl, $act);exit;

        // 4. 取回ctl和act
        $ctl =  empty($uriArr[1]) ? 'Index' : \ucfirst($uriArr[1]);
        $ctl = '\app\\'.\env('APP_NAME').'\controllers\\'.$ctl;
        $act =  empty($uriArr[2]) ? 'index' : $uriArr[2];
        // var_dump($uri, $uriArr, $ctl, $act);//exit;

        // 5. 执行
        return \call_user_func_array([new $ctl, $act], \array_slice($uriArr, 3));
    }
    

    // 解析路由 20210221154756
    public function parseRoute(array $routeFiles)
    {
        // 1. 引入falstRoute
        // vendor('fast-route'); 

        // 2. 载入路由
        $dispatcher = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $route) use($routeFiles) {

            foreach($routeFiles as $f){
                \is_file($f)
                    ? include($f)
                    : logger()->error('路由不存在：'.\basename ($f));
            }
        });

        // 3. 处理 uri 和 httpMethod
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];

        //只取路径部份过滤掉参数部分（?之后的所有内容）        
        // $uri = \parse_url($uri)['path'];
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        //过滤掉后缀部分（.之后的所有内容）
        // if (false !== $pos = \strrpos($uri, '.')) {
        //     $uri = \substr($uri, 0, $pos);
        // }

        // url解码
        $uri = \rawurldecode($uri);

        // var_dump($uri);exit;
        // 将路径信息(字串)写入环境变量
        \env('uri', $uri);

        // 4.得到路由的解析结果
        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        // 5. 结果分派
        switch ($routeInfo[0]) {

            // uri不匹配
            case \FastRoute\Dispatcher::NOT_FOUND:
                // throw new \hw\error\HttpError(404);
                \hw\Rtn::epage(404);

            // 请求方式不匹配
            case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                // $allowedMethods = $routeInfo[1];
                // ... 405 Method Not Allowed
                // var_dump('405 Method Not Allowed'); break;
                // throw new \hw\error\HttpError(405);
                \hw\Rtn::epage(405);

            // 匹配ok
            case \FastRoute\Dispatcher::FOUND:
                // $handler = $routeInfo[1];//回调资源
                // $vars = $routeInfo[2];//路由参数数据
                // var_dump($routeInfo);exit;
                // ... call $handler with $vars
                $this->getApp()->boot();//启动注入
                return $this->execute($routeInfo[1], $routeInfo[2]);
                break;
        }
    }

    /* 
     * 执行路由回调
     * $fn mix 回调资源
     * $params array 从路由中解析的参数数据
     * 
     */
    public function execute($fn, array $params)
    {
        // 数组型（也需要转为普通型处理）：中间件
        if(\is_array($fn)){
            $next = $this->paserMiddleWare($fn);
            $request =Request::mine($params);
            // var_dump($request);exit;
            // return $next($request);
            // array_unshift($params, $request);//将请求压入第一位
            // var_dump($params);exit;

            // 将 request 做为 路由参数 第一位
            \array_unshift($params, $request);

            return \call_user_func_array($next, $params);
        }
        // 普通型
        else{
            $callable = $this->getCallable($fn);
            return \call_user_func_array($callable, $params);
        }
    }

    // 返回请求资源 20201231123823
    public function getCallable($fn)
    {
        // 可回调型：闭包
        if(\is_callable($fn)) return $fn;

        // 字符串: 类+方法
        if( \is_string($fn) ){
            // 静态方法
            if( \strpos($fn, '::') ){

                // 返回说明：普通要求传入数组，中间件要求 Request
                return function(...$params){
                    // var_dump($params);
                    return \call_user_func_array($fn, $params);
                };
            }

            // 成员方法
            if( \strpos($fn, '@') ){
                $clsMethod = \explode('@', $fn);
                return function(...$params) use($clsMethod){
                    // var_dump($params);
                    return \call_user_func_array([new $clsMethod[0], $clsMethod[1]], $params);
                };
            }
        }

        throw new \Expception('非法的回调类型！');
    }


    // 解析中间件
    private function paserMiddleWare($middlewares_fn)
    {
        // 1. 取出要使用的所有中间件
        $middlewares = $middlewares_fn['middleware'] ?? [];
        // 2. 取出回调方法
        //  $next = end($middlewares_fn);
        $next = $this->getCallable(end($middlewares_fn));

        // 3. 注册中间件，并生成中间件栈
        foreach(\array_reverse($middlewares) as $middleware){
            $next = (new $middleware)->closure($next);
        }

        return $next;
        //   var_dump($next, $params);exit;

        // 4. 创建请求资源（用于中间件取数据）
        $request =Request::mine($params);
        // var_dump($request);exit;

        // 5. 执行 回调闭包（并触发中间件栈执行）
        return $next($request);
    //   $this->callFn();
    }

}