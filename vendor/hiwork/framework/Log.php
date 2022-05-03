<?php namespace hw;

use \Monolog\Logger;
use \Monolog\Handler\BrowserConsoleHandler;
use \Monolog\Handler\StreamHandler;
use \Monolog\Formatter\JsonFormatter;

/**
 * 日志
 * 20201230115001
 * lm: 20220503135652 分离处理器，单例调用
 * chy
 * 
 * 关于monolog
 * https://github.com/Seldaek/monolog/blob/2.x/doc/01-usage.md
 * 每一个日志服务实例 (Logger) 都有一个通道（名称）即：new Logger('framework') ，并有一个处理器 (Handler)栈。
 * 
 */

Class Log
{
	// 所有日志记录器
	private static $loggers=[];

	// 用户在程序中调用使用此方法
	public static function getlogger($loggerType='console', $loggerName='developer')
	{
		// 日志记录器名称：频道+日志类型
		$logKey = $loggerName.'+'.$loggerType;

		// 创建 日志（频道）实例
		if(!isset(self::$loggers[$logKey])){
			// 创建（频道）实例
			self::$loggers[$logKey] = new Logger($loggerName);

			// 加入处理器栈(不使用栈，均为单个处理器)
			if(!!$loggerType){
				$loggerType .= 'Dealer';
				self::$loggerType(self::$loggers[$logKey]);
			}
		}

		// 返回 日志（频道）实例
		return self::$loggers[$logKey];
	}


	// console日志记录器 20201230104118
	private static function consoleDealer(&$monologger)
	{
		//以json输出，且如果还有其它处理器，则允许冒泡
		$monologger->pushHandler(
			(new BrowserConsoleHandler(Logger::DEBUG, true))->setFormatter(new JsonFormatter())
		);
	}

	// file记录器 20201230104118
	private static function fileDealer(&$monologger)
	{
		//如还有其它处理器，则允许冒泡
		$monologger->pushHandler(
			new StreamHandler(
				self::logFilePath($monologger->getName().'-'.\date('Y-m-d')), 
				Logger::DEBUG,
				true 
			)
		);
	}


	// log文件地址 20210207125623
	private static function logFilePath($fileName=null)
	{
		$fileName  = $fileName ?? 'note-'.\date('Y-m-d');
		return \DOC_ROOT.'/run/logs/'.$fileName.'.log';
	}
	
}