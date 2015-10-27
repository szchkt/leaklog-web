<?php

$map->root(['module' => 'leaklog', 'controller' => 'welcome', 'action' => 'index']);
$map->login('/login', ['controller' => 'application', 'action' => 'login']);
$map->register('/register', ['controller' => 'application', 'action' => 'register']);
$map->connect('/login_or_register', ['controller' => 'application', 'action' => 'login']);

$map->connect('/(?<controller>\w+)', ['module' => false, 'action' => 'index']);
$map->connect('/(?<controller>\w+)/(?<id>\d+)', ['module' => false, 'action' => 'show']);
$map->connect('/(?<controller>\w+)/(?<id>\d+)/(?<action>\w+)', ['module' => false]);
$map->connect('/(?<controller>\w+)/(?<id>\d+)/(?<action>\w+)\.(?<format>\w+)', ['module' => false]);
$map->connect('/(?<controller>\w+)/(?<action>\w+)', ['module' => false]);
$map->connect('/(?<controller>\w+)/(?<action>\w+)\.(?<format>\w+)', ['module' => false]);

$map->connect('/(?<module>\w+)/(?<controller>\w+)', ['action' => 'index']);
$map->connect('/(?<module>\w+)/(?<controller>\w+)/(?<id>\d+)', ['action' => 'show']);
$map->connect('/(?<module>\w+)/(?<controller>\w+)/(?<id>\d+)/(?<action>\w+)');
$map->connect('/(?<module>\w+)/(?<controller>\w+)/(?<id>\d+)/(?<action>\w+)\.(?<format>\w+)');
$map->connect('/(?<module>\w+)/(?<controller>\w+)/(?<action>\w+)');
$map->connect('/(?<module>\w+)/(?<controller>\w+)/(?<action>\w+)\.(?<format>\w+)');

?>
