<?php namespace hw\librarys;

/*
	会话应用示例：
	第一步：发送验证码时，标记一个验证码的会话：
		$code = Cookie::mk(rand(1000, 9999));//生成4位验证码
	第二步：验证用户填入的验证码：
		Cookie::yz($_GET['code']);

*/

class Cookie
{
	use \hw\traits\Singlet;

	protected $ckey;//cookie名称

	//注意：HHK建议修改，任意值15位以下的字串
	public const HHk='vtpsesskey';


	//通过索引取cookie值 20180725
	public static function get(string $ckey)
	{
		return $_COOKIE[$ckey] ?? null;
	}

	/**
	 * 设置cookie值 20180725
	 * $ckey [必] string cookie名 
	 * $value [选] string cookie值，默认空表删除ck, 其它为值
	 * $time [选] int 有效期，默认：time=0表示关闭浏览器失败, 其它为有效秒数
	 * 
	 * 示例：
	 * 设置关闭浏览器失效：self::SET('uid', '1005');
	 * 设置1小时有效：self::SET('uid', '1005', 3600);
	 * 删除：self::SET('uid');
	 * 
	 */
	public static function set($ckey, $value=false, int $time=0)
	{
		$time = $time===0 ? null : \time()+$time;
		\setcookie($ckey, $value, $time, '/');
		return $value;
	}

	//删除cookie 20190522
	public static function del($ckey)
	{
		setcookie($ckey, false, -100, '/');
	}
	

	/*
	 * cookie会话应用：标记会话
	 * $val mix [选] 会话值
	 * $time int [选] 会话时间,默认10分钟有效
	 * $hhk mix [选] 会话名称，为空
	 * return 会话的值
	 */
	public static function mk($val=null, $hhk=null, $time=600)
	{
		if($hhk===null) $hhk=self::key();
		if($val===null) $val=time();
		return self::SET($hhk, $val, $time);
	}


	/*
	 * cookie会话应用：验证会话
	 * 说明：验证会话是通过传入的会话值与标记的会话值进行比较
	 * $val [必] string 要验证的会话值
	 * $hhk [选] string 会话key
	 * 20200419192920
	 */
	public static function yz($val, $hhk=null)
	{
		if($hhk===null) $hhk=self::key();
		$v = self::get($hhk);

		//验证会话
		//会话值$cVal  与 传入值$val 的比较
		$b = $v===null ? false : ($v==$val);
		
		//验证成功后清除（默认在10分内有效）
		if($b) self::DEL($hhk);
		//返回验证结果
		return $b;
	}

	
	/* 
	 * cookie会话应用：生成会话标记
	 * 说明：生成会话ck的套嵌key
	 * 20190802
	 */
	public static function key()
	{
		$ckey = self::get(self::HHk);
		if($ckey===null)
		{
			$ckey = self::set(self::HHk, substr(base64_encode(time()), 0, rand(7,9)));
		}

		return $ckey;
	}

}


