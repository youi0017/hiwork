<?php namespace hw\librarys;

/*
 * 本地文件操作库 原localdata
 * Ver1.0 20170708 初创，缓存的状态+数据的定时更新+写入+读取
 * ver2.0 20200804103900
 *
 * 说明：为数据创建本地缓存
 * 
 * MODE NOTE: 
 	1. 更新缓存时，将原数据进行备份 20170708
 * 
 * 
 */

class Temp
{
	//缓存文件地址
	private $fsite;
	//缓存过期类型 定时timeout:00~24，间隔interval 0~++
	private $timer;
	//缓存有效性储值: 0:文件不存在；-1:已过期；1:有效
	private $stCode;
	//最后更新时间的时间戳
	private $utime;


	/*
	 * 
	 * @param $timer array 默认1小时更新更存
	 * 注：
	 	定时更新是 在设定的小时（0~24）每天更新(打开后) 
	 	如：['timeout'=>'09'] 表示9点后，打开时更新
	 	间隔更新是 在设定的时间间隔（0~无限大）更新(打开后)
	 	如：['interval'=>'2'] 表示每隔2个小时，打开时更新
	 * 
	 * 20170702
	 */
	public function __construct($fsite, array $timer=['interval'=>1])
	{
		if(!\is_dir(\dirname($fsite))) throw new \Exception('传入路径不存在:'.$fsite);

		$this->fsite = $fsite;
		$this->timer = $timer;
		$this->utime = \is_file($fsite) ? \filemtime($fsite) : 1;
		//取出状态码
		$this->stCode=$this->setStCode();
	}


	// 取回状态码 20200804114018
	public function getStCode()
	{
		return $this->stCode;
	}


	/*
	 * 生成状态码
	 * return int  0:文件不存在；-1:已过期；1:有效
	 * 20170703
	 * 20200804113651 优化处理逻辑
	 */
	public function setStCode()
	{
		// 设定状态码：默认为 0
		$stCode = 0;
		// 判断状态码
		if(is_file($this->fsite)){
			//过期的判断（优先执行间隔定时）
			if(isset($this->timer['interval'])){
				// 计算时间差
				$offset = \date('YmdH')-\date('YmdH', $this->utime);
				//有效:小于控制时间
				$stCode = $offset < $this->timer['interval'] ? 1 : -1;
			}				
			else if(isset($this->timer['timeout'])){

				$date0=\date('Ymd');//当前日期
				$date1=\date('Ymd', $this->utime);//最后更新日期

				//有效的条件：日期相同（说明已更新）有效 || 日期大于文件的且时间小于设定的有效(说明$date0永远大于$date1)
				$stCode = $date0==$date1 || date('G')<$this->timer['timeout'] ? 1 : -1;
			}
		}

		//var_dump($stCode);exit;
		// 返回状态码
		return $stCode;
	}
	
	
	/**
	 * 取得临时数据的内容
	 * @getDataFunc function 更新数据的函数
	 * 
	 * return mix 返回结果类型与getDataFunc的类型一致
	 * lm: 20200804120659
	 * lm: 20200924152931  加入 if(!$r) $r='';
	 * lm: 20201019173308 出错时return null; 且不再生成缓存数据 
	 */
	public function get(callable $getDataFunc, $params=[])
	{
		// 合法状态读取临时数据
		if($this->stCode>0){
			return require($this->fsite);
		}
		// 过期或不存在时重新生成
		else{
			// 取回数据
			$rdata = \call_user_func_array($getDataFunc, $params);
			if(!empty($rdata)){
				$this->set($rdata);
				return $rdata;
			}

			return null;

			if(empty($rdata)) $rdata='';//如未取得数据，则以空字串写入
			// var_dump($r);exit;
			$this->set($rdata);
			return $rdata;
		}
	}

	/*
	 * 生成本地临时数据
	 * return bool true;false
	 * 20170702
	 */
	public function set($data)
	{
		//先备份
		if($this->stCode!=0){
			rename($this->fsite, $this->fsite.'.bak');
		}

		$b = @file_put_contents($this->fsite, '<?php return '.var_export($data, true).';');
		return $b;
	}
	

	// 取出最后更新日期与时间 20200804120947
	public function getUtime($fomat='')
	{
		return $fomat=='' ? $this->utime : \date($fomat, $this->utime);
	}


	
}





