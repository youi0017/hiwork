<?php namespace hw\librarys;

/* 
 * 上传类
 * lm: bug chy修复，上传文件与文件名之间没有"."
 */

class upload
{
	private $fieldName;//上传域名称

	//构造进需设置的属性：类型，最大限制
	private $allow_type;//程序限制上传类型
	private $max_size;//程序限制上传大小

	private $fileName;//新文件名 20161117新建
	private $fileExt='';//新(文件)扩展名 20161117新建

	private $upfile_path;//上传完整路径，用于返回结果

	//内部全局属性
	private $originFile;//获得上传文件的源文件名称(含扩展名)
	private $tmp_file_path;//获得上传文件的临时文件地址(完整的临时目录)
	private $fileType;//获得上传文件的文件类型
	private $fileSize;//获得上传文件的文件大小
	private $errNmb=0;//错误号

	//构造函数完成基本功能
	function __construct(
		$fieldName, 
		array $allow_type=['image/pjpeg','image/jpeg','image/gif','image/png','image/x-png','image/bmp'], 
		int $max_size=1024000
	)
	{
		// 上传域名称
		$this->fieldName = $fieldName;

		// 允许上传类型
		$this->allow_type = $allow_type;

		// 允许上传大小
		$this->max_size = $max_size;
	}


	/**
	 * 文件上传方法
	 * $upfile_dir 上传文件存放目录
	 * $newName 上传后新的文件名(不含后缀)
	 *
	 * 20161117
	 */
	public function up($upfile_dir, $newName='now')
	{
		//路径(是否输入/是否是目录/是否可写入)检查
		if( !$this->check_path($upfile_dir) ) return false;

		//取得上传文件信息
		if( !$this->set_files() ) return false;

		//文件合法性(类型/大小)检查
		if( !$this->check_file() ) return false;

		//上传目录合法性处理
		if($upfile_dir[-1]!='/') $upfile_dir.='/';

		//合成新文件名(含扩展名)
		$_newFile = $this->_set_newFile($newName);

		//合成上传文件原整地址
		$upfile_path = $upfile_dir.$_newFile;

		//原文件存在则删除
		if(is_file($upfile_path)) unlink($upfile_path);

		//由临时文件转为上传文件
		if( !move_uploaded_file($this->tmp_file_path, $upfile_path) )
			return false;

		//赋值上传路径
		$this->upfile_path = $upfile_path ;

		return true;
	}

	//类属性数组赋值专用
	private function set_option($key, $val)
	{
		$this->$key=$val;
	}

	/**
	 * 重名为新名称:
	 * $new_file_name
	 * '' 使用原文件名做为新文件的文件名
	 * now 使用当前时间戳做为新文件的文件名
	 * 名称 使用输入的名称做为新文件的文件名
	 * 20161117 
	 * lm:20170610 修复bug: strrpos在没有'.'时，substr报错
	 * lm:20220302095747 修复bug: 文件名与后缀名之间没有"."
	 */
	private function _set_newFile($_newName='')
	{
		// 取回扩展名
		$this->fileExt = \pathinfo($this->originFile, \PATHINFO_EXTENSION);

		//为空则返回原文件名
		if(!$_newName){
			$_file = $this->originFile;
		}else{
			$this->fileName = 
				$_newName=='now' 
				? time().'_'.\rand(10000,99999) 
				: $_newName;

			$_file = $this->fileName.'.'.$this->fileExt;
		}

		//win下将文件名转化为BG，防止中文乱码
		return strtoupper(substr(PHP_OS,0,3))=='WIN' ? iconv('UTF-8', 'gb2312', $_file) : $_file;
	}

	//FUN:check_path 检查路径及权限
	private function check_path($upfile_dir)
	{
		//检查是否是路径
		return is_dir($upfile_dir) ? true : $this->setErr(-6);

		/*
		//检查路径是否具有权限 暂不进行权限检查2015年10月26日
		if( substr(base_convert(@fileperms($upfile_dir),10,8),-4)!='0777' )
		{
			$this->set_option('errNmb', '-7');
			return false;
		}
		return true;
		*/
	}

