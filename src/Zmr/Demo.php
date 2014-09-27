<?php
/**
 * Created by PhpStorm.
 * User: tworzenieweb
 * Date: 24.09.14
 * Time: 15:50
 */

namespace Zmr;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

/**
 * A simple pub/sub implementation
 * Anything clients publish on a topic will be received
 *  on that topic by all clients
 */
class Demo implements WampServerInterface {

    const CTRL_FORMS = 'zmr:forms';
    const RELEASED = 'released';
    const LOCKED = 'locked';

    protected $formsLocked;

    /**
     * @var \SplObjectStorage[]
     */
    protected $formsSubscribers;

    public function __construct() {
        $this->formsLocked = [];
        $this->formsSubscribers = [];
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

                    $this->broadcast($form, array("status" => static::LOCKED), $conn);


                } else {

                    $conn->callError($id, "Form was locked", array('form' => $form, 'status' => static::LOCKED, 'locker' => $this->formsLocked[$form]));

                }

                break;

            case 'releaseForm':

                $this->formsLocked[$form] = null;

                $this->broadcast($form, array("status" => static::RELEASED));

                break;

            default:
                return $conn->callError($id, 'Unknown call');
                break;
        }
    }

    public function onSubscribe(ConnectionInterface $conn, $form) {


        if (!isset($this->formsSubscribers[$form])) {
            $this->formsSubscribers[$form] = new \SplObjectStorage();
        }

        echo sprintf("User %s subscribed", $conn->WAMP->sessionId);

        $this->formsSubscribers[$form]->attach($conn);

        $conn->event($form, array(
            "status" => $this->formsLocked[$form] ? static::LOCKED : static::RELEASED
        ));



    }
    public function onUnSubscribe(ConnectionInterface $conn, $topic) {}

    public function onOpen(ConnectionInterface $conn) {

        echo "New User Joined\n";

    }
    public function onClose(ConnectionInterface $conn) {


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