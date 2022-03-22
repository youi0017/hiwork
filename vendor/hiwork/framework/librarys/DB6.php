<?php namespace hw\librarys;

/*
 * 数据库的操作类db
 * 说明：完成数据库相关的基础操作
 * ver1.0 初版
 * ver1.1 增加 P()函数 20180713
 * ver1.2 增加 meer\D\D2\I\U\UI函数 20180720
 * ver4.0 增加 继承于baselib基类 20190523
 * ver4.1 
 * 		增加 I1（私有） 实现单数据写入
 * 		增加 I2（私有） 实现多条数据写入
 *   	更改 get_err 为 getErr
 *   	20190815114015 删除lastId属性，getLastid直接取值
 *   	注：I方法为插入数据的公开入口
 *
 * ver4.2
 * 		getStmt的$arr是：一维的关联或索引数组，优化数据绑定：索引数组由之前bindValue绑定，改为execute绑定
 * 		err的默认值由ok更改为空字串 20200504190840
 * 		删除暂存但已无用的方法 20200510155138
 * 		 
 * 	ver6.0
 * 	 优化对查询结果的返回，数字型的字段心数字型返回 20210113112227
 * 
 */
class DB6
{
	use \hw\traits\Singlet;

	private $err='';
	private $pdo;
	
	//pdo基础设置
	public function __construct()
	{
		try{
			// 取回数据库配置
			$db = \config('db');
			
			//1. 连接数据库
			$this->pdo=new \PDO("{$db['type']}:host={$db['host']};port={$db['port']};dbname={$db['dbname']};", $db['usr'], $db['pwd']);
			$this->pdo->exec('set names utf8');
			
			//开启错误，抛出错误
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

			// 关闭强制以字符串方式对待所有的值
			// $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
			// $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
		}
		catch( \PDOException $err ){
			$this->err=$err->getMessage();
		}		
	}

	// 取得最后insert的数据ID
	// 注：必需在insert语句立该使用，否则会被其它语句覆盖
	// 20190712160347 LM:20190815114051
	public function getLastid()
	{
		return $this->pdo->lastInsertId();
	}

	/*
	 * 执行操作SQL语句，返回影响条数
	 * @sql [必] string 操作型SQL语句(必需是：update/delete/insert)
	 * @row [选] array 要绑定的数据 
	 * @return int 受操作语句影响的行数: 整数 或 false(sql错误)
	 *
	 * lm: 加入错误判断 20180614
	 */
	public function execute($sql, array $row=[])
	{
		//执行sql语句
		$stmt=$this->getStmt($sql, $row);
		//返回影响条数
		return $stmt ? $stmt->rowCount() : false;
	}

	/*
	 * D方法一
	 * $tblName [必] string 表名
	 * $whr [必] string 删除条件,如 "id between 5 and 10" 或 "tit like '%手机%'" 或 "id=?" 或 "id=:id and stat=:stat"
	 * $row [选] 要绑定的一维数组，没有要绑定的数据则留空
	 * 
		db()->D('t_qy_dgtbl', 'id>1000 and stat=0');
		db()->D('t_qy_dgtbl', 'id=:id and grp=:grp', ['id'=>9, 'grp'=>14]);
		db()->D('t_qy_dgtbl', 'id in (?,?,?)', [101,109,138]);
	 * 
	 * 20180720
	 */
	public function D($tblName, string $whr, array $row=[])
	{		
		$sql="delete from {$tblName} where {$whr};";
		// var_dump($sql);exit;

		//执行sql语句
		$stmt=$this->getStmt($sql, $row);
		//返回影响条数
		return $stmt ? $stmt->rowCount() : false;
	}


