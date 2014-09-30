<?php

namespace Zmr;

use Monolog\Logger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

/**
 * A simple pub/sub implementation
 * Anything clients publish on a topic will be received
 *  on that topic by all clients
 */
class FormLock implements WampServerInterface {

    const RELEASED = 'released';
    const LOCKED = 'locked';

    /**
     * @var string[] array of sessionIds
     */
    protected $formsLocked;

    /**
     * @var \SplObjectStorage[]
     */
    protected $formsSubscribers;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Logger $logger) {
        $this->formsLocked = [];
        $this->formsSubscribers = [];
        $this->logger = $logger;
    }

    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        $topic->broadcast($event);
    }

    /**
     * @param ConnectionInterface $conn
     * @param string $id
     * @param \Ratchet\Wamp\Topic|string $fn
     * @param array $params
     * @return mixed
     */
    public function onCall(ConnectionInterface $conn, $id, $fn, array $params) {

        $form = $params['form'];

        switch ($fn) {

            case 'lockForm':

                $locker = $params['locker'];

                // form is touched for the first time
                if (empty($this->formsLocked[$form])) {

                    $this->formsLocked[$form] = $locker;

                    $conn->callResult($id, array(
                            'event' => 'FormLock',
                            'locker' => $locker
                    ));

                    $this->logger->addDebug(sprintf('Form %s was locked by %s', $form, $locker));
                    $this->broadcast($form, array("status" => static::LOCKED), $conn);

                } else {

                    $conn->callError($id, "Form was locked", array('form' => $form, 'status' => static::LOCKED, 'locker' => $this->formsLocked[$form]));

                }

                break;

            case 'releaseForm':

                $this->formsLocked[$form] = null;
                $this->logger->addDebug(sprintf('Form %s was released', $form));
                $this->broadcast($form, array("status" => static::RELEASED));

                break;

            default:
                $this->logger->addError('Unknown method call');
                return $conn->callError($id, 'Unknown call');
                break;
        }
    }

    public function onSubscribe(ConnectionInterface $conn, $form) {


        if (!isset($this->formsSubscribers[$form])) {
            $this->formsSubscribers[$form] = new \SplObjectStorage();
        }

        $this->formsSubscribers[$form]->attach($conn);
        $this->logger->addInfo(sprintf('User %s subscribed %s', $conn->WAMP->sessionId, $form));

        $conn->event($form, array(
            "status" => empty($this->formsLocked[$form]) ? static::RELEASED : static::LOCKED
        ));



    }
    public function onUnSubscribe(ConnectionInterface $conn, $topic) {}

    public function onOpen(ConnectionInterface $conn) {

        $this->logger->addInfo(sprintf('New user %s connected', $conn->WAMP->sessionId));

    }
    public function onClose(ConnectionInterface $conn) {


        // release forms for closed connection
        foreach ($this->formsLocked as $form => $sessionId) {

            if ($conn->WAMP->sessionId === $sessionId) {
                $this->formsLocked[$form] = null;
                $this->broadcast($form, array("status" => static::RELEASED));
                $this->formsSubscribers[$form]->detach($conn);
            }

        }

    }
    public function onError(ConnectionInterface $conn, \Exception $e) {}

    protected function broadcast($form, $msg, ConnectionInterface $exclude = null) {
        foreach ($this->formsSubscribers[$form] as $client) {
            if ($client !== $exclude) {
                $client->event($form, $msg);
            }
        }
    }

}