<?php

namespace simaland\amqp\tests\functional\components;

use PhpAmqpLib\Message\AMQPMessage;
use simaland\amqp\components\Consumer;
use simaland\amqp\components\consumer\CallbackInterface;
use simaland\amqp\components\Exchange;
use simaland\amqp\components\Message;
use simaland\amqp\components\Producer;
use simaland\amqp\tests\_mock\TestLogger;
use simaland\amqp\tests\TestCase;

/**
 * Tests consumer component
 */
class ConsumerTest extends TestCase
{
    /**
     * Tests consumer declaration
     */
    public function testDeclare(): void
    {
        /** @var Consumer $consumer */
        $consumer = static::$component->consumer;
        $this->assertFalse($consumer->isDeclared());
        $this->assertTrue($consumer->declare());
        $this->assertTrue($consumer->isDeclared());
    }

    /**
     * Tests queue consuming
     *
     * @throws \Throwable
     */
    public function testConsume(): void
    {
        /** @var Consumer $consumer */
        $consumer = static::$component->consumer;
        /** @var Producer $producer */
        $producer = static::$component->producer;
        /** @var Exchange $exchange */
        $exchange = static::$component->exchanges->current();
        /** @var Message $message */
        $message = static::$component->createMessage('Test');
        /** @var TestLogger $logger */
        $logger = $consumer->logger;
        $exchange->declare();
        $producer->publish($message, $exchange);
        $consumer->callbacks['testQueue'] = function (AMQPMessage $message) {
            $this->assertEquals('Test', $message->getBody());

            return CallbackInterface::MESSAGE_ACK;
        };
        $this->assertEmpty($logger->successArray);
        $consumer->consume(1);
        $this->assertCount(1, $logger->successArray);
        $this->assertEquals('Message was successfully consumed.', $logger->successArray[0]);
        $logger->successArray = [];
    }
}
