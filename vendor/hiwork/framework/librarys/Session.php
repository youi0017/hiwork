<?php namespace hw\librarys;

/* 
 * 会话session
 * 20180606
 */

class Session
{
	use \hw\Staticer;
	
	protected $skey;
	
	//取得session_id 20180725
	public static function sid()
	{		
		if(!isset($_SESSION)) session_start();
		return session_id();
	}

	//取值 20180725
	//session::GET('uid');
	public static function get($key)
	{
		self::sid();
		return $_SESSION[$key] ?? null;
	}

	//设置 20180725
	//session::SET('uid', 'chy2019');
	public static function set($key, $val)
	{
		self::sid();
		$_SESSION[$key]=$val;
		return $val;
	}

	//删除 20180725
	//session::DEL('uid');
	public static function del($key)
	{
		self::sid();
		unset($_SESSION[$key]);
	}	
	
	
	//清空session,慎用 20180725
	public static function clear()
	{
		self::sid();
		session_destroy();
	}
	
}
