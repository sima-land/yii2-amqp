<?php

namespace Simaland\Amqp\Tests\functional\components;

use Simaland\Amqp\Components\Exchange;
use Simaland\Amqp\Tests\TestCase;

/**
 * Tests exchange component
 */
class ExchangeTest extends TestCase
{
    /**
     * Tests exchange declaration
     */
    public function testDeclare(): void
    {
        /** @var Exchange $exchange */
        $exchange = static::$component->exchanges->current();
        $this->assertFalse($exchange->isDeclared());
        $this->assertTrue($exchange->declare());
        $this->assertTrue($exchange->isDeclared());
    }
}
