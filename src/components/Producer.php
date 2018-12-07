<?php

namespace simaland\amqp\components;

use simaland\amqp\events\AMQPPublishEvent;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\exceptions\RuntimeException;
use simaland\amqp\logger\ApplicationLogger;
use simaland\amqp\logger\LoggerInterface;
use yii\di\Instance;
use function is_bool;

/**
 * Service that sends AMQP Messages
 */
class Producer extends AMQPObject
{
    /**
     * @var bool Is producer safe
     */
    public $safe = true;

    /**
     * @var LoggerInterface
     */
    public $logger = [
        'class' => ApplicationLogger::class,
    ];

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        if (!is_bool($this->safe)) {
            throw new InvalidConfigException('Producer option `safe` should be of type boolean.');
        }
        $this->logger = Instance::ensure($this->logger, LoggerInterface::class);
        parent::init();
    }

    /**
     * Publishes the message and merges additional properties with basic properties
     *
     * @param Message  $message    Message for publishing
     * @param Exchange $exchange   Exchange which is used for publishing message
     * @param string   $routingKey Additional routing key
     * @throws RuntimeException
     */
    public function publish(
        Message $message,
        Exchange $exchange,
        string $routingKey = ''
    ): void {
        if ($this->safe && !$exchange->isDeclared()) {
            throw new RuntimeException("Exchange `{$exchange->name}` does not declared in broker (You see this message because safe mode is ON).");
        }
        try {
            $this->component->trigger(AMQPPublishEvent::BEFORE_PUBLISH, \Yii::createObject([
                'class' => AMQPPublishEvent::class,
                'message' => $message,
                'producer' => $this,
            ]));
            $this->connection->channel->basic_publish($message->amqpMessage, $exchange->name, $routingKey);
            $this->component->trigger(AMQPPublishEvent::AFTER_PUBLISH, \Yii::createObject([
                'class' => AMQPPublishEvent::class,
                'message' => $message,
                'producer' => $this,
            ]));
            $this->logger->info("Message was sent to exchange {$exchange->name}");
        } catch (\yii\base\InvalidConfigException $e) {
            $this->logger->error("Couldn't initialize publish event. " . $e->getMessage());
            throw new RuntimeException("Couldn't initialize publish event.", $e->getCode(), $e);
        }
    }
}
