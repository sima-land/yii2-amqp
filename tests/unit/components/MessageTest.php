<?php

namespace simaland\amqp\tests\unit\components;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use simaland\amqp\tests\TestCase;

/**
 * Message test class.
 */
class MessageTest extends TestCase
{
    /**
     * Tests amqp message.
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testGetAmqpMessage(): void
    {
        $message = static::$component->createMessage('Test');
        $this->assertInstanceOf(AMQPMessage::class, $amqpMessage = $message->getAmqpMessage());
        $this->assertEquals('Test', $amqpMessage->getBody());
        $this->assertEquals([
            'content_type' => 'text/plain',
            'delivery_mode' => 2,
        ], $amqpMessage->get_properties());
    }

    /**
     * Tests serialized message.
     *
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function testSerializedAmqpMessage(): void
    {
        $testData = ['Test' => 'Data'];
        $amqpMessage = static::$component->createMessage($testData)->getAmqpMessage();
        $this->assertEquals(serialize($testData), $amqpMessage->getBody());
        /** @var AMQPTable $amqpTable */
        $this->assertInstanceOf(AMQPTable::class, $amqpTable = $amqpMessage->get('application_headers'));
        $this->assertEquals([
            'amqp.serialized' => true,
        ], $amqpTable->getNativeData());
    }
}
