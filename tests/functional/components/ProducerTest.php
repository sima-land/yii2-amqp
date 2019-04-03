<?php

namespace Simaland\Amqp\Tests\functional\components;

use Simaland\Amqp\Components\Exchange;
use Simaland\Amqp\Components\Message;
use Simaland\Amqp\Components\Producer;
use Simaland\Amqp\Events\AMQPPublishEvent;
use Simaland\Amqp\Exceptions\RuntimeException;
use Simaland\Amqp\Tests\_mock\TestLogger;
use Simaland\Amqp\Tests\TestCase;

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
