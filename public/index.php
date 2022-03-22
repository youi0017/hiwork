<?php
declare(strict_types = 1);
namespace hw;
header("Content-type: text/html; charset=utf-8");

require __DIR__.'/../vendor/autoload.php';

(new Bootstrap)->run();




/* 
use \hw\routing\Route;

// var_dump(new Route);exit;

Route::get('/', function(){
    return 'ok';
});


Route::get('/abc', function(){
    return '/abc';
});

var_dump($_SERVER['routes']);

 */