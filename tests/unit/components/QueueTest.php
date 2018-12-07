<?php

namespace simaland\amqp\tests\unit\components;

use simaland\amqp\components\Queue;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\tests\TestCase;

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
