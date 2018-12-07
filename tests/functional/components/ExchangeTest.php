<?php

namespace simaland\amqp\tests\functional\components;

use simaland\amqp\components\Exchange;
use simaland\amqp\tests\TestCase;

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
