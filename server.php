<?php

require dirname(__FILE__) . '/vendor/autoload.php';

$server = new Zmr\Server();
$server->run();