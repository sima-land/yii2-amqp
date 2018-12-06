<?php

namespace simaland\amqp\tests;

use simaland\amqp\Component;
use simaland\amqp\tests\_mock\TestQueueCallback;

/**
 * Base test case class.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Component
     */
    protected static $component;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$component = \Yii::createObject([
            'class' => Component::class,
            'id' => 'testAmqp',
            'connection' => [
                'host' => '127.0.0.1',
            ],
            'queues' => [
                [
                    'name' => 'testQueue',
                ],
            ],
            'exchanges' => [
                [
                    'name' => 'srcExchange',
                    'type' => 'direct',
                ],
                [
                    'name' => 'tgtExchange',
                    'type' => 'direct',
                ],
            ],
            'routing' => [
                [
                    'sourceExchange' => 'srcExchange',
                    'targetQueue' => 'testQueue',
                ],
                [
                    'sourceExchange' => 'srcExchange',
                    'targetExchange' => 'tgtExchange',
                ],
            ],
            'consumer' => [
                'callbacks' => [
                    'testQueue' => TestQueueCallback::class,
                ],
            ],
        ]);
    }
}