	//FUN:check_file 上传文件合法性检查
	private function check_file()
	{
		//$allow_type==[]时，不检查类型合法性
		if(!!$this->allow_type){
			//检查类型合法性
			if (!in_array($this->fileType, $this->allow_type)){
				return $this->setErr(-1);
			}
		}

		//检查大小
		if( $this->fileSize > $this->max_size ){
			return $this->setErr(-2);
		}

		//合法true
		return true;
	}

	//设置和$_FILES有关的内容
	private function set_files()
	{
    //没取到传递的file
		if(!isset($_FILES[$this->fieldName]))
			return $this->setErr(-8);

		
		$files = $_FILES[$this->fieldName];
		if($files['error']!=0)
			return $this->setErr($files['error']);

		$this->set_option('originFile', $files['name']);
		$this->set_option('tmp_file_path', $files['tmp_name']);
		$this->set_option('fileSize', $files['size']);
		$this->set_option('fileType', $files['type']);
		return true;
	}

	//FUN:catch_err 错误处理(生成错误代码)
	public function getErr()
	{
		$msg= 'ERR: ';

		switch($this->errNmb){
			case 0: $msg = '运行正常，没有捕捉到错误'; break;
			//1~5为系统错误代码,-1~-7为逻辑控制错误代码
			case 5: $msg .= '上传文件大小为0'; break;
			case 4: $msg .= '没有文件被上传'; break;
			case 3: $msg .= '文件只被部分上传'; break;
			case 2: $msg .= '上传文件超过了HTML表单中MAX_FILE_SIZE选项指定的值'; break;
			case 1: $msg .= '上传文件超过了php.ini 中upload_max_filesize选项的值'; break;
			case -1: $msg .= '不充许的文件类型'; break;
			case -2: $msg .= '文件过大，上传文件不能超过['.$this->max_size.']字节'; break;
			case -3: $msg .= '上传失败'; break;
			case -4: $msg .= '建立存放上传文件目录失败，请重新指定上传目录'; break;
			//case -5: $msg .= '未指定上传路径'; break;
			case -6: $msg .= '上传路径不存在'; break;
			case -7: $msg .= '指定的路径没有上传权限'; break;
            case -8: $msg .= '未取得文件域[name]或post文件超出系统设置'; break;
			default : $msg .= '末知错误';
		}
		return $msg;
	}

	// 设置错误号
	public function setErr($errNmb)
	{
		$this->errNmb = $errNmb;
		return false;
	}


	/**
	 * 上传base64图片
	 * @param base64img [必] 图片base64字符串
	 * @param dir [必] 上传目录
	 * @param fileName [选] 文件名，空则以当前时间戳命名
	 * 
	 * @return stringRtn 第一位标记状态，后面为说明或数据，如:15645679.jpg,1为成功，5645679.jpg为上传后的文件名
	 * 
	 * 20171124
	 */
	public static function up_base64img($base64img, $dir, $fileName='')
	{
		if(!is_dir($dir)) return '0上传目录不存在';
		
		//匹配出图片的格式 
		if(preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64img, $res))
		{
			$basename=($fileName?:time()).'.'.$res[2];
			return file_put_contents($dir.$basename, base64_decode(str_replace($res[1], '', $base64img))) ? '1'.$basename : '0图片无法保存到服务器';
		} 
	}



	//扩展方法：取得上传文件信息
	public function get_upfile_info()
	{
	   $res = '';
		if($this->set_files()){
			$res .= '上传文件名称：'.$this->originFile.'<br/>';
			$res .= '上传文件类型：'.$this->fileType.'<br/>';
			$res .= '上传文件大小：'.$this->fileSize.'<br/><br/>';
		}

		$res .= $this->getErr();
        return $res;
	}

	//private to public
	public function p2p($private_val)
	{
		return $this->$private_val;
	}

}