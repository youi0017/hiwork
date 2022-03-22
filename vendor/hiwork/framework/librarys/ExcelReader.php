<?php namespace hw\librarys;
/*
 * excel读取封装类
 * PhpSpreadsheet封装
 * 参考文献：PhpSpreadsheet Documentation
 	https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/
 * 20190622
 */

class Excel
{
	//单元格值的类型：视识值getFormattedValue，计算值getCalculatedValue，0原值getValue
	private static $valType = 'getFormattedValue';
	private $sht;//当前激活的工作表对象20190621
	private $emsg='';
	private $flExt;//文件后缀名

	private static $_meer;//mysql数据库对象实例
	
	/**
	 * excel初始化函数
	 * $xlsPath string xls/xlsx文件路径
	 * $vType int 值类型：1计算（默认），0原样
	 * 20190623
	 */
	public function __construct($xlsPath, $valType='getFormattedValue')
	{
		$this->xlsPath = $xlsPath;
		self::$valType=$valType;
	}

	/*
	 * 取后缀名
	 * 
	 */
	public function getFlExt($ucfirst=false)
	{
		if(isset($this->flExt)==false)
		{
			$this->flExt=substr(strrchr($this->xlsPath, '.'), 1);
			//cfirst(pathinfo($this->xlsPath)['extension']);
		}

		return $ucfirst ? ucfirst($this->flExt) : $this->flExt;
	}

	/**
	 * 返回当前类的实例
	 * 20170622
	 */
	public static function meer($xlsPath='')
	{
		if(!isset(self::$_meer)) self::$_meer = new static($xlsPath);
		return self::$_meer;
	}





/** Write start ****************/

/*
 * 写入一行数据：arr or obj
 * 
 */
protected function setRow($sht, $y, $row)
{
	$x='A';
	foreach($row as $v)
	{
		$sht->setCellValue($x.$y, $v);
		$x++;	
	}

	return $sht;		
}


/*
 * 写入多行，二维数据
 * 20190623
 */
protected function setShtData(&$sht, $arr)
{
	$y=1;
	foreach($arr as $row )
	{
		$this->setRow($sht, $y, $row);
		$y++;
	}

	return $sht;
}


/*
 * 设置文档属性
 *  20190702
 *  
 */
protected function setPps(&$spreadsheet, $arr=[])
{
	// 文档标准属性
	$_arr=[
		'creator'=>'ChyVtp',
		'title'=>'VtpFramework Created',
		'description'=>'VtpFramework Created by ChyVtp',
	];

	// 属性覆盖
	foreach($arr as $k => $v)
	{
		if(isset($_arr[$k]))
		{
			$_arr[$k]=$v;
		}

	}

	//写入文档
	$spreadsheet->getProperties()
		->setCreator($_arr['creator'])
    	->setTitle($_arr['title'])
    	->setDescription($_arr['description']);
}

/**
 * 设置exl文件数据,生成文档对象
 * $dArr array 三维数组，如 
 * [
 * 		'sheet1'=>[
 * 			['a1', 'b1', 'c1'],
 * 			['a2', 'b2', 'c2'],
 * 			['a3', 'b3', 'c3'],
 * 	 	], 
 * 	 	'sheet2'=>[
 * 			['aa1', 'bb1', 'cc1'],
 * 			['aa2', 'bb2', 'cc2'],
 * 			['aa3', 'bb3', 'cc3'],
 * 	 	],
 * 	]
 *
 */
protected function setExl(array $dArr)
{
	vendor('phpoffice-phpspreadsheet');
	// 创建文档
	$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
	// 设置属性
	$this->setPps($spreadsheet);

	$i=0;
	foreach($dArr as $k => $arr)
	{
		//创建工作表
		$sht = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $k);
		//向工作表写入数据
		$this->setShtData($sht, $arr);
		//工作表加入exl
		$spreadsheet->addSheet($sht, ++$i);//($sht, 0) 表加入第一位
	}

	return $spreadsheet;

	
}

/*
 * 生成exl文件
 * $dArr array 要写入的数据(三维数组)
 *
 * 
 */
public function crtExl(array $dArr)
{
	//生成文档对象
	$spreadsheet=$this->setExl($dArr);

	//创建写对象
	$libPath = '\PhpOffice\PhpSpreadsheet\Writer\\'.$this->getFlExt(true);
	$writer = new $libPath($spreadsheet);

	//生成exl文件
	//WR.'run/tmp/exl'.date('Ymd').'.xlsx'
	$b=$writer->save($this->xlsPath);
	var_dump($b);
}


/*
 * 生成下载excel
 * $fileName string 下载文件名(注意：无后缀名) 
 * $dArr array 文件的数据（三维数组） 
 * return exit阻断执行
 * chy 20190703115354
 *
 * 示例：
 * 
 */
public function dldExl($fileName, array $dArr)
{
	//下载的文件名称
	$fileName=$fileName.'.'.$this->getFlExt();
	//生成文档对象
	$spreadsheet=$this->setExl($dArr);

	header('Content-Disposition: attachment;filename="'.$fileName.'"');
	header('Cache-Control: max-age=0');
	header('Cache-Control: max-age=1');
	// If you're serving to IE over SSL, then the following may be needed
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
	header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
	header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
	header('Pragma: public'); // HTTP/1.0

	$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, $this->getFlExt(true));
	$writer->save('php://output');
	exit;

}


/** Write end ****************/




