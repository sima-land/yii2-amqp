<?php

namespace Simaland\Amqp\Tests\unit\components;

use Simaland\Amqp\components\Queue;
use Simaland\Amqp\exceptions\InvalidConfigException;
use Simaland\Amqp\Tests\TestCase;

/**
 * Tests queue component
 */
class QueueTest extends TestCase
{
    /**
     * Tests queue init with exception
     */
    public function testInitWithException(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Queue(static::$component->connection, static::$component);
    }

    /**
     * Tests queue normal init
     */
    public function testInit(): void
    {
        $queue = new Queue(static::$component->connection, static::$component, [
            'name' => 'testName',
        ]);
        $this->assertEquals('testName', $queue->name);
    }
}
