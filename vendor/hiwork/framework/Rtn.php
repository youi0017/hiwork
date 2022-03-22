<?php namespace hw;

/*
 * @author chy
 * 返回库 20160301
 * 20190601
 	1.将Err中对错误的处理全部转移到Rtn中
 	2.加入_RTN方法：作为内部通用ajax请求返回方法
 	3.对返回的结果状态的判断用header中的status判断，结果用string或json自动根据传入的数据返回
 * 20200528175624 
	1. 修改err返回的状态码为200
	2. 增加setHttpCode方法，用于生成header中的httpCode
 *  
 * 
 */

class Rtn
{
/* 阻断返回：简单结果输出 */
    /*
     * 类静态函数，直接返回TRUE的快键方式
     * @param mix $data 要显示的数据：复合量值会被转为json
     * @return Result Result对象
 	示例：
     	Rtn::OKK(['say'=>'okkk']);
		Rtn::ERR(['say'=>'errr']);
		Rtn::OKK('you are ok');
		Rtn::ERR('catch error');
     */    
	public static function okk($msg='okk', $data='', $code=200)
	{
		self::_rtn(1, $code, $msg, $data);
	}

	public static function err($msg='err', $data='', $code=200)
	{
		self::_rtn(0, $code, $msg, $data);
	}

	//返回的公共方法 20190601
	protected static function _rtn($stat, $code, $msg, $data)
	{
		// 设置httpCode
		$code=self::setHttpCode($code);
		
		if($data==='TEXTHTML') {
			header('Content-Type: text/html');
			exit($msg);
		}
		else {
			header('Content-Type: application/json');
			exit(json_encode([
				'msg'=>$msg,
				'data'=>$data,
				'code'=>$code,
				'stat'=>$stat,
				'rtime'=>time(),
			], \JSON_UNESCAPED_UNICODE|\JSON_NUMERIC_CHECK ));
		}
	}


	//生成 header中http状态码 20200528173058
	public static function setHttpCode($code=200)
	{
		$httpCode = self::httpEnCode();
	    if(!isset($httpCode[$code])) $code=200;

	    // 发出header头信息
		header('HTTP/1.1 '.$code.' '.$httpCode[$code]);
		return $code;
	}

	// http 英文code
	public static function httpEnCode()
	{
		return [
		    // Informational 1xx
		    100 => 'Continue',
		    101 => 'Switching Protocols',
		    // Success 2xx
		    200 => 'OK',
		    201 => 'Created',
		    202 => 'Accepted',
		    203 => 'Non-Authoritative Information',
		    204 => 'No Content',
		    205 => 'Reset Content',
		    206 => 'Partial Content',
		    // Redirection 3xx
		    300 => 'Multiple Choices',
		    301 => 'Moved Permanently',
		    302 => 'Moved Temporarily ',
		    303 => 'See Other',
		    304 => 'Not Modified',
		    305 => 'Use Proxy',
		    // 306 is deprecated but reserved
		    307 => 'Temporary Redirect',
		    // Client Error 4xx
		    400 => 'Bad Request',
		    401 => 'Unauthorized',
		    402 => 'Payment Required',
		    403 => 'Forbidden',
		    404 => 'Not Found',
		    405 => 'Method Not Allowed',
		    406 => 'Not Acceptable',
		    407 => 'Proxy Authentication Required',
		    408 => 'Request Timeout',
		    409 => 'Conflict',
		    410 => 'Gone',
		    411 => 'Length Required',
		    412 => 'Precondition Failed',
		    413 => 'Request Entity Too Large',
		    414 => 'Request-URI Too Long',
		    415 => 'Unsupported Media Type',
		    416 => 'Requested Range Not Satisfiable',
		    417 => 'Expectation Failed',
		    // Server Error 5xx
		    500 => 'Internal Server Error',
		    501 => 'Not Implemented',
		    502 => 'Bad Gateway',
		    503 => 'Service Unavailable',
		    504 => 'Gateway Timeout',
		    505 => 'HTTP Version Not Supported',
		    509 => 'Bandwidth Limit Exceeded',
		];

	}


