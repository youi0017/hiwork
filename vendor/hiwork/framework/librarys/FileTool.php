<?php namespace hw\librarys;

/**
 * 文件系统操作工具类
 * 注意：所有工具名称与linux中操作指令一致
 * 
 * 示例：
 * 
		$ft = new \hw\librarys\FileTool(__DIR__);

		goto cp;

		cp:
			$b = $ft->cp('a.txt', 'a.txt');//错误，新旧名称不能一样
			$b = $ft->cp('a.txt', 'a.bak.txt');
			var_dump($b, $ft->getErr());
			exit;

		mv:
			// $b = $ft->mv('a1/', 'a2/');//改名（同级移动）
			// $b = $ft->mv('a2/a11/', 'a3/');//上下级移动
			// $b = $ft->mv('a.txt', 'a.txt');//不错，但没用
			$b = $ft->mv('a.txt', 'b.txt');//文件更名
			var_dump($b, $ft->getErr());
			exit;


		mkdir:
			$b = $ft->mkdir('a1/a11/a111', 0750, true);
			var_dump($b, $ft->getErr());
			exit;

		exit;

		rmdir:
			$b = $ft->rmdir('a.cs');
			var_dump($b, $ft->getErr());
			exit;

		rm:
			$b = $ft->rm('a.txt');
			var_dump($b, $ft->getErr());
			exit;

		ls:
			$list = $ft->ls('a9-2/');
			var_dump($list, $ft->getErr());
			exit;
 * 
 */
class FileTool
{
	use \hw\traits\Singlet;

	private $emsg;
	private $basedir;

	/* 
	 * $basedir [必] 文件操作的基础目录，必需以'/'结尾
	 * 20220328104341 新增
	 */
	public function __construct($basedir)
	{
		$this->basedir = $basedir[-1]=='/' ? $basedir : $basedir.'/';
	}


	/**
	 * 取得文件内容
	 * 20220328151737
	 * @return string
	 */
	public function cat($file){
		$file = $this->realPath($file);

		if( \is_file($file) ){
			return \file_get_contents($file);
		}

		return $this->setErr('文件不存在');
	}

	
	/**
	 * 移动文件或目录
	 *
	 * @return void
	 * 注意：新旧名称一样也不错
	 */
	public function mv($oldPath, $newPath)
	{
		try{
			\rename($this->basedir.$oldPath, $this->basedir.$newPath);
		}
		catch(\ErrorException $e){
			return $this->setErr('移动时出错！');
		}

		return true;
		// $b = \rename($this->basedir.$oldPath, $this->basedir.$newPath);
		// return $b ?: $this->setErr('移动时出错！');
	}


	/**
	 * 复制文件或目录
	 *
	 * 注意：新旧名称不能一样
	 * @return void
	 */
	public function cp($oldPath, $newPath){

		try{
			\copy($this->basedir.$oldPath, $this->basedir.$newPath);
		}
		catch(\ErrorException $e){
			return $this->setErr('复制时出错！');
		}

		return true;
		// $b = \copy($this->basedir.$oldPath, $this->basedir.$newPath);
		// return $b ?: $this->setErr('复制时出错！');
	}


	//  
	/* 
	 * 创建目录
	 * $dirPath [必] 目录路径
	 * $mode [选] 权限码 默认：0777
	 * $recursive [选]	是否递归创建 默认：false
	 * 20220328103436
	 */
	public function mkdir($dirPath, $mode=0777, $recursive=false)
	{
		$dirPath = $this->realPath($dirPath);
		// var_dump($dirPath);exit;

		// 注意：mkdir的错误，try和catch不能直接捕获，需由系统异常接管后，再抛出ErrorException后，由try语句处理。
		try {
			\mkdir($dirPath, 0777, $recursive);
			\chmod($dirPath, $mode);
		}
		// catch(\Throwable $e){
		catch(\ErrorException $e){
			// var_dump($e);exit;
			return $this->setErr('[错误]请检查目录是否存在或有权限');
		}
		
		return true;
	}


	/**
	 * 删除 文件
	 * 注意：系统中删除文件或目录时：父目录有w权限即可。
	 * 注意：不支持 通配符，如需可参考下面：
	 		$mask = "*.jpg"
    	array_map( "unlink", glob( $mask ) );
	 *	
	 * 20220328102635
	 */
	public function rm($file)
	{
		$file = $this->realPath($file);

		if(is_file($file)){
			$b = unlink($file);
			return $b ? true : $this->setErr('文件无法删除');
		}

		return $this->setErr('文件不存在');
	}




	/**
     * 删除目录树（递归）
     * 核心方法
     * @param $dir [必填] 有：则为GB2312的目录
     * @return
     *
     * 20220328102541
     */
    public function rmdir($dir)
    {
			// $this->setErr('禁止删除目录');
			// return false;

			$dir = $this->basedir.$dir;

			return $this->_rmdir($dir);
    }


		/**
		 * 递归删除dir
		 *
		 * @param [type] $dir
		 * @return void
		 * 
		 * 注意：系统中删除文件或目录时：父目录有w权限即可。
		 * 20220328142430
		 */
		private function _rmdir($dir){

				// var_dump( $dir, self::_isdir($dir));exit;
				if(self::_isdir($dir)===false) return false;

				$dfs = array_diff( scandir($dir), ['.','..'] );
				
				foreach ($dfs as $df){

					$dirOrFile = $dir.'/'.$df;

					$b = \is_dir($dirOrFile) ? self::_rmdir($dirOrFile) : \unlink($dirOrFile);

					if(!$b) return $this->setErr('删除目录时出错！');
				}
			
				return \rmdir($dir) ?: $this->setErr('删除目录时出错！');//由内而外删除空目录
		}




	/* 
	 ** 列出目录内容
	 * 20220325152911 目录type:dir, 文件有后缀则类型返回后缀，否则为file
	 * 增加过滤项 $filter
	 * 
	 */
	public function ls($dir='./', $removeIts=['.', '..'], $desc=true)
	{
		$dir = $this->realPath($dir);
		// var_dump($dir);

		if(self::_isdir($dir)===false) return false;

		// 读取目录(降序排)
		$list = scandir($dir, 1);

		// 过滤 .和.. 并分离目录和文件 
		$r=['f'=>[],'d'=>[]];
		foreach($list as $li){
	
			if(in_array($li, $removeIts)) continue;

			$it = $dir.'/'.$li;//合成当前路径

			if(is_file($it)){
				$r['f'][]=[
					'name'=>$li,
					'type'=> \strrchr($li, '.') ?: 'file',
					'mtime'=> \filemtime($it),
					'size'=> \filesize($it),
				];
			}
			else if(is_dir($it)){
				$r['d'][]=[
					'name'=>$li,
					'type'=>'dir',
					'mtime'=> 0,
					'size'=> 0,
				];
			}
		}
 
		return array_merge($r['d'], $r['f']);
	}



	// 判断是否是目录 20210129161545
	public function realPath($path)
	{		
		return $this->basedir.$path;
	}



	// 判断是否是目录 20210129161545
	private function _isdir($dir)
	{		
		return \is_dir($dir) ?: $this->setErr('目录不存在或没有读取权限！');
	}

	public function getErr()
	{
		return $this->emsg;
	}

	public function setErr($msg)
	{
		$this->emsg=$msg;
		return false;
	}

}