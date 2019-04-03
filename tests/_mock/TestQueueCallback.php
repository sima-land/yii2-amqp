<?php

namespace Simaland\Amqp\Tests\_mock;

use PhpAmqpLib\Message\AMQPMessage;
use Simaland\Amqp\Components\Consumers\CallbackInterface;

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
