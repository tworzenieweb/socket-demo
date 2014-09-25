<?php

namespace Zmr;


use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\ServerProtocol;
use Ratchet\WebSocket\WsServer;

class Server {

    public function run() {

        $webServer = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new ServerProtocol(

                        new Demo()

                    )
                )
            ), 8080
        );

        $webServer->run();


    }

} 