	/*
	 * I方法：插入数据方法
	 * $tblName [必] string 表名
	 * $array [必] array 待插入的数据（一/二维关联数组）
	 * return false/int flase有错误，int受操作影响行数 
	 * 
	 	示例：
		db()->I('t_qy_dgtbl', [
			'tit'=>'I insert tit',
			'cnt'=>'I insert cnt',
			'grp'=>4
		]);

	 */
	public function I($tblName, array $arr)
	{
		//1. 根据数组维度，判断是一/二维写入
		$ifun=is_array(reset($arr)) ? 'I2' : 'I1';
		$stmt = $this->$ifun($tblName, $arr);

		//4. 返回结果
		return $stmt ? $stmt->rowCount() : false;
	}


	/*
	 * U方法
	 * $param tblName string 表名
	 * $param row array 待更新的一维数组数据
	 * $keys string,array 索引/联合索引/组合索引, 默认为id
		 说明：
		 	keys默认是主键的标记，即默认值id
			也可以是由多个字段联合组成的标记，如['id','stat']表id和stat组成条件
	 *
	 * 注1：更新条件间必需 and 关系 20200516103940
	 * 注1：不允许无条件 或 无设置项的更新 20200516103940
	 * 
	 * 示例一：
		$r = db()->U('t_wx_token', ['id'=>5, 'uid'=>100], ['id']);
		//$r = db()->U('t_wx_token', ['id'=>5, 'uid'=>100], 'id');//同上
		//$r = db()->U('t_wx_token', ['id'=>5, 'uid'=>100]);//同上
		var_dump($r, db()->getErr());

		示例二：
		$r = \lib\db()->U('t_wx_token', ['id'=>5, 'uid'=>350,'value'=>'abcdefg123456'], ['id', 'uid']);
		var_dump($r, \lib\db()->getErr());
	 *  chy 20200516110349
	 */
	public function U($tblName, array $row, $keys='id')
	{
		//索引可以是一个也可以是联合的或组合式的(数组只要索引不要值20200611110932)		
		$keys = is_array($keys) ? array_flip($keys) : ['id'=>0];
		// if(!is_array($keys)) $keys=['id'=>0];
		// 比较索引是否全部在数据中（如有则说明索引值有缺失）
		// var_dump($keys,$row);exit;
		if(array_diff_key($keys,$row)){
			$this->err='更新条件对应的数据缺失，请检查数据！';
			return false;
		}

		// 从数据中分离：设定值 和 条件
		$arr=['set'=>[], 'whr'=>[]];
		foreach($row as $k => $v){
			if(isset($keys[$k]))
				$arr['whr'][]="`{$k}`=:{$k}";
			else
				$arr['set'][]="`{$k}`=:{$k}";
		}

		// 处理数据的错误
		if(!$arr['set']){
			$this->err='更新的数据为空，请检查更新条件和数据！';
			return false;
		}
		// 执行
		$sql='update '.$tblName.' set '.implode(',', $arr['set']).' where '.implode(' and ', $arr['whr']);
		// var_dump($sql);

		//执行sql语句
		$stmt=$this->getStmt($sql, $row);
		//返回影响条数
		return $stmt ? $stmt->rowCount() : false;
	}