/** Reader1: 读取excel ****************/

	//取出工作表: 注意限止只能取一个工作表
	/*
	 * [读]取出工作表
	 * @param shtName [选] string 工作表名称，默认(空)取出第一个
	 * 20190623
	 */
	public function getSht($shtName='')
	{
		if(!$this->sht)
		{
			vendor('phpoffice-phpspreadsheet');
			//以工厂模式打开后缀为xxx的文件
			$fType =\PhpOffice\PhpSpreadsheet\IOFactory::identify($this->xlsPath);
			$reader=\PhpOffice\PhpSpreadsheet\IOFactory::createReader($fType);
			//只打开某表：要读多个表此处可省略，注意表不存在时，取第一个
			if($shtName) $reader->setLoadSheetsOnly($shtName);
			//var_dump($reader);exit;
			//载入excel文件
			$spreadsheet = $reader->load($this->xlsPath);

			//判断工作表是否存在
			$shtNames = $spreadsheet->getSheetNames();		
			if(reset($shtNames)=='Worksheet')
			{
				$this->emsg = '工作表不存在';
				return false;
			}

			//$spreadsheet->setActiveSheetIndex(0);
			//$spreadsheet->setActiveSheetIndexByName($shtName);
			$this->sht = $spreadsheet->getActiveSheet();
		}
		
		return $this->sht;		
	}

	/*
	 * 取一行的列数据
	 * $sht obj 工作表对象
	 * $x int 行号 (要求>=1)
	 * $maxCol int 最大列号 (默认为K)
	 * $cols array 要取的列名：[A,C,D,E]
	 * return array一维
	 */
	public function getRow($sht, $x, $cols=[])
	{
		$row = [];

		//最大列数
		$maxCol = $sht->getHighestColumn(); // e.g 'H'
		++$maxCol;

		//列名和索引 交换，以方便判断
		$cols = array_flip($cols);
		
		//取一行数据
		for($y='A'; $y!=$maxCol; $y++)
		{
			//调置了cols，但cols中不取这个列则跳过
			//var_dump($y, $cols[$y], $cols && in_array($y, $cols)==false);
			if($cols && isset($cols[$y])==false) continue;
			//$val=self::$valType==1 ? 'getCalculatedValue' : 'getValue';
			$val=self::$valType;
			$row[]=$sht->getCell($y.$x)->$val();
		}

		//返回一维数组
		return $row;
	}



	/*
	 * 取单元格中的值 
	 * $shtName string 工作表名称，默认第一个
	 * $hderLine int 表头所在行，注：默认1，第一行为表表头
	 * $start int 起始行数，注：默认2，从第2行开始读
	 * $end int 终止行数，注：默认读取第10行，0读为取所有行数
	 * $cols array 要读取的列数组，如[A,C,F,H,G] 
	 * return array 或 false 
	 * 
	 * 20190622
	 示例：
	 $path = WR.'/run/tmp/readDome.xlsx';
	 $exl = new \lib\excel\excel($path);
	 //$r = $exl->getDarr('',2, 3, 10, ['B', 'D', 'I']);//默认取第一个表
	 //$r = $exl->getDarr('chysheet1',2, 3, 10, ['B', 'D', 'I']);//chysheet1表, 2行列头，3到10行的B、D、I列数据
	 //$r = $exl->getDarr('chysheet1',0, 3, 10);//chysheet1表, 无列头，3到10行所有数据数据
	 //$r = $exl->getDarr('chysheet1', 2, 0, 5);//chysheet1表，2行头，3~5行数据
	 $r = $exl->getDarr();//第1个表, 无列头，1~100行所有数据数据
	 var_dump($r, $exl->getEmsg());
	 */
	public function getDarr($shtName='', $hderLine=0, $start=0, $end=100, $cols=[])
	{
		$r = [];
		//取回待处理的工作表
		$sht = $this->getSht($shtName);
		//var_dump($sht);exit;
		if(!$sht) return false;

		//取回最大行、列数
		$maxRow = $sht->getHighestRow(); // e.g. 7
		$maxCol = $sht->getHighestColumn(); // e.g 'H'
		++$maxCol;
		//var_dump("最大行、列数：{$maxRow}、{$maxCol }");

		//取出列头
		if($hderLine>0) $keys = $this->getRow($sht, $hderLine, $cols);
		//var_dump($keys);

		//处理数据
		if($end==0 || $end>$maxRow) $end=$maxRow;//最大行数
		if(!$start) $start=$hderLine+1;//最大行数

		//以行为基础，取出行与列的数据
		for($i=$start; $i<=$end; $i++)
		{
			//将列头与数据合并为关联数组
			if($hderLine>0)
				$r[] = array_combine($keys, $this->getRow($sht, $i, $cols));
			else
				$r[] = $this->getRow($sht, $i, $cols);
		}

		return $r;
		
	}

/*
	//取私有属性 20190622
	public function __call($fname, $args)
	{
		$fname=lcfirst(substr($fname, 3) );
		return $this->$fname;
	}
*/


	/**
	 * 输出错误信息
	 * 20190622
	 */
	public function getEmsg()
	{
		return $this->emsg;
	}


	/**
	 * 通过列索引返回对应的数组索引
	 * 注意：$col_key为["a"到"zz"]，即输出数字最大为702
	 */
	private function get_number_from_colkey($col_key)
	{
		$col_key = strtoupper($col_key);

		$key1 = substr($col_key, 0, 1);
		$key2 = substr($col_key, 1, 1);

		$num = ord($key1) -65;
		$num = $key2 ? (ord($key2)-65 + ($num+1)*26) : $num;

		return $num+1;
	}

	/**
	 * 通过数字取值EXCEL对应的列数
	 * 注意：$column_num<702
	 */
	private function get_colkey_from_number($column_num)
	{
		$num = intval($column_num/26);
		$letter = chr(65 + $column_num%26);
		$letter = $num>0 ? chr(65+$num-1).$letter : $letter;
		return $letter;
	}

	
	
	
}




