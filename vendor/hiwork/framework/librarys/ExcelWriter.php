<?php namespace hw\librarys;
/*
 * excel读取封装类
 * PhpSpreadsheet封装
 * 参考文献：PhpSpreadsheet Documentation
 	https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/
 * 使用示例见文末
 * 20190622
 */

class ExcelWriter
{
	// 文档对象
	//private $spreadsheet;
	// 活动工作表名称
	private $actShtName;
	// 待写入的数据(三维数组)
	/*
	 * 	说明：此三维数组是通过"活动表"一行行追加写入，最后一次性写入excel文档
	 * 	[
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
	 */
	private $dArr=[];

	public function __construct($shtName='chySheet1')
	{
		$this->addSheet($shtName);	
	}

	// 设置活动工作表(通过名称) 20190703180334
	public function setActSht($shtName)
	{
		$this->actShtName=$shtName;
	}


	// 加入工作表 20190703175652
	public function addSheet($shtName)
	{
		// 创建一个新的工作表（实际是创建工作表的数据）
		$this->dArr[$shtName]=[];
		// 加入后将本工作表作为活动表
		$this->setActSht($shtName);
	}

	// 加入一行数据 20190703175649
	public function addRow($row)
	{
		$sht = &$this->dArr[$this->actShtName];
		$sht[]=$row;
	}


	/*
	 * 生成exl文件[公共调用]
	 * $xlsPath string 生成excel的路径
	 * $confArr array 生成excel的属性数组
	 * 20190703183642
	 * 示例：
	 * $exl = new ExcelWriter('chysht1');
	 * $exl->addRow([2019, 'chy', '你好']);
	 * $exl->addRow([2018, '集合', 'php']);
	 * $exl->addRow(['公式', '＝a1', '=100+99']);
	 * $path = WR.'run/tmp/_tmp_dl_'.date('Ymd_His').'.xls';
	 * $exl->crtExl($path);
	 */
	public function crtExl($xlsPath, $confArr=[])
	{
		//生成文档对象
		$spreadsheet=$this->setExl($confArr);

		//创建写对象
		$libPath = '\PhpOffice\PhpSpreadsheet\Writer\\'.$this->getFlExt($xlsPath, true);
		$writer = new $libPath($spreadsheet);

		//生成exl文件
		$b=$writer->save($xlsPath);
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
	public function dldXlsx($fileName, $confArr=[])
	{
		$fileName = $fileName.'.xlsx';
		//生成文档对象
		$spreadsheet=$this->setExl($fileName, $confArr);

		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="'.$fileName.'"');
		header('Cache-Control: max-age=0');
		header('Cache-Control: max-age=1');
		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
		$writer->save('php://output');
		exit;
	}	

	public function dldXls($fileName, $confArr=[])
	{
		$fileName = $fileName.'.xls';
		//生成文档对象
		$spreadsheet=$this->setExl($fileName, $confArr);
				
		// Redirect output to a client’s web browser (Xls)
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="'.$fileName.'"');
		header('Cache-Control: max-age=0');
		// If you're serving to IE 9, then the following may be needed
		header('Cache-Control: max-age=1');

		// If you're serving to IE over SSL, then the following may be needed
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
		header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
		header('Pragma: public'); // HTTP/1.0

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');
		$writer->save('php://output');
		exit;
	}	



	/**
	 * 设置exl文件数据,生成文档对象
	 * $ppsConf array excel文件属性数组 
	 * $dArr array 三维数组，如 
	 *
	 */
	protected function setExl($ppsConf=[])
	{
		vendor('phpoffice-phpspreadsheet');
		// 创建文档
		$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		// 删除spreadsheet的默认空工作表
		$spreadsheet->removeSheetByIndex(0);
		// 设置属性
		$this->setPps($spreadsheet, $ppsConf);

		$i=0;
		foreach($this->dArr as $k => $arr)
		{
			//创建工作表
			$sht = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $k);
			//setActiveSheetIndexByName
			//向工作表写入数据
			$this->setShtData($sht, $arr);
			//工作表加入exl
			$spreadsheet->addSheet($sht, $i++);//($sht, 0) 表加入第一位
		}
		// 将生成的第一个工作表作为默认打开表
		$spreadsheet->setActiveSheetIndex(0);
		return $spreadsheet;		
	}




















	/*
	 * 取后缀名
	 * $xlsPath string xls的"文件路径"或"文件名称"
	 * $ucfirst bool 是否首字母大写
	 */
	public function getFlExt($xlsPath,$ucfirst=false)
	{
		$flExt = substr(strrchr($xlsPath, '.'), 1);
		return $ucfirst ? ucfirst($flExt) : $flExt;
	}




	/*
	 * 写入多行数据（主写入方法）
	 * $arr array 要写入表的二维数组
	 * 20190623
	 */
	protected function setShtData(&$sht, $arr)
	{
		$y=1;
		foreach($arr as $row)
		{
			$x='A';
			foreach($row as $v)
			{
				$sht->setCellValue($x.$y, $v);
				$x++;	
			}
			$y++;
		}

	}



	/*
	 * 设置文档属性
	 *  20190702
	 *  
	 */
	protected function setPps(&$spreadsheet, $confArr=[])
	{
		// 文档标准属性
		$_arr=[
			'creator'=>'ChyVtp',
			'title'=>'VtpFramework Created',
			'description'=>'VtpFramework Created by ChyVtp',
		];

		// 属性覆盖
		foreach($confArr as $k => $v)
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


	
}

/*

// 1. 基础使用示例
	$exl = new \Lib\ExcelWriter('chysht1');
	// 示例化后，默认定位到第一个工作表
	$exl->addRow([2019, 'chy', '你好']);
	$exl->addRow([2018, '集合', 'php']);
	$exl->addRow(['公式', '=a1', '=100+99']);
	// 添加工作表
	$exl->addSheet('chysht2');
	// 添加行数据
	$exl->addRow(['id', '名称', '班级']);
	$exl->addRow(['1001', '小王', '=100+50']);
	$exl->addRow(['1078', '=b1', '=c2+100']);
	// 下载xls
	// $exl->dldXls('test-dlxls-'.time());	
	// 下载 xlsx
	// $exl->dldXlsx('test-dlxlsx-'.time());
	// 生成文件
	$exl->crtExl(WR.'tmp.xls');

*/


