<?php namespace hw;

use \hw\error\OwnException;

/**
 * 模版视图的赋值与解析
 * 注：kernel\trait\View不再使用，控制器中也不再多向继承 20200528171810
 * chy
 */

Class View
{
	use \hw\traits\Singlet;

	// 视图地址
	protected $tplSite;

	// 当前视图数据
	protected $dArr=[];

	// 公共视图数据
	private static $shareData=[];

	// 禁止直接实例化（即必需使用单态模式）
	private function __construct(){}

	/*
	 * 解析视图
	 * 完成内容：
	 	1. 导出数组变量
	 	2. 加载视图
	 	
	 	注意：多次调用 display可能会由于多次爆开数据导致内存升高
	 * 20190519
	 * lm: 20210102154858 使用缓冲，不直接输出内容
	 */
	public function display($tpl, $isSysView=false) 
	{
		//1. 取回视图路径
		$fsite = self::exists($tpl, $isSysView);
		// var_dump($fsite);exit;

		
		if(!$fsite)
			// error("视图文件 {$tpl} 不存在！");
			throw new \ErrorException("视图文件 {$tpl} 不存在！");
		
		// 2. 解析视图
		$parseView = function($data) use($fsite){
			// 展开数据
			\extract($data, \EXTR_OVERWRITE);

			// 缓冲输出内容
			\ob_start();
			include $fsite;
			$html = \ob_get_contents();
			\ob_end_clean();
			return $html;
		};

		return $parseView($this->dArr+self::$shareData);
	
	}

	
	/*
	 * 模版个次数据赋值
	 * 
	 * 说明：
	 	$key为数组：要解析的所有变量和值，以索引和值进行映射
	 	$key为字串：此时$vals将作为变量名，$v作为值
	 * 
	 * 20190519
	 */
	public function assign($key, $value=null)
	{
		if(is_array($key)){
			$this->dArr = $key+$this->dArr;//新值代旧值 
		}
		else if(is_string($key)){
			$this->dArr[$key]=$value;
		}
		
		return $this;
	}



	// 判断视图文件是否存在
	// return fsite | false
	// 20210102161656
	public static function exists($tpl, $isSysView)
	{
		$fsite = self::fsite($tpl, $isSysView);  
		return \is_file($fsite) ? $fsite : false;
	}


	/**
	 * 返回模版文件路径
	 * 20190519
	 */
    public static function fsite($file, $isSysView=false)
    {

		return \DOC_ROOT.'/resources/views/'.($isSysView ? 'hiwork/' : '').$file;

		// var_dump(getcwd(), __DIR__, DOC_ROOT);exit;
		//判断加载：模版文件地址
	    return ($isSysView ? __DIR__ : \APP_ROOT).'/views/'.$file;
	}
	

	
	
	/**
	 * 共享视图数据
	 * 20210102151240
	 */
	public static function share($key, $value=null)
    {
		//新值代旧值 
		if(is_array($key)){
			self::$shareData = $key+self::$shareData;
		}
		else if(is_string($key)){
			self::$shareData[$key] = $value;
		}
		else{
			throw new \Exception('错误的视图数据类型');
		}
    }


	/*
	 * 解析视图
	 * 完成内容：
	 	1. 导出数组变量
	 	2. 加载视图
	 	
	 	注意：
	 	多次调用 display 会由于 多次爆开数据导致内存升高
	 * 20190519
	public function display1($tpl, $isSysView=false)
	{
		//取回视图路径
		$fsite = self::exists($tpl, $isSysView);
		// var_dump($fsite);exit;
        
		if(!$fsite)
			throw new \Exception("编译文件 {$tpl} 不存在！");

		// unset($tpl, $isSysView, $fsite);//清除跟视图无关的变量

		//导出数组变量，并解析视图
		extract($this->dArr, \EXTR_OVERWRITE);
		include $fsite;
	}
		 */


}