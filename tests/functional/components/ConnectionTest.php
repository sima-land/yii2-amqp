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
     */
    public function testAMQPConnection(): void
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
