<?php
// 模板视图 辅助函数
function view() {
    return \hw\View::mine();  
}

// 模板视图 辅助函数
function db() {
    return \hw\librarys\DB6::mine();  
}

// 抛出错误 （可能存在错误栈不准的情况20210102174642）
function error($msg)
{
    throw new \ErrorException($msg);
}


// 抛出错误 （可能存在错误栈不准的情况20210102174642）
function request()
{
    return \hw\Request::mine();
}


/* 
 * 日志输助函数
 * 20210104112657
 * 示例：
 * logger('console')->info('这是一个测试信息');
 */
function logger($loggerType='console', $loggerName='running')
{
    return \hw\Log::logger($loggerType, $loggerName);
}

// 公共包加载器 辅助函数
function vendor($packName) {
    // var_dump( $_SERVER['DOCUMENT_ROOT']);exit;
    require_once  $_SERVER['DOCUMENT_ROOT'].'/../vendor/'.$packName.'/vendor/autoload.php';
}

// 取/设环境值 20210106153454
function env($key, $val=null)
{
    if(\is_null($val)){

        if(\getenv('APP_NAME')==false){
            // 读取环境配置文件
            $env = load(DOC_ROOT.'/.env');

            // 如果没有环境配置文件，则使用默认设置
            if(!$env){
                $env = [
                    'APP_NAME'=>'sample',
                    'APP_DEBUG'=>1,
                    'APP_FILELOG'=>'file',
                    'APP_TIMEZONE'=>'PRC',
                ];
            }

            foreach($env as $k =>$v){
                \putenv($k.'='.$v);
            }
        }

        return \getenv($key);
    }
    else{
        \putenv($key.'='.$val);
    }
}


// 
/* 
 * 取回服务配置数据
    注：
    1. 框架的服务（数据库/短信/...）的配置均在/APP目录下；
    2. 项目配置 > 公共配置；
 */
function config($configName)
{
    return 
        load(\APP_ROOT."/{$configName}.php") 
        + 
        load(\APP_ROOT."/../{$configName}.php");
}

/*
 * 载入配置文件
 * @access public
 * @param  string $file 配置文件名
 * @param  string $name 一级配置名
 * @return array
 */
function load($file)
{
    if(!is_file($file)) return [];

    $type   = pathinfo($file, \PATHINFO_EXTENSION);
    $config = [];
    switch ($type) {
        case 'php':
            $config = include $file;
            break;
        case 'yml':
        case 'yaml':
            if (\function_exists('yaml_parse_file')) {
                $config = \yaml_parse_file($file);
            }
            break;
            case 'env':
            case 'ini':
                $config = \parse_ini_file($file, true, \INI_SCANNER_TYPED) ?: [];
            break;
        case 'json':
            $config = \json_decode(\file_get_contents($file), true);
            break;
    }
    return $config;
}












