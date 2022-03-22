<?php namespace hw;

use \hw\Rtn;
use \hw\Log;

/*
 * 框架的错误处理库
 * 本类 拦截 notice、warning等try-catch拦截不了的错误，并提供了处理方法，但应注意，本类不作逻辑处理（交由bootstrap）
 */

class HwException
{

	/**
	 * 错误入口: 注册错误机制
	 * 注册错误/异常语柄
	 * 20210207125043
	 */
	public static function exc()
	{
		// 报告所有错误，但控制显示
        \error_reporting(\E_ALL);
		\ini_set('display_errors', 'off');//off on
		// \ini_set('log_errors', 'On');
		// \ini_set('error_log',  Log::logFilePath());

		// 异常处理接管(包括下面的 set_error_handler转化的错误异常)
		set_exception_handler(self::class.'::dealExpt');
		// 捕获错误(change error to ErrorException)
		set_error_handler(self::class.'::dealError');
		// 捕获其它未被捕捉或停止的
		register_shutdown_function(self::class.'::otherErrToExpt');
	}

	/* 
	 * 生成Exception后，按异常处理  
	 * 可处理的错误有：notice、warning等
	 * 20201225131316 chy
	 */
	public static function dealError($errCode, $errMsg, $file, $line)
	{
		self::dealExpt(
			new \ErrorException(
				$errMsg, 
				$errCode, 
				$errCode, 
				$file, 
				$line
			)
		);
	}


	/**
	 * 系统捕获的(错误或异常)的控制(显示与日志)与分发总入口 
	 * 注：所有的异常被自动捕获后，均在此处理 20190616
	 * 注1：此方法不用显式调用，self::register已绑定过，有错误时被自动调用
	 * 注2：a.有错误时，此方法作为错误的处理方法，接管错误
	 * 		b.有异常，且未被捕捉时，自动执行本方法
	 * 20190816115117
	 * lm:20220301100546 加入\header('Access-Control-Allow-Origin: *');
	 *
	 * 能捕获的错误有（持续更新）：
	 * 1. 语法错误 20200326113507
	 * 2. 不存在的函数 20200326113509
     */

	//控制错误:20190816114454
	public static function dealExpt($expt)
	{
		\header('Access-Control-Allow-Origin: *');
		// 日志：console/file/empty
		// var_dump(\env('APP_FILELOG'));exit;
		logger(\env('APP_FILELOG'), 'error')->error('捕获错误', [
			'message'=>$expt->getMessage(),
			'line'=>$expt->getLine(),
			'file'=>$expt->getFile(),
			'uri'=>$_SERVER['REQUEST_URI'],
			// 'stack'=>(string)$expt,
		]);
		// var_dump($expt);return;

		// 调试页，develop:详细错误信息  product:简文字
		exit(
			env('APP_DEBUG')
				? self::debugPage($expt)//develop
				// : exit(new HttpError(500))//product
				: Rtn::epage(500)//product
		);

	}


	/* 
	 * 调试视图 
	 * @expt 抛出的异常对象
	 * @return 以视图返回调试页面
	 * 20201225142946
	 */
	public static function debugPage($expt)
	{
		header('HTTP/1.1 500 Internal Server Error');

		// var_dump($expt);exit;
		// Rtn::setHttpCode(500);

		//注意：抛出异常的位置往往不是真正错误的位置，故使用 trace[0]做为信息来源 20201225124147
		// $trace = $expt->getTrace()[0];
		// var_dump($expt::class);exit;

		if($expt instanceof \ErrorException){
			$trace = $expt->getTrace()[0];
		}
		
		$data=[
			'message'=>$expt->getMessage(),
			'line'=>$trace['line'] ?? $expt->getLine(),
			'file'=>$trace['file'] ?? $expt->getFile(),
			// 'line'=>$expt->getLine(),
			// 'file'=>$expt->getFile(),
			'stack'=>(string)$expt,
		];
		$data['line0'] = $data['line']<11 ? 1 : ($data['line']-10);
		// var_dump($data);exit;

		//1. 定位错误文件，并读取上下各10行
		$data['lines'] = self::_getFileLines(
			$data['file'],
			$data['line0'],
			$data['line']+10
		);

		// var_dump($data);exit;
		//2. 解析数据并载入视图		
		return view()
			->assign($data)
			->display('debuger_page.phtml', true);	
	}


	/** 返回文件从X行到Y行的内容(支持php5、php4)  
	 * @param string $filename 文件名
	 * @param int $startLine 开始的行数
	 * @param int $endLine 结束的行数
	 * @return string
	 */
	public static function _getFileLines($filename, $startLine = 1, $endLine=50, $method='rb')
	{
		$r = [];
	    $count = $endLine - $startLine;  

	    $fp = new \SplFileObject($filename, $method);
	    $fp->seek($startLine-1);// 转到第N行, seek方法参数从0开始计数
	    for($i = 0; $i <= $count; ++$i) {
	        $r[]=$fp->current();// current()获取当前行内容
	        $fp->next();// 下一行
	    }

	    return array_filter($r); // array_filter过滤：false,null,''
	}


	
	/*
	 * Fatal error 捕获与处理: 
	 * 注意：因为使用 shut 捕获，所以exit也会激发此函数
	 *
	 * 能捕获的错误有（持续更新）：
	 * 1. 函数重名 20200326113502
	 **/
	public static function otherErrToExpt()
	{
		$error = \error_get_last();
		/* 
		var_dump($error, new \ErrorException(
			$error['message'], 
			$error['type'], 
			$error['type'], 
			$error['file'], 
			$error['line']
			)
		);exit;
 		*/
		 
	   	if($error) {
			exit(
				self::dealExpt(
					new \ErrorException(
						$error['message'], 
						$error['type'], 
						$error['type'], 
						$error['file'], 
						$error['line']
					)
				)
			);

			// throw  new \ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
	    }

	}

}



	