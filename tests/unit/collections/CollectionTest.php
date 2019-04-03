<?php

namespace Simaland\Amqp\Tests\unit\collections;

use Simaland\Amqp\components\Queue;
use Simaland\Amqp\collections\Queue as QueueCollection;
use Simaland\Amqp\Tests\TestCase;
use TypeError;

/**
 * Tests components collection
 */
class CollectionTest extends TestCase
{
    /**
     * Tests collection init error
     */
    public function testError(): void
    {
        $this->expectException(TypeError::class);
        new QueueCollection(['test']);
    }

    /**
     * Tests collection
     */
    public function testAny(): void
    {
        $collection = new QueueCollection([
            $queue1 = new Queue(static::$component->connection, static::$component, [
                'name' => 'queue1',
            ]),
            $queue2 = new Queue(static::$component->connection, static::$component, [
                'name' => 'queue2',
            ]),
        ]);
        $this->assertEquals(2, $collection->length());
        $this->assertEquals($queue1, ($newCollection = $collection->filterByName('queue1'))->current());
        $this->assertEquals(1, $newCollection->length());
        $this->assertEquals('queue3', $newCollection->each(function (Queue $item) {
            $item->name = 'queue3';
        })->current()->name);
    }
}
