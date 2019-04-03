<?php

namespace Simaland\Amqp\Components\Consumers;

use PhpAmqpLib\Message\AMQPMessage;

/**
 * Consumer interface
 */
interface CallbackInterface
{
    /**
     * Flag for message ack
     */
    public const MESSAGE_ACK = 0;

    /**
     * Flag for reject and drop message
     */
    public const MESSAGE_REJECT = 1;

    /**
     * Flag for reject and requeue message
     */
    public const MESSAGE_REQUEUE = 2;

    /**
     * @param AMQPMessage $message AMQP message
     * @return mixed false to reject and requeue, any other value to acknowledge
     */
    public function execute(AMQPMessage $message);
}
