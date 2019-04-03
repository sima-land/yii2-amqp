<?php

namespace Simaland\Amqp\Events;

use Simaland\Amqp\Components\Message;
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
