<?php

namespace Simaland\Amqp\Tests\unit;

use PhpAmqpLib\Message\AMQPMessage;
use Simaland\Amqp\Collections\Exchange;
use Simaland\Amqp\Collections\Queue;
use Simaland\Amqp\Components\Connection;
use Simaland\Amqp\Components\Consumer;
use Simaland\Amqp\Components\Message;
use Simaland\Amqp\Components\Producer;
use Simaland\Amqp\Collections\Routing;
use Simaland\Amqp\Exceptions\InvalidConfigException;
use Simaland\Amqp\Tests\TestCase;

/**
 * @coversDefaultClass \Simaland\Amqp\Component
 */
class ComponentTest extends TestCase
{
    /**
     * Setter are not allowed
     *
     * @covers \Simaland\Amqp\Component::setProducer
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
     * @covers \Simaland\Amqp\Component::setConsumer
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
     * @covers \Simaland\Amqp\Component::setExchanges
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
     * @covers \Simaland\Amqp\Component::setRouting
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
     * @covers \Simaland\Amqp\Component::setConnection
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
     * @covers \Simaland\Amqp\Component::setQueues
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
     * @covers \Simaland\Amqp\Component::createMessage
     * @covers \Simaland\Amqp\Components\Message::getAmqpMessage
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
     *
     * @covers \Simaland\Amqp\Component::getConnection
     */
    public function testGetConnection(): void
    {
        $this->assertInstanceOf(Connection::class, static::$component->connection);
    }

    /**
     * Tests producer getter
     *
     * @covers \Simaland\Amqp\Component::getProducer
     */
    public function testGetProducer(): void
    {
        $this->assertInstanceOf(Producer::class, static::$component->producer);
    }

    /**
     * Tests queues getter
     *
     * @covers \Simaland\Amqp\Component::getQueues
     */
    public function testGetQueues(): void
    {
        $this->assertInstanceOf(Queue::class, static::$component->queues);
    }

    /**
     * Tests exchanges getter
     *
     * @covers \Simaland\Amqp\Component::getExchanges
     */
    public function testGetExchanges(): void
    {
        $this->assertInstanceOf(Exchange::class, static::$component->exchanges);
    }

    /**
     * Tests routing getter
     *
     * @covers \Simaland\Amqp\Component::getRouting
     */
    public function testGetRouting(): void
    {
        $this->assertInstanceOf(Routing::class, static::$component->routing);
    }

    /**
     * Tests consumer getter
     *
     * @covers \Simaland\Amqp\Component::getConsumer
     */
    public function testGetConsumer(): void
    {
        $this->assertInstanceOf(Consumer::class, static::$component->consumer);
    }

    /**
     * Tests service name generator
     *
     * @covers \Simaland\Amqp\Component::getServiceName
     */
    public function testGetServiceName(): void
    {
        $this->assertEquals('ext.amqp.testAmqp.producer', static::$component->getServiceName('producer'));
        $this->assertEquals('ext.amqp.testAmqp.testService', static::$component->getServiceName('testService'));
    }
}
