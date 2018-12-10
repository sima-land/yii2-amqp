<?php

namespace simaland\amqp\tests\functional\components;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use simaland\amqp\tests\TestCase;

/**
 * Tests connection component
 */
class ConnectionTest extends TestCase
{
    /**
     * Tests AMQP connection
     * @covers \simaland\amqp\components\Connection
     * @covers \simaland\amqp\Component::getConnection
     * @covers \simaland\amqp\Component::getServiceName
     */
    public function testDefault(): void
    {
        $this->assertInstanceOf(
            AMQPStreamConnection::class,
            ($connection = static::$component->connection)->amqpConnection
        );
        $this->assertFalse($connection->reconnect());
        $this->assertInstanceOf(
            AMQPChannel::class,
            $connection->channel
        );
        $this->assertTrue($connection->amqpConnection->isConnected());
        $this->assertTrue($connection->reconnect());
        $this->assertFalse($connection->close());
    }
}
