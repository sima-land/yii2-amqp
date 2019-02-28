<?php

namespace simaland\amqp\tests\unit\components;

use simaland\amqp\components\Exchange;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\tests\TestCase;

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
