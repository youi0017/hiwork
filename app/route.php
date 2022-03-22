<?php 
$route->get('/', function(){
    return view()
        ->assign('uri', $_SERVER['REQUEST_URI'])
        ->assign('date', date('Y-m-d'))
        ->display('sample/index.phtml');
});

$route->get('/404[.html]', function(){
   \hw\Rtn::epage(404);
});

// $route->get('/403[.html]', function(){
//     \hw\Rtn::epage(403);
// });

// $route->get('/405[.html]', function(){
//     \hw\Rtn::epage(405);
// });

// $route->get('/500[.html]', function(){
//     \hw\Rtn::epage(500);
// });



