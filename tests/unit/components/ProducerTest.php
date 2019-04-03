<?php

namespace Simaland\Amqp\Tests\unit\components;

use Simaland\Amqp\components\Producer;
use Simaland\Amqp\exceptions\InvalidConfigException;
use Simaland\Amqp\Tests\TestCase;

/**
 * Tests producer component
 */
class ProducerTest extends TestCase
{
    /**
     * Tests producer init with exception
     */
    public function testInitWithException(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Producer(static::$component->connection, static::$component, [
            'safe' => '1',
        ]);
    }

    /**
     * Tests producer init with no logger exception
     */
    public function testInitWithNoLogger(): void
    {
        $this->expectException(\yii\base\InvalidConfigException::class);
        new Producer(static::$component->connection, static::$component, [
            'logger' => null,
        ]);
    }
}
