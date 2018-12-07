<?php

namespace simaland\amqp\tests\functional\components;

use simaland\amqp\components\Routing;
use simaland\amqp\tests\TestCase;

/**
 * Tests routing component
 */
class RoutingTest extends TestCase
{
    /**
     * Tests routing declaration
     */
    public function testDeclare(): void
    {
        /** @var Routing $routing */
        $routing = static::$component->routing->current();
        $this->assertFalse($routing->isDeclared());
        $this->assertTrue($routing->declare());
        $this->assertTrue($routing->isDeclared());
    }
}
