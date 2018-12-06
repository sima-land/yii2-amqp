<?php

namespace simaland\amqp\events;

use simaland\amqp\components\Consumer;

/**
 * AMQP consume event
 */
class AMQPConsumeEvent extends AMQPMessageEvent
{
    /**
     * Event name before consume
     */
    public const BEFORE_CONSUME = 'beforeConsume';

    /**
     * Event name after consume
     */
    public const AFTER_CONSUME = 'afterConsume';

    /**
     * @var Consumer Message consumer
     */
    public $consumer;

    /**
     * @var string Queue name
     */
    public $queueName;

    /**
     * @var float Time in microseconds
     */
    public $time;

    /**
     * @var float Execute time
     */
    public $execTime = 0.0;
}