	// http 中文code
	public static function httpZhCode()
	{
		return [
		    // Informational 1xx
		    100 => '继续',
		    101 => '交换协议',
		    // Success 2xx
		    200 => '成功',
		    201 => '已创建',
		    202 => '已接受',
		    203 => '非授权信息',
		    204 => '无内容',
		    205 => '重置内容',
		    206 => '部分内容',
		    // Redirection 3xx
		    300 => '多种选择',
		    301 => '永久移动',
		    302 => '临时移动',  // 1.1
		    303 => '查看其他位置',
		    304 => '未修改',
		    305 => '使用代理',
		    // 306 is deprecated but reserved
		    307 => '临时重定向',
		    // Client Error 4xx
		    400 => '错误请求',
		    401 => '未经许可的',
		    402 => '付费内容',
		    403 => '禁止',
		    404 => '未找到',
		    405 => '方法禁用',
		    406 => '不接受',
		    407 => '需要代理授权',
		    408 => '请求超时',
		    409 => '冲突',
		    410 => '已删除',
		    411 => '需要有效长度',
		    412 => '未满足前提条件',
		    413 => '请求实体过大',
		    414 => '请求的 URI 过长',
		    415 => '不支持的媒体类型',
		    416 => '请求范围不符合要求',
		    417 => '未满足期望值',
		    // Server Error 5xx
		    500 => '服务器内部错误',
		    501 => '尚未实施',
		    502 => '错误网关',
		    503 => '服务不可用',
		    504 => '网关超时',
		    505 => 'HTTP 版本不受支持',
		    509 => '服务器达到带宽限制',
		];

	}

    //== js执行的静态方法 =======================================
    //输出js代码
    public static function jscript($js_code='')
    {
        if(!$js_code) $js_code='alert("JS_STR is null!")';

        exit('<script type="text/javascript">'.$js_code.'</script>');
    }
    
    //输出js调试值
    public static function jslog($jsVar)
    {
        exit('<script type="text/javascript">console.log("'.$jsVar.'")</script>');
    }



//== 返回错误页 =============================================

    /*
     * 返回一个信息页面(仅仅是一条信息)
     * 注:可用于404页面的返回,或直接做为路由的404页面
     * 
     * 20200411155457
     */
    public static function warning($msg='404, 您访问的页面不存在', $code=404)
	{
		self::err("<h2 style='color:brown; padding:1em; border:1px solid #a5b6c8; background-color:#eef3f7;'>
			{$msg}</h2>", 'TEXTHTML' , $code);
	}


	/*
	 * 返回一个信息页面(含有栈调用信息)
	 *
	 * 注:因含有相对敏感的信息,不建议使用
	 * 
	 * 示例:
	   在控制器 index-sherr 中调用 Rtn::alert(); 输出以下内容
	   输出错误：程序因故停止！ At Index-shErr:42

	 * 20200411155701
	 */
	public static function alert($msg='程序因故停止！', $code=403)
	{
		// 取回溯跟踪,: 不取参数,且只取到第一层
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2);
		// var_dump($trace);exit;

		$file=basename($trace[1]['class'] ?? $trace[1]['file'] ?? 'X');
		$func=basename($trace[1]['function'] ?? 'X' );
		$line=$trace[0]['line'] ?? 'X';
		$msg.=" At {$file}-{$func}:{$line}";

		self::warning($msg, $code);
	}	


	// http错误页 20210105144842
	public static function epage($code=404, $msg='')
	{
		switch($code){
			case 404: 
				if(!$msg) $msg = $code.'，受访页面不存在！';
				break;
			case 403: 
				if(!$msg) $msg = $code.'，禁止访问！';
				break;
			case 405: 
				if(!$msg) $msg = $code.'，请求方式错误！';
				break;
			case 500:
				if(!$msg) $msg = $code.'，内部服务器错误，请稍后再试！';
				break;
			default:
				if(!$msg) $msg=$code.'，未知错误，正在处理中...';
				break;
		}
	
		self::err("<h2 style='color:brown; padding:1em; border:1px solid #a5b6c8; background-color:#eef3f7;'>
			{$msg}</h2>", 'TEXTHTML' , $code);

	}
    
}
