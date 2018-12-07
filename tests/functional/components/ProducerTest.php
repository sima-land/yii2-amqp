<?php

namespace simaland\amqp\tests\functional\components;

use simaland\amqp\components\Exchange;
use simaland\amqp\components\Message;
use simaland\amqp\components\Producer;
use simaland\amqp\events\AMQPPublishEvent;
use simaland\amqp\exceptions\RuntimeException;
use simaland\amqp\tests\_mock\TestLogger;
use simaland\amqp\tests\TestCase;

/**
 * Tests producer component
 */
class ProducerTest extends TestCase
{
    /**
     * Tests producer declaration
     */
    public function testDeclare(): void
    {
        /** @var Producer $producer */
        $producer = static::$component->producer;
        $this->assertFalse($producer->isDeclared());
        $this->assertTrue($producer->declare());
        $this->assertTrue($producer->isDeclared());
    }

    /**
     * Tests message publishing with exception
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testPublishException(): void
    {
        /** @var Producer $producer */
        $producer = static::$component->producer;
        /** @var Message $message */
        $message = static::$component->createMessage('Test');
        /** @var Exchange $exchange */
        $exchange = static::$component->exchanges->current();
        $this->expectException(RuntimeException::class);
        $producer->publish($message, $exchange);
    }

    /**
     * Tests message publishing with exception
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testPublish(): void
    {
        /** @var Producer $producer */
        $producer = static::$component->producer;
        /** @var Message $message */
        $message = static::$component->createMessage('Test');
        /** @var Exchange $exchange */
        $exchange = static::$component->exchanges->current();
        /** @var TestLogger $logger */
        $logger = $producer->logger;
        $exchange->declare();
        $this->assertEmpty($logger->infoArray);
        $beforePublish = false;
        $afterPublish = false;
        static::$component->on(AMQPPublishEvent::BEFORE_PUBLISH, function (AMQPPublishEvent $event) use (&$beforePublish) {
            $this->assertEquals('Test', $event->message->amqpMessage->getBody());
            $beforePublish = true;
        });
        static::$component->on(AMQPPublishEvent::AFTER_PUBLISH, function (AMQPPublishEvent $event) use (&$afterPublish) {
            $this->assertEquals('Test', $event->message->amqpMessage->getBody());
            $afterPublish = true;
        });
        $this->assertFalse($beforePublish);
        $this->assertFalse($afterPublish);
        $producer->publish($message, $exchange);
        $this->assertTrue($beforePublish);
        $this->assertTrue($afterPublish);
        $this->assertCount(1, $logger->infoArray);
        $this->assertEquals('Message was sent to exchange srcExchange', $logger->infoArray[0]);
        $logger->infoArray = [];
    }
}
