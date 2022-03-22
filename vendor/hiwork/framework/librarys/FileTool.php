<?php namespace hw\librarys;

/*
 * chy
 * 20210129170719
 */

class FileTool
{
	private $emsg;

	public function getErr()
	{
		return $this->emsg;
	}

	public function setErr($msg)
	{
		$this->emsg=$msg;
	}

	// 移动文件
	public function move($old, $new)
	{
		

	}


	// 创建目录 20210201143001
	public function createDir($dirPath, $mode=0777)
	{
		// if(!$this->isDir($dirPath)) return false;
		\mkdir($dirPath);
		\chmod($dirPath, $mode);
		return true;
	}

	// 判断是否是目录 20210129161545
	public function isDir($dir)
	{
		if(\is_dir($dir)) return true;
		$this->emsg='目录不存在或没有读取权限！';
		return false;
	}

	// 删除 文件 或 目录
	// 危险 暂不开启
	private function delete($dfPath)
	{
		$b = \is_file($dfPath) 
			? \unlink($dfPath)
			: self::delDir($dfPath);
		
		return $b;
	}


	// 删除目录（非空则递归删除） 或 文件
	public function deleteFile($filePath)
	{
		if(\is_file($filePath))
			return \unlink($filePath);
		else{
			$this->setErr('删除文件失败：不存在 或 没有删除权限');
			return false;
		}
	}

	/**
     * 删除目录树（递归）
     * 核心方法
     * @param $dir [必填] 有：则为GB2312的目录；无：则使用构造的$this->dir；以上必需满足其一
     * @return
     *
     * 说明：壳方法对参数已做处理，这里只运行
     * 2015/12/8
     */
    public function deleteDir($dir)
    {
		$this->setErr('禁止删除目录');
		return false;
		// var_dump( $dir, self::isDir($dir));exit;
		if(self::isDir($dir)==false) return false;

		$dfs = \array_diff(\scandir($dir), ['.','..']);
		
        foreach ($dfs as $df){

			$dir_or_file = $dir.'/'.$df;
			
			if(\is_dir($dir_or_file))
				self::delDir($dir_or_file);
			else
				\unlink($dir_or_file);
		}
		
        return \rmdir($dir);//由内而外删除空目录
    }

	// 读目录
	public function scan($dir)
	{
		if(self::isDir($dir)==false) return false;

		 // 读取目录(降序排)
		 $list = \scandir($dir, 1);

		 // 过滤 .和.. 并分离目录和文件 
		 $r=['f'=>[],'d'=>[]];
		 foreach($list as $li){
			 if($li=='.' || $li=='..') continue;
 
			 $it = $dir.'/'.$li;//合成当前路径
 
			 if(\is_file($it)){
				 $r['f'][]=[
					 'type'=>0,
					 'file'=>$li,
				 ];
				 continue;
			 }
 
			 if(\is_dir($it)){
				 $r['d'][]=[
					 'type'=>1,
					 'file'=>$li,
				 ];
				 continue;
			 }
		 }
 
		 return \array_merge($r['d'], $r['f']);
	}

}