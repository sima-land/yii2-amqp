<?php

namespace simaland\amqp\events;

use simaland\amqp\components\Message;
use yii\base\Event;

/**
 * Base AMQP message event
 */
class AMQPMessageEvent extends Event
{
    /**
     * @var Message message
     */
    public $message;
}
