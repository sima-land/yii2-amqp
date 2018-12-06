<?php

namespace simaland\amqp\tests\_mock;

use PhpAmqpLib\Message\AMQPMessage;
use simaland\amqp\components\consumer\CallbackInterface;

/**
 * Test consumer callback
 */
class TestQueueCallback implements CallbackInterface
{
    /**
     * @inheritdoc
     */
    public function execute(AMQPMessage $message)
    {
        return self::MESSAGE_ACK;
    }
}