	/*
	 * 取得sql的查询结果
	 * $sql string [必] sql语句
	 * $row array [选] 作为sql语句的数据
	 * retrurn array|false 如果是false，则sql语句错误，如是空数组则代表未查到任何数据
	 * 
	 * 20180601 chy
	 * lm: 加入 错误判断 20180614
	 * lm: 加入 单值的输出 20180715
	 * 注：二维时未查到返回空数组，其它返回false，所以对结果的判断用empty
	 	20181213 一、二维时未查到返回空数组，单值没找到返回空字串，语句错误返回false
	 */
	public function R($sql, array $row=[], $fetchType=2, $rType='array')
	{
		//执行sql语句
		$stmt=$this->getStmt($sql, $row);
		if(!$stmt) return false;
		// var_dump($stmt, $stmt->fetchAll(\PDO::FETCH_OBJ));
		
		switch($fetchType)
		{
			//返回 二维数组
			case 2:
				return $stmt->fetchAll($this->get_rtnType($rType));
			//返回 一维数组
			case 1:
				return $stmt->fetch($this->get_rtnType($rType))?:[];
			//返回 单值
			default:
				return $stmt->fetchColumn()?:'';
		}

	}

	
	/*
	 * 取得分页数据
	 * $sql [必] 查询语句 
	 * $row [选] 要绑定的数据
	 * $pgInf [选] 页码相关的数据，以引用方式存储
	 * 		$pgInf示例：[
	 * 			sh:int//显示条数(传入)
	 * 		 	pn:int//页码数(传入)
	 * 		   	tt:int//总条数(返回)
	 * 		    tp:int//总页数(返回)
	 * 		    key:'page'//页码key默认为'page'(传入)
	 * 		]
	 * 
	 * 访问url示例：
	 * http://hw.com/cs-pg?pn=2&sh=10
	 * 
	 * $sql 执行的sql
	 * $row 绑定的值
	 * $pgInf 页码信息 [
		 'show'=>每页显示条数(默认5条), 
		 'page'=>当前页码数(默认第1页),
		 'totalPage'=>总页码数,
		 'totalRow'=>总条数,
		 'pageKey'=>传入的页码索引,//默认为page
		]
	 * 
		使用示例一：
		$page=[];//存储页码数据
		$rows=db()->P($sql, [], $page);//取出分页数据
		var_dump($page, $rows);//测试输出 分页数据 和 页码
		使用示例二：
		//控制每页显示10条
		$arr=['rows'=>[],'page'=>['show'=>10]];
		$arr['rows'] = db()->P($sql, [], $arr['page']);
		var_dump($arr);

	 * 	优化分页参数的验证，加入最后一位;的处理20200428183219
	 */
	public function P($sql, $row=[], $pgInf=[], $rType='array')
	{
		// 清除最后一位的分号
		if($sql[-1]==';') $sql=\substr($sql, 0,-1);

		// 显示条数
		$pgInf['show']=\filter_input(\INPUT_GET, 'show', \FILTER_VALIDATE_INT, [
			'options'=>[
				'min_range'=>1,//最小1条
				'max_range'=>1000,//最小1000条
				'default'=>$pgInf['show'] ?? 5,//默认5
			]
		]);

		// 当前页码数
		// 20220107094350 在pgInf中加入pageKey
		if(!isset($pgInf['pageKey'])) $pgInf['pageKey']='page';

		$pgInf['page'] = \filter_input(\INPUT_GET, $pgInf['pageKey'], \FILTER_VALIDATE_INT, [
			'options'=>[
				'min_range'=>1,//最小第1页
				// 'max_range'=>1000,//最大1000页
				'default'=>$pgInf['page'] ?? 1,//默认1
			]
		]);
		
		//总条数
		$sql_t=preg_replace('/^select .* from/i', 'select count(*) as t from', $sql);
		// var_dump($sql_t);exit;
		$pgInf['totalRow']=(int)$this->R($sql_t, $row, 0);//总条数
		// var_dump($pgInf);exit;
		//总页数 = 向上取整(总条数/显示条数) 
		$pgInf['totalPage'] = (int)ceil($pgInf['totalRow']/$pgInf['show']);//总页数
		
		//启始条数 = (页数-1)*显示条数
		$limit=($pgInf['page']-1)*$pgInf['show'];
		$limit=" limit {$limit}, {$pgInf['show']}";

		// var_dump($pgInf);//exit;
		// var_dump($sql.$limit);exit;
		return [
			'rows'=>$this->R($sql.$limit, $row, 2, $rType),
			'page'=>$pgInf,
		];
	}




