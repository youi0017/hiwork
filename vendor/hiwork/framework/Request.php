<?php namespace hw;

/* 
 * 请求
 * chy 20201231112516
 * 
 * 示例：
 * //以整型取uid
		$uid=$rqt->get('uid', 'int');
		//以整型取age,且要求9到24之间
		$age = $rqt->get('age', 'int',[
				// 'default'=>20,
				'min_range'=>9,
				'max_range'=>40,
		]);
		//以邮箱格式取mail
		$mail = $rqt->get('mail', 'email');
		//以正则匹配usr
		$usr=$rqt->get('usr', 'regexp', [
			'regexp'=>'/^[a-zA-Z]\w{5,30}$/'
		]);
		// 通过回调函数取idx[]
		$idx=$rqt->get('idx', 'func',['options'=>function($v){
			return $v<1 ? null : $v;
		}]);
 * 
 */

class Request
{
    // 接口扩展（实现单态模式）
    use \hw\traits\Singlet;

    private $routeParams = [];
    private static $inputs;

    /* 
     * $routeParams array [选] 从路由中解析出的数据数组
     */
    public function __construct(array $routeParams=[])
    {
        $this->routeParams = $routeParams;
    }


    // 取从路由中解析的数据 20201231105853
    public function param($key)
    {
        return $this->routeParams[$key] ?? null;
    }

    // 取上传文件
    public function file($key)
    {

    }


    public function get($key, $type='string', $options=[])
    {
        // return $_GET[$key] ?? null;
        return self::validateFilter($_GET, $key, $type, $options) ?? null;
    }

    public function post($key, $type='string', $options=[])
    {
        // return $_POST[$key] ?? null;
        return self::validateFilter($_POST, $key, $type, $options) ?? null;

    }

    /* 
     * 取 input流数据
     * $key 索引 不传表达取回所有数据
     * 20201230152633
     */
    public function input($key='', $type='string', $options=[])
    {
        // 取回流数据
        if(!self::$inputs){
            self::$inputs = \json_decode(
                \file_get_contents('php://input'),
                true
            );
        }

        return 
            !$key 
            ? self::$inputs 
            : (self::validateFilter(self::$inputs, $key, $type, $options) ?? null);
    }



    
    // 当前请求方法
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }



    
    /**
     * 基础验证器
     * $key [必] 索引
     * $dataType  [选] 取值方式 get,post,cookie,server,env
     * $validateType [选] 验证器类型 默认字串
     * $options [选] 验证控制项
     * 20210118164332
     */
    public static function validateFilter($data, $key, $validateType='string', $options=[])
    {
        if(!isset($data[$key])) return null;

        if(!isset($options['options']))
            $options['options']=$options;

        $v=\filter_var(
            // \constant('INPUT_'.\strtoupper($dataType)),
            // $dataType,
            // $key,
            $data[$key], 
            self::_getFilter($validateType), 
            $options
        );

        return $v===false || $v===null ? null : $v;
    }

    // 取得验证器常量 20200307101547
	public static function _getFilter($type='string'){
		//类型标记转为小写
		$type = strtolower($type);

		//类型分派
		switch ($type) {
            case 'int':
                return \FILTER_VALIDATE_INT;
            case 'boolean':
                return \FILTER_VALIDATE_BOOLEAN;
            case 'float':
                return \FILTER_VALIDATE_FLOAT;
            case 'ip':
                return \FILTER_VALIDATE_IP;
            case 'email':
                return FILTER_VALIDATE_EMAIL;
            case 'url': 
                return \FILTER_VALIDATE_URL;
            case 'regexp': 
                return \FILTER_VALIDATE_REGEXP;
			
            case 'func': 
                return \FILTER_CALLBACK;

			// 字串则为净化, 编码特殊字符（???此处不确定）
            default: 
                return \FILTER_SANITIZE_STRING;
		}

	}




}