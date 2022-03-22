<?php namespace hw\librarys;

/*
 * 远程采集 curl
 * chy
 * 20150101 LM:20170716
 */

class Curl
{
	use \hw\traits\Singlet;


	// 取得受访用户IP 20201022123536
	public static function getClientIP()
	{
		return $_SERVER["HTTP_CLIENT_IP"]
				?? $_SERVER["HTTP_X_FORWARDED_FOR"]
				?? $_SERVER["REMOTE_ADDR"]
				?? null;
	}


	/* 
	 * 
	 * $url 受访url
	 * $ip 用于访问的IP(伪造ip), 一般为当前访客IP
	 * $refererUrl 用于访问的url(伪造url),一般为受访问的url
	 * $params array 用于受访url的参数,一般直接拼接在url中
	 * 20201022124000
	*/
	public function get($url, $ip=null, $refererUrl=null, $params=[])
	{
		// 当前访客IP
		if(!$ip) $ip=self::getClientIP();

		// 合成header
		$headers=[
			'CLIENT-IP:'.$ip,
			'X-FORWARDED-FOR:'.$ip,
			'Referer:'.($refererUrl ?? $url),
		];

		// var_dump($headers);exit;
		
		// curl
		$ch = \curl_init();
		\curl_setopt($ch, CURLOPT_URL, $url);
		
		//https处理
		if(substr($url, 0, 8)=="https://") {
			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
			\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证域名
		}

		\curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);//设置超时时间为10秒
		\curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);//在header中伪装ip/url
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//禁止直接显示获取的内容
		//\curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//禁止自动跳转

		
		$r=trim(\curl_exec($ch));
		\curl_close($ch);
		//根据结果类型返回数据
		return $r;
	}

	/*
	 * curl主采集程序
	 * @param $url: 远程URL
	 * @param $rType: 返回数据的类型（对类型进行处理，见dealRtn()方法）
	 * @param $isHttpCode bool 以httpCode返回 为兼容get_httpCode()方法
	 * 20170716
	 */
	public function exc($url, $rType='text', $isHttpCode=false)
	{
		$ch = \curl_init();
		\curl_setopt($ch, CURLOPT_URL, $url);
		
		//https处理
		if(substr($url, 0, 8)=="https://") {
			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
			\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证域名
		}

		\curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);//设置超时时间为10秒
		\curl_setopt($ch, CURLOPT_HTTPHEADER, array("Referer: $url"));//伪装地址
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//禁止直接显示获取的内容
		//\curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//禁止自动跳转

		//根据状态码控制输出类型
		if($isHttpCode){
			\curl_setopt($ch, CURLOPT_NOBODY, 1);//不返回主体内容
			\curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET'); //不加这个会返回403，加了才返回正确的200，原因不明 

			// 设置访问者（20220105112132）
			$UserAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
			\curl_setopt($curl, CURLOPT_USERAGENT, $UserAgent);

			\curl_exec($ch);
			$r = \curl_getinfo($ch, CURLINFO_HTTP_CODE);//状态码
			\curl_close($ch);
			return $r;
		}
		else{
			$r=trim(\curl_exec($ch));
			\curl_close($ch);
			//根据结果类型返回数据
			return $this->dealRtn($r, $rType);
		}

	}

	/*
	 * 返回请求url的状态码
	 * 20170716
	 */
	public function get_httpCode($url)
	{
		return $this->exc($url, 'text', true);
	}


	/*
	 * 以post发送数据
	 * 注：url必需为完整url参数，否则错误
	 * 
	 * 示例：
		$b = \lib\curl::curler()->postData('http://cshost.com/getPost', ['a'=>'a1', 'b'=>'b1']);

	 * 20170426
	 */
	public function postData($url, $data)
	{
		if (is_array($data))
    {
        $data = http_build_query($data, null, '&');
    }
 
    $ch = \curl_init();
    \curl_setopt($ch, CURLOPT_POST, 1);
    \curl_setopt($ch, CURLOPT_URL, $url);
    \curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = \curl_exec($ch);
    $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);
    \curl_close($ch);
 
	return array($httpCode, $response);
	
	
		if(empty($data)) return false;
		
		$ch = \curl_init();
		\curl_setopt($ch, CURLOPT_URL, $url);
		
		//https处理
		if(substr($url, 0, 8)=="https://")
		{
			\curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
			\curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证域名
		}

		//包含user-agent头字串
		\curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		//自动设置header中的referer信息
		\curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		//post发送数据
		//\curl_setopt($ch, CURLOPT_POST, 1);
		\curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // \curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        \curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		//设置其它项
		\curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);//设置超时时间为10秒
		//\curl_setopt($ch, CURLOPT_HTTPHEADER, array("Referer: $url"));//伪装地址
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//禁止直接显示获取的内容
		//\curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//禁止自动跳转

		//执行并返回
		$r = \curl_exec($ch);
		if(\curl_errno($ch)) return \curl_error($ch);
		else
		{
			\curl_close($ch);
			return $r;
		}
	}


	/*
	 * 处理curl结果
	 * @param $rCurl: curl结果
	 * @param $rType string 类型控制码
	 	注：rType是curl的结果的类型 text:直接输出为string, json:对json解码, xml:将xml转为xmlObj
	 * return rType对应的结果
	 	注：当$rCurl==''时，curl未取得数据,返回假类型的值，此时：text:'', json:null, xml:false
	 * 
	 */
	private function dealRtn($rCurl, $rType='text')
	{
		switch($rType)
		{
			case 'text': return $rCurl;
			case 'object': return json_decode($rCurl);
			case 'array': return json_decode($rCurl, true);
			case 'xmltoarray': return simplexml_load_string($rCurl, 'SimpleXMLElement', LIBXML_NOCDATA);
			default: return $rCurl;
		}		
	}
	
}
