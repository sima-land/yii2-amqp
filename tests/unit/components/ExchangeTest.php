<?php

namespace Simaland\Amqp\Tests\unit\components;

use Simaland\Amqp\components\Exchange;
use Simaland\Amqp\exceptions\InvalidConfigException;
use Simaland\Amqp\Tests\TestCase;

/**
 * Tests exchange component
 */
class ExchangeTest extends TestCase
{
    /**
     * Tests exchange init with exception
     */
    public function testInitWithException(): void
    {
        $this->expectException(InvalidConfigException::class);
        new Exchange(static::$component->connection, static::$component, [
            'name' => 'testExchange',
            'type' => 'testType',
        ]);
    }

    /**
     * Tests exchange normal init
     */
    public function testInit(): void
    {
        $exchange = new Exchange(static::$component->connection, static::$component, [
            'name' => 'testExchange',
            'type' => 'direct',
        ]);
        $this->assertEquals('direct', $exchange->type);
    }

    /**
     * Tests declaration disabling
     */
    public function testDeclarationDisabled(): void
    {
        $exchange = new Exchange(static::$component->connection, static::$component, [
            'name' => 'textExchange',
            'type' => 'direct',
            'declaration' => Exchange::DECLARATION_DISABLE,
        ]);
        $this->assertTrue($exchange->isDeclarationDisabled());
        $this->assertTrue($exchange->declare());
    }
}
