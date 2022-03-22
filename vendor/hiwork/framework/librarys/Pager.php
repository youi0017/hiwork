<?php namespace hw\librarys;

/*
 * 页码类
 * 20200118151356

 * 实例：配合分页调用
 * $fenye = db()->P(
		 'select * from news where id>? and uid=?',
		 [20, 45],
		 $page=['show'=>8]
	);
	$pager = (new \hw\librarys\Pager())->set($fenye['page']);
	echo $pager['tags'];//分页标签
	echo $pager['label'];//分页信息


 */

class Pager
{
	private $page;//当前页码数
	private $pageKey;//页码key
	private $show;//每页条数
	private $totalRow;//总条数
	private $totalPage;//总页数

	private $PResult;//db::P()的结果

	private $url;

	
	public function __construct($show=8, $pageKey='page')
	{
		$this->show = $show;
		$this->pageKey = $pageKey;

		// db()->P($sql, $row=[], $pgInf=[], $rType='array')

	}

	// 从分页中取出相关属性
	public function __get($name){
		return $this->PResult['page'][$name] ?? null;
	}

	
	// 从分页中取出相关属性
	public function __set($name, $val){
		$this->PResult['page'][$name]=$val;
	}

	// 查询结果（数据+分页）
	public function result()
	{
		return $this->PResult;
	}

	// 取数据rows 20210402162741
	public function rows()
	{
		return $this->PResult['rows'];
	}

	// 用于返回结果
	public function getResult($sql, $row=[], $rType='array')
	{
		$this->PResult = db()->P(
			$sql, 
			$row, 
			[
				'show'=>$this->show,
				'pageKey'=>$this->pageKey,
			], 
			$rType
		);

		return $this;
	}

	// 生成页面url
	// $page int 当前页码
	public function url(int $page)
	{
		if(!$this->url){
			// 1. 取回页码索引
			$pageKey = $this->pageKey;

			// 2. 合成uri
			$uri = parse_url($_SERVER['REQUEST_URI']);

			// 先删除原有页码
			unset($_GET[$pageKey]);

			// 合成不含页码值的uri
			if(!$_GET)
				$this->url = $uri['path'] . "?{$pageKey}=";
			else
				$this->url = $uri['path'].'?'. \http_build_query($_GET) . "&{$pageKey}=";

		}

		return $this->url.$page;

	}


	public function getErr(){
		return db()->getErr();
	}






	/*
	 * 输出分页页码标签
	 * @param $page [必] 由DB中P方法返回的分页信息[注意：必须传入pn和tp二个参数]
	 * return string 分页标签
	 * 20200118162738
	 */
	public static function set(array $pageArr=[])
	{
		//处理页码(用于调试，正式使用时用db::P的页码数组)
		$pageKey = $pageArr['pageKey'] ?? 'page';//取页码key
		$pageArr+=['page'=>$_GET[$pageKey] ?? 1, 'totalPage'=>10];
		$page = $pageArr['page'];//取回当前页码


		//生成页码信息 
		$info="第{$pageArr['page']}页，共{$pageArr['totalPage']}页";

		// 取回当前uri
		$uri = parse_url($_SERVER['REQUEST_URI']);
		//先删除原有页码
		unset($_GET[$pageKey]);

		// 合成不含页码值的uri
		if(empty($_GET))
			$uri=$uri['path']."?{$pageKey}=";
		else
			$uri=$uri['path'].'?'.http_build_query($_GET)."?{$pageKey}=";
		// var_dump($uri,  $page);exit;

		// 取数组索引变量并赋值
		// extract($page, \EXTR_OVERWRITE);

		// 上页，下页，首页，末页
		if($page<=1){
			$pre = 'javascript:void(0)';
			$first = 'javascript:void(0)';
			$preCls = 'style="color:#AAA;"';
		}
		else{
			$pre = $uri.($page-1);
			$first = $uri.'1';
			$preCls = '';
		}

		if($page>=$pageArr['totalPage']){
			$next = 'javascript:void(0)';
			$end = 'javascript:void(0)';
			$nextCls = 'style="color:#AAA;"';
		}
		else{
			$next = $uri.($page+1);
			$end = $uri.$pageArr['totalPage'];
			$nextCls = '';
		}

		// 返回页码标签
		return [
			'info'=>$info,
			'label'=>"<a title='首页' {$preCls} href='{$first}'>首页</a> <a title='上页' {$preCls} href='{$pre}'>上页</a> <a title='下页' {$nextCls} href='{$next}'>下页</a>	<a title='末页' {$nextCls} href='{$end}'>末页</a>",
		];
	}
 
	/*
	 * 取得url中的路径部份
	 * 如：http://vtp.com/abc.php/cs-index/xx.html?abc=123&cc=c456
	   返回的是：/cs-index/xx.html
	 * 20200118153244
	 */
	// public static function uri($uri=''){
	// 	return $uri ?: $_SERVER['REQUEST_URI'];
	// }


	/*
	 * 重新生成url参数字串
	 * 注：$arr中单元用于覆盖query参数
	 * 如：http://vtp.com/abc.php/cs-index/adfa.html?cate=xy&pn=7
	   self:build(['pn'=>15]);//则重生成的参数是cate=xy&pn=15
	 * 20200118153238
	 */
	// public static function resetParams($arr=[]){
	// 	// 取出原get参数
	// 	parse_str($_SERVER['QUERY_STRING'], $row);
	// 	// 合并传入数据
	// 	$arr=$arr+$row;
	// 	// 生成新get参数
	// 	return http_build_query($arr);
	// }


}


