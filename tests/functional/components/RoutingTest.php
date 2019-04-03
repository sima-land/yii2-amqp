<?php

namespace Simaland\Amqp\Tests\functional\components;

use Simaland\Amqp\components\Routing;
use Simaland\Amqp\Tests\TestCase;

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
