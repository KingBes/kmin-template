<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Kmin\Template;

$template = new Template([
    'view_path' => __DIR__ . '/views',
    'cache_path' => __DIR__ . '/cache',
    'cache_time' => 0,
]);

echo $template->fetch('index.html', [
    'name' => 'kllxs',
    'age' => 18,
    'sex' => '男',
    'bool' => true,
    'json' => json_encode(['a' => 1, 'b' => 2]),
]);
