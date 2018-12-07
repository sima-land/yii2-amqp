<?php

namespace simaland\amqp\tests\functional\components;

use simaland\amqp\components\Queue;
use simaland\amqp\tests\TestCase;

/**
 * Tests queue component
 */
class QueueTest extends TestCase
{
    /**
     * Tests queue basic operations
     */
    public function testDefault(): void
    {
        /** @var Queue $queue */
        $queue = static::$component->queues->current();
        $this->assertFalse($queue->isDeclared());
        $this->assertTrue($queue->declare());
        $this->assertTrue($queue->isDeclared());
        $queue->noWait = true;
        $this->assertNull($queue->purge());
        $this->assertNull($queue->delete());
    }
}
