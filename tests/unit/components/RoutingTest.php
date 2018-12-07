<?php

namespace simaland\amqp\tests\unit\components;

use simaland\amqp\components\Routing;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\tests\TestCase;

/**
 * Tests routing component
 */
class RoutingTest extends TestCase
{
    /**
     * Tests routing init with exception
     */
    public function testInitWithException(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Routing(static::$component->connection, static::$component);
    }

    /**
     * Tests routing normal init
     */
    public function testInit(): void
    {
        $routing = new Routing(static::$component->connection, static::$component, [
            'sourceExchange' => 'srcExchange',
            'targetQueue' => 'testQueue',
        ]);
        $this->assertEquals('srcExchange', $routing->sourceExchange);
        $this->assertEquals('testQueue', $routing->targetQueue);
        $this->assertNull($routing->targetExchange);
    }
}
