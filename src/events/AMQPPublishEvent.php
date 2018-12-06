<?php

namespace simaland\amqp\events;

use simaland\amqp\components\Producer;

/**
 * AMQP publish event
 */
class AMQPPublishEvent extends AMQPMessageEvent
{
    /**
     * Event name before publish
     */
    public const BEFORE_PUBLISH = 'beforePublish';

    /**
     * Event name after publish
     */
    public const AFTER_PUBLISH = 'afterPublish';

    /**
     * @var Producer Message producer
     */
    public $producer;
}
