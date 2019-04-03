<?php

namespace Simaland\Amqp\Tests\unit\components;

use Simaland\Amqp\components\Consumer;
use Simaland\Amqp\exceptions\InvalidConfigException;
use Simaland\Amqp\Tests\_mock\TestQueueCallback;
use Simaland\Amqp\Tests\TestCase;

/**
 * Tests consumer component
 */
class ConsumerTest extends TestCase
{
    /**
     * Tests consumer init with exception
     */
    public function testInitWithException(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Consumer(static::$component->connection, static::$component);
    }

    /**
     * Tests consumer init without deserializer as valid callable
     */
    public function testInitWithNoDeserializer(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Consumer(static::$component->connection, static::$component, [
            'callbacks' => [
                'testQueue' => TestQueueCallback::class,
            ],
            'deserializer' => 'unknown_callback_function',
        ]);
    }

    /**
     * Tests consumer normal init
     */
    public function testInit(): void
    {
        $consumer = new Consumer(static::$component->connection, static::$component, [
            'callbacks' => [
                'testQueue' => TestQueueCallback::class,
            ],
        ]);
        $this->assertEquals('unserialize', $consumer->deserializer);
    }
}
