<?php

namespace Zmr;


use Monolog\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

abstract class AbstractDispatcher implements WampServerInterface {

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger) {
        $this->logger = $logger;
        $this->init();
    }

    protected abstract function init();

    /**
     * @param $topic broadcasting topic
     * @param $message string
     * @param $subscribers \SplObjectStorage[] array of subscribers
     * @param ConnectionInterface $exclude
     * @throws \Exception
     */
    protected function broadcast($topic, $message, array $subscribers, ConnectionInterface $exclude = null)
    {

        if (empty($subscribers[$topic])) {
            throw new \Exception(sprintf('There are no subscribers for %s', $topic));
        }

        foreach ($subscribers[$topic] as $client) {

            /**
             * @var $client \Ratchet\Wamp\WampConnection
             */

            if ($client !== $exclude) {
                $client->event($topic, $message);
            }
        }
    }

    /**
     * @param $message
     * @param int $level
     * @return bool
     */
    public function log($message, $level = Logger::DEBUG) {

        return $this->logger->log($level,$message);

    }


    /**
     * @param ConnectionInterface $conn
     * @param \Ratchet\Wamp\Topic|string $topic
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic) {

        $this->onClose($conn);

    }

    /**
     * @param ConnectionInterface $conn
     */
    public function onOpen(ConnectionInterface $conn) {

        $this->log(sprintf('User %s connected', $conn->WAMP->sessionId));

    }


    /**
     * @param ConnectionInterface $conn
     * @param \Exception $e
     */
    public function onError(ConnectionInterface $conn, \Exception $e) {

        $this->log($e->getMessage(), Logger::ERROR);

    }


    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        $topic->broadcast($event);
    }
}