	/*
	 * [I2 二维数组的写入]
	 * 注：I的支持性方法，不公开
	 * @Author   chy
	 * @DateTime 2019-12-02
	 * @param $arr 二维关联格式数组
	 * @return stmt
	 */
	private function I2($tbl, array $arr)
	{
		//1.取出基础数据
		// k:索引, v:值, seats:?占位符
		$d = ['v'=>[], 'seats'=>[]];
		// 1.1 生成索引
		$d['k'] = array_keys(reset($arr));
		// 1.2 取出二维值生成占位符与对应值（一维值数组）
		foreach($arr as $row) {
			$_seats=[];
			foreach ($row as $v) {
				$_seats[]='?'; 
				$d['v'][]=$v;
			}

			$d['seats'][]='('.implode(',', $_seats).')';
		}


		//2. 生成预处理语句
		$sql = 'insert into '.$tbl.' ('.implode(',', $d['k']).") values ".implode(',', $d['seats']).';';
		// var_dump($sql);exit;

		//3. 执行并返回结果集
		return $this->getStmt($sql, $d['v']);
	}


	/*
	 * [I1 一维数组的写入]
	 * 注：I的支持性方法，不公开
	 * @Author   chy
	 * @DateTime 2019-12-02
	 * @param $arr 一维关联格式数组
	 * @return $stmt
	 */
	private function I1($tbl, array $row)
	{
		//1.取出基础数据
		// k:索引, v:值, seats:?占位符
		$d = ['k'=>[], 'v'=>[], 'seats'=>[]];
		foreach($row as $k =>$v)
		{
			$d['k'][]=$k;
			$d['v'][]=$v;
			$d['seats'][]='?';
		}
	
		//2. 生成预处理语句
		$sql = "insert into {$tbl} (".implode(',', $d['k']).") values (".implode(',', $d['seats']).");";

		//3. 执行并返回结果集
		return $this->getStmt($sql, $d['v']);
	}



	//返回错误信息 20180614
	public function getErr()
	{
		return $this->err;
	}


	/*
	 * 执行sql语句，返回pdo::stmt对象
	 * $row [必] array 要绑定的数据，一维数组: 索引 与 关联均可
	 * return PDOStatment结果集对象
	 * lm:20191202180718
	 */
	private function getStmt($sql, array $row)
	{
		if(!$this->pdo) return false;
		
		try{
			//预执行sql语句
			$stmt=$this->pdo->prepare($sql);

			//绑定数据[]
			if(!!$row){
				//数值型数组
				if(isset($row[0]) && $row[0]===reset($row)){
					$stmt->execute($row);
				}
				//关联型数组
				else{
					foreach( $row as $key => $val){
						$stmt->bindValue(':'.$key, $val);
					}
					$stmt->execute();
				}
			}
			else{
				$stmt->execute();
			}

			return $stmt;		
		}
		catch( \PDOException $err ){
			$this->err=$err->getMessage();
			return false;
		}
		
	}

	/*
	 * 设置结果类型
	 * 20170715
	 */
	private function get_rtnType($rType='object')
	{
		switch($rType)
		{
			case 'object': return \PDO::FETCH_OBJ;
			case 'array': return \PDO::FETCH_ASSOC;
			case 'both': return \PDO::FETCH_BOTH;
			default: return \PDO::FETCH_OBJ;
		}
	}


	
	/*
	 * IU方法
	 * 20200516110731 废除此方法
	 * @param tblName string 表名
	 * @param dArr array 待更新/插入的一维数组数据
	 * @pk tblName string 主键名称
	 * 说明: 如果 pkName在数据中则更新，不在则插入
	 * 
	 	示例：
		$arr=['id'=>'2', 'tit'=>'第2行标题', 'grp'=>1];
		$c=db::U('t_qy_dgtbl', $arr, 'id');
		$c>0 ? rtn::okk() : rtn::err();
		exit;

	public function IU($tblName, array $row, $key='')
	{
		return $key 
			? $this->U($tblName, $row, $key)
			: $this->I($tblName, $row);
	}
	*/
}


