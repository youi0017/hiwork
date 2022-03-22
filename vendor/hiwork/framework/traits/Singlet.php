<?php namespace hw\traits;

/**
 * 类的单态模式
 * chy 20201231112417
 */

trait Singlet
{
	protected static $_mine;

	//单态实例 20190811170839
	//LM: CHY 20200509144506 兼容性修复：单态实例与实例化时参数不对应的问题
	public static function mine(...$args)
	{
		if(!static::$_mine){
			//后期静态绑定找回当前类名
            $class=static::class;
            // var_dump('执行了单态模式，类：'.$class);

			// PHP5.6+，...operator解构（20200510083627不确定是否有bug）
			static::$_mine=new $class(...$args);

			// 通过映射方式到构造函数，同上面的解构
			// $reflect = new \ReflectionClass($class);
    		// static::$_mine = $reflect->newInstanceArgs($args);
		}
			
		return static::$_mine;
	}
}