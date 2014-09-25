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

    public function onCall(ConnectionInterface $conn, $id, $fn, array $params) {
        switch ($fn) {
            case 'setName':
                break;

            case 'lock':

                var_dump('Form locked');

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

        $this->formsSubscribers[$form]->attach($conn);

        $conn->event(static::CTRL_FORMS, 'some message');

    }
    public function onUnSubscribe(ConnectionInterface $conn, $topic) {}

    public function onOpen(ConnectionInterface $conn) {

        $conn->Forms = new \StdClass;
        $conn->Forms->lockedForms = array();

        echo "New User Joined\n";

    }
    public function onClose(ConnectionInterface $conn) {}
    public function onError(ConnectionInterface $conn, \Exception $e) {}
}