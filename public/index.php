<?php
declare(strict_types = 1);
namespace hw;
header("Content-type: text/html; charset=utf-8");

require __DIR__.'/../vendor/autoload.php';

(new Bootstrap)->run();
