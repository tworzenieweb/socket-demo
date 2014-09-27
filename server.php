<?php

require dirname(__FILE__) . '/vendor/autoload.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = new Zmr\Server();
$server->run();