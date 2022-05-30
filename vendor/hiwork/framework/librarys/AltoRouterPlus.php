<?php namespace hw\librarys;

/* 
 * AltoRouter 升级版
 * 20220517110526 chy 
 * 
 * 20220530161303 去除自定义的根路径方法，\hw\Route已使用官方用法
 */

class AltoRouterPlus extends AltoRouter
{

  // 20220530111517 官方库有根路径功能，故此方法不用（留存以查看）
  // 对 路由路径 增加 base
  // public function map($method, $route, $target, $name = null)
  // {
  //   $base = \env('URL_BASE');
  //   if(!!$base) $base = '/'.$base;
  //   return parent::map($method, $base.$route, $target, $name);
  // }


  /* 
   * 路由分组
   * add by chy 20220502075847
   * 
   * 使用示例
      $router->group('/some', [
        ['GET', '[/]?', '__getUri'],
        ['GET', '/abc[/]?', '__getUri'],
        ['GET', '/abc/xxx[/|.html]?', '__getUri'],
        ['POST', '/auth/register', 'RegisterController@register'],
        ['POST', '/auth/forgot', 'ForgotPasswordController@forgot'],
      ]);
    */
    public function group($prefix, $routes){
      foreach($routes as $k=>$r){
        $routes[$k][1] = $prefix.$r[1];
      }

      $this->addRoutes($routes);
  }



}