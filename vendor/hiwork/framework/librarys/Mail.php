<?php namespace hw\librarys;

use \hw\Rtn;

/**
 * 邮件库 swiftmailer
 * PHPMailer-master 20170103版
 * 20190517 v1.0 由PHPMailer切换为swiftmailer
 * 
 * 	需手动传入邮箱的配置信息(20210426163056)，如：
		$mailConfig = [
			'host'=>'smtp.qq.com',//普通qq帐户使用：smtp.qq.com
			'port'=>'465',//ssl发信服务器端口
			'usr'=>'abc@qq.com',//发信人地址
			'pwd'=>'16位授权码',
			'nick'=>'admin',
			'debug'=>1,
		];
 * 
 */

class Mail
{
	use \hw\traits\Singlet;

	private $cnf;
	//记录错误
	private $emsg;


	public function __construct(array $mailConfig)
	{
		$this->cnf =  $mailConfig;
	}

	/**
	 * $rUsr 接收者 array 如：[ 'xiaowang@126.com', 'xiaoli@qq.com' => '小李']
	 * $tit 标题 string
	 * $body 邮件主体 string，可以是html
	 * 
	 * 20190517
	 */
	public function sendMail($rUsr, $tit, $body)
	{
		// vendor('swiftmailer-swiftmailer');

		//创建连接
		$transport = (
			new \Swift_SmtpTransport(
				$this->cnf['host'],
				$this->cnf['port'],
				'SSL'
			)
		)
		->setUsername($this->cnf['usr'])
		->setPassword($this->cnf['pwd'])
		;

		//创建邮件类
		$mailer = new \Swift_Mailer($transport);

		//填补内容
		$msg = (new \Swift_Message($tit))
			->setFrom([$this->cnf['usr'] => $this->cnf['nick']])
			->setTo($rUsr)
			->setBody($body)
			;
			
		try{
			$b = $mailer->send($msg);
			return true;
		}
		catch (\Throwable $e){
			// var_dump($e);exit;
			$this->emsg=$e->getMessage();
			\logger('file', 'error')->error('发信错误：'.$this->emsg);
			return false;
		}

	}

	//取回错误信息
	public function getErr()
	{
		return $this->emsg;
	}


}