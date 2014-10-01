<?php

namespace Zmr;

use Monolog\Logger;
use Ratchet\ConnectionInterface;

/**
 * Class Responsible for form locking functionality
 * @package Zmr
 */
class FormLock extends AbstractDispatcher {

    const FORM_RELEASED = 'released';
    const FORM_LOCKED = 'locked';

    /**
     * @var string[] array of sessionIds
     */
    protected $formsLocked;

    /**
     * @var string[] array of names for each form
     */
    protected $formsLockedName;

    /**
     * @var \SplObjectStorage[] this property stores connections for each formId
     */
    protected $formsSubscribers;


    protected function init() {
        $this->formsLocked = [];
        $this->formsLockedName = [];
        $this->formsSubscribers = [];
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

        $this->log(sprintf('Call method %s executed with parameters %s', $fn, var_export($params, true)));

        switch ($fn) {

            case 'lockForm':

                $locker = $params['locker'];
                $name = $params['name'];

                // form is touched for the first time
                if (empty($this->formsLocked[$form])) {

                    $this->formsLocked[$form] = $locker;
                    $this->formsLockedName[$form] = $name;

                    $conn->callResult($id, array(
                            'event' => 'FormLock',
                            'locker' => $locker
                    ));

                    $this->log(sprintf('Form %s was locked by %s', $form, $locker));
                    $this->broadcast($form, array("status" => static::FORM_LOCKED, "name" => $name), $this->formsSubscribers, $conn);

                } else {

                    $conn->callError($id, "Form was locked", array('form' => $form, 'status' => static::FORM_LOCKED, 'locker' => $this->formsLocked[$form]));

                }

                break;

            case 'releaseForm':

                $this->formsLocked[$form] = null;
                $this->formsLockedName[$form] = null;
                $this->log(sprintf('Form %s was released', $form));
                $this->broadcast($form, array("status" => static::FORM_RELEASED), $this->formsSubscribers);

                break;

            default:
                $this->log('Unknown method call', Logger::ERROR);
                return $conn->callError($id, 'Unknown call');
                break;
        }
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Ratchet\Wamp\Topic|string $form
     */
    public function onSubscribe(ConnectionInterface $conn, $form) {


        if (!isset($this->formsSubscribers[$form])) {
            $this->formsSubscribers[$form] = new \SplObjectStorage();
        }

        $this->formsSubscribers[$form]->attach($conn);
        $this->log(sprintf('User %s subscribed %s', $conn->WAMP->sessionId, $form), Logger::INFO);

        if (!empty($this->formsLocked[$form])) {

            $conn->event($form, array(
                "status" => static::FORM_LOCKED,
                "name" => $this->formsLockedName[$form]
            ));

        }



    }

    /**
     * @param ConnectionInterface $conn
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn) {


        // release forms for closed connection
        foreach ($this->formsLocked as $formName => $sessionId) {

            if ($conn->WAMP->sessionId === $sessionId) {

                unset($this->formsLocked[$formName], $this->formsName[$formName]);
                $this->formsSubscribers[$formName]->detach($conn);

                $this->broadcast($formName, array("status" => static::FORM_RELEASED), $this->formsSubscribers);
            }

        }

    }

}