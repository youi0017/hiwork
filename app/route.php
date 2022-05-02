<?php 
$router->map('get', '/', function(){
    return view()
        ->assign('uri', $_SERVER['REQUEST_URI'])
        ->assign('date', date('Y-m-d'))
        ->display('sample/index.phtml');
});

$router->map('get', '/4xx[.html]?', function(){
   \hw\Rtn::epage(404);
});
