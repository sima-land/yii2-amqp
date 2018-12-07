<?php

namespace simaland\amqp\tests\unit\components;

use simaland\amqp\components\Connection;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\tests\TestCase;
use function property_exists;

/**
 * Tests connection component
 */
class ConnectionTest extends TestCase
{
    /**
     * Tests connection init exception
     */
    public function testInitWithException(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Connection(static::$component);
    }

    /**
     * Tests connection normal init
     */
    public function testInit(): void
    {
        /** @var Connection $connection */
        $connection = new Connection(static::$component, [
            'dsn' => 'amqp://testuser:testpassword@localhost:1234/someVirtualHost?param1=value1&heartBeat=1',
        ]);
        $this->assertEquals('testuser', $connection->user);
        $this->assertEquals('testpassword', $connection->password);
        $this->assertEquals('localhost', $connection->host);
        $this->assertEquals('1234', $connection->port);
        $this->assertEquals('someVirtualHost', $connection->vHost);
        $this->assertEquals('1', $connection->heartBeat);
        $this->assertFalse(property_exists($connection, 'param1'));
    }
}
