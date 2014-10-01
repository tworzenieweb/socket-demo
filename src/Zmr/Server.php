<?php

namespace Zmr;


use Monolog\Handler\ErrorLogHandler;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\ServerProtocol;
use Ratchet\WebSocket\WsServer;
use Monolog\Logger;

class Server {

    public function run() {

        $logger = new Logger('console');
        $logger->pushHandler(new ErrorLogHandler());
        $formLock = new FormLock($logger);

        $webServer = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ServerProtocol($formLock)
                )
            ), 8001, '46.41.129.246'
        );

        return $webServer->run();

    }

} 