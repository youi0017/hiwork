<?php namespace hw;

use \Monolog\Logger;
use \Monolog\Handler\BrowserConsoleHandler;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\JsonFormatter;

/**
 * 日志
 * 20201230115001
 * chy
 */

Class Log
{
	// 所有日志记录器
	private static $loggers=[];

	
	// 被充许的记录方法
	const allowMethods = [
		'DEBUG'=>1,
		'INFO'=>1,
		'NOTICE'=>1,
		'WARNING'=>1,
		'ERROR'=>1,
		'CRITICAL'=>1,
		'ALERT'=>1,
		'EMERGENCY'=>1,
	];


	/* 
	 * 日志处理器（对应某一频道的特定日志处理器）
	 * $loggerType string [选] 日志类型 console,file,null
	 * $loggerName string [选] 日志频道
	 * @return object 日志实例
	 * chy 20201230115610
	 */
	public static function logger($loggerType='console', $loggerName='running')
	{
		// var_dump($loggerType, $loggerName);exit;
		// 返回logger类型
		switch($loggerType){
			case 'console':{
				// var_dump('console', $loggerName, $loggerType);exit;
				return self::pushConsoleHandler($loggerName);
			}
			case 'file':{
				// var_dump('file', $loggerName, $loggerType);exit;
				return self::pushStreamHandler($loggerName);
			}
			case 'empty':
			default:{
				// var_dump('empty', $loggerName, $loggerType);exit;
				return self::pushNullHandler($loggerName);
			}
		}


	}
	
	/* 
	 * 取回 日志实例（某一频道的日志实例）
	 * $loggerName string [选] 日志频道
	 * @return object 日志实例
	 * chy 20201230115610
	 */
	private static function getLogger($loggerName)
	{
		// 创建 日志（频道）实例
		if(!isset(self::$loggers[$loggerName])){
			// \vendor('monolog/monolog');
			self::$loggers[$loggerName] = new Logger($loggerName);		
		}

		// 返回 日志（频道）实例
		return self::$loggers[$loggerName];
	}

	// console日志记录器 20201230104118
	public static function pushConsoleHandler($loggerName)
	{
		$logger = self::getLogger($loggerName);
		//以json输出且禁止冒泡
		$logger->pushHandler(
			(new BrowserConsoleHandler(Logger::DEBUG, false))->setFormatter(new JsonFormatter())
		);

		return $logger;
	}

	// file记录器 20201230104118
	public static function pushStreamHandler($loggerName)
	{
		$logger = self::getLogger($loggerName);
		// 添加handler
		self::getLogger($loggerName)->pushHandler(
			new StreamHandler(
				self::logFilePath($loggerName.'-'.\date('Y-m-d')), 
				Logger::DEBUG,
				false
			)
		);//且禁止冒泡
		
		return $logger;
	}

	// 增加 空记录器 20201230104118
	// 说明：空记录器是针对不需要 console 或 filelog的情况增加的特殊功能
	public static function pushNullHandler($loggerName)
	{
		return new self;
	}

	// 针对空记录器调用空方法 20201230114519
	public function __call($name, $arguments)
	{
		return true;
	}


	// 双日志（steam+console）记录 20201230105332
	// public static function pushBothHandler()
	// {
	// 	self::pushStreamHandler();
	// 	return self::pushConsoleHandler();
	// }


	// log文件地址 20210207125623
	public static function logFilePath($fileName=null)
	{
		$fileName  = $fileName ?? 'note-'.\date('Y-m-d');
		return \DOC_ROOT.'/run/logs/'.$fileName.'.log';
	}


/* 
	public static function __callxxx($name, $arguments)
	{
		$name = \strtoupper($name);

		// var_dump($name, self::allowMethods);exit;

		if(isset(self::allowMethods[$name])){
			\call_user_func_array( [self::getLogger(), $name], $arguments );
		}
		else{
			throw new \Exception('不存在的方法：logger::'.$name);
		}
	}
 */
	
}