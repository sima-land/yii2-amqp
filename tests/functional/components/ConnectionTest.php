<?php

namespace Simaland\Amqp\Tests\functional\components;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Simaland\Amqp\Tests\TestCase;

/**
 * Tests connection component
 */
class ConnectionTest extends TestCase
{
    /**
     * Tests AMQP connection
     * @covers \Simaland\Amqp\Components\Connection
     * @covers \Simaland\Amqp\Component::getConnection
     * @covers \Simaland\Amqp\Component::getServiceName
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
