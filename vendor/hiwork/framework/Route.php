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

    // 解析路由 20210221154756
    public function parseRoute(array $routeFiles)
    {
        //1. 路由实例
        $router = new \hw\librarys\AltoRouter();

        //2. 载入路由文件
        foreach($routeFiles as $f){
            \is_file($f)
                ? include($f)
                : logger()->error('路由不存在：'.\basename ($f));
        }

        // 4.得到路由的解析结果
        $match = $router->match();
        // var_dump($match);//调试输出：当前匹配

        // 5. 结果分派
        if( isset($match['target']) ) {
            // 将路径信息(字串)写入环境变量
            \env('uri', $match['uri']);

            // 注意：有错则由系统错误接管，此处不用处理
            // try{

                // 对controller@action形式，路由未提供解析，需由自己完成20220502124104
                if( is_callable($match['target'])==false && strpos($match['target'], '@' )){
                    list( $controller, $action ) = explode( '@', $match['target'] );
                    $match['target']=[new $controller, $action];
                }
                
                $this->getApp()->boot();//启动前注入

                echo call_user_func_array( $match['target'], $match['params'] ); 
            // }
            // catch( \Throwable $e){
            //     var_dump('route paser error!!!!');
            //     var_dump($e);
            // }
        } 
        else {
            Rtn::epage(404, "[404] 受访页面不存在 : {$match['method']} {$match['uri']}");
        }

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
        
        //3. uri合法化并写入env
        $uri = preg_replace('/\/{2,}/', '/', $uri);
        \env('uri', $uri);

        //3. 解析为uriPath
        $uriArr = explode('/', \rawurldecode($uri));
        
        // 4. 取回ctl和act
        $ctl =  empty($uriArr[1]) ? 'Index' : \ucfirst($uriArr[1]);
        $ctl = '\app\\'.\env('APP_NAME').'\controllers\\'.$ctl;
        $act =  empty($uriArr[2]) ? 'index' : $uriArr[2];
        // var_dump($uri, $uriArr, $ctl, $act);//exit;

        // 5. 执行
        if(method_exists($ctl, $act)){
            echo \call_user_func_array([new $ctl, $act], \array_slice($uriArr, 3));
        }
        else {
            Rtn::epage(404, "[404] 受访页面不存在 : {$uri}");
        }
        
    }
    
}