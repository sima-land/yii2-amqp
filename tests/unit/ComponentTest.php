<?php

namespace simaland\amqp\tests\unit;

use PhpAmqpLib\Message\AMQPMessage;
use simaland\amqp\collections\Exchange;
use simaland\amqp\collections\Queue;
use simaland\amqp\components\Connection;
use simaland\amqp\components\Consumer;
use simaland\amqp\components\Message;
use simaland\amqp\components\Producer;
use simaland\amqp\collections\Routing;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\tests\TestCase;

class ComponentTest extends TestCase
{
    /**
     * Setter are not allowed
     *
     * @throws InvalidConfigException
     */
    public function testSetProducer(): void
    {
        $this->expectException(InvalidConfigException::class);
        static::$component->setProducer();
    }

    /**
     * Setter are not allowed
     *
     * @throws InvalidConfigException
     */
    public function testSetConsumer(): void
    {
        $this->expectException(InvalidConfigException::class);
        static::$component->setConsumer();
    }

    /**
     * Setter are not allowed
     *
     * @throws InvalidConfigException
     */
    public function testSetExchanges(): void
    {
        $this->expectException(InvalidConfigException::class);
        static::$component->setExchanges();
    }

    /**
     * Setter are not allowed
     *
     * @throws InvalidConfigException
     */
    public function testSetRouting(): void
    {
        $this->expectException(InvalidConfigException::class);
        static::$component->setRouting();
    }

    /**
     * Setter are not allowed
     *
     * @throws InvalidConfigException
     */
    public function testSetConnection(): void
    {
        $this->expectException(InvalidConfigException::class);
        static::$component->setConnection();
    }

    /**
     * Setter are not allowed
     *
     * @throws InvalidConfigException
     */
    public function testSetQueues(): void
    {
        $this->expectException(InvalidConfigException::class);
        static::$component->setQueues();
    }

    /**
     * Tests message creating
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testCreateMessage(): void
    {
        $this->assertInstanceOf(Message::class, $message = static::$component->createMessage('Test'));
        $this->assertInstanceOf(AMQPMessage::class, $message->amqpMessage);
    }

    /**
     * Tests connection getter
     */
    public function testGetConnection(): void
    {
        $this->assertInstanceOf(Connection::class, static::$component->connection);
    }

    /**
     * Tests producer getter
     */
    public function testGetProducer(): void
    {
        $this->assertInstanceOf(Producer::class, static::$component->producer);
    }

    /**
     * Tests queues getter
     */
    public function testGetQueues(): void
    {
        $this->assertInstanceOf(Queue::class, static::$component->queues);
    }

    /**
     * Tests exchanges getter
     */
    public function testGetExchanges(): void
    {
        $this->assertInstanceOf(Exchange::class, static::$component->exchanges);
    }

    /**
     * Tests routing getter
     */
    public function testGetRouting(): void
    {
        $this->assertInstanceOf(Routing::class, static::$component->routing);
    }

    /**
     * Tests consumer getter
     */
    public function testGetConsumer(): void
    {
        $this->assertInstanceOf(Consumer::class, static::$component->consumer);
    }

    /**
     * Tests service name generator
     */
    public function testGetServiceName(): void
    {
        $this->assertEquals('ext.amqp.testAmqp.producer', static::$component->getServiceName('producer'));
        $this->assertEquals('ext.amqp.testAmqp.testService', static::$component->getServiceName('testService'));
    }
}
