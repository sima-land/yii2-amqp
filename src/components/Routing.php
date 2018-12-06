<?php

namespace simaland\amqp\components;

use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\exceptions\RuntimeException;
use function in_array;

/**
 * Binding between exchange and queue
 */
class Routing extends AMQPObject
{
    /**
     * Binding target: queue
     */
    protected const BIND_TARGET_QUEUE = 'queue';

    /**
     * Binding target: exchange
     */
    protected const BIND_TARGET_EXCHANGE = 'exchange';

    /**
     * @var string Exchange name
     */
    public $sourceExchange;

    /**
     * @var string Queue name
     */
    public $targetQueue;

    /**
     * @var string Target exchange name
     */
    public $targetExchange;

    /**
     * @var array Routing keys
     */
    public $routingKeys = [];

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        if (empty($this->sourceExchange)) {
            throw new InvalidConfigException('Exchange name is required for binding.');
        }
        if (!\is_array($this->routingKeys)) {
            throw new InvalidConfigException('Option `routing_keys` should be an array.');
        }
        if (
            (empty($this->targetQueue) && empty($this->targetExchange))
            || (!empty($this->targetQueue) && !empty($this->targetExchange))
        ) {
            throw new InvalidConfigException('Either `queue` or `to_exchange` options should be specified to create binding.');
        }
        if ($this->component->exchanges->filterByName($this->sourceExchange)->length() === 0) {
            throw new InvalidConfigException("Exchange `{$this->sourceExchange}` is not configured.");
        }
        if (!empty($this->targetQueue) && $this->component->queues->filterByName($this->targetQueue)->length() === 0) {
            throw new InvalidConfigException("Queue `{$this->targetQueue}` is not configured.");
        }
        if (
            !empty($this->targetExchangee)
            && $this->component->exchanges->filterByName($this->targetExchange)->length() === 0
        ) {
            throw new InvalidConfigException("Target exchange `{$this->targetExchange}` is not configured.");
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function declare(): bool
    {
        if (!$this->_isDeclared && parent::declare()) {
            $this->_isDeclared = $this->declareExchange($this->sourceExchange);
            if ($this->_isDeclared) {
                if (!empty($this->targetQueue)) {
                    $this->_isDeclared = $this->declareQueue();
                    $this->bindExchangeTo(static::BIND_TARGET_QUEUE, $this->targetQueue);
                } elseif (!empty($this->targetExchange)) {
                    $this->_isDeclared = $this->declareExchange($this->targetExchange);
                    $this->bindExchangeTo(static::BIND_TARGET_EXCHANGE, $this->targetExchange);
                } else {
                    $this->_isDeclared = false;
                }
            }
        }

        return $this->_isDeclared;
    }

    /**
     * Declare queue
     *
     * @return bool
     */
    protected function declareQueue(): bool
    {
        return $this
                   ->component
                   ->queues
                   ->filterByName($this->targetQueue)
                   ->filter(function (Queue $item) {
                       return $item->declare();
                   })
                   ->length() > 0;
    }

    /**
     * Declare specified exchange (target or source)
     *
     * @param string $exchangeName Exchange name
     * @return bool
     */
    protected function declareExchange(string $exchangeName): bool
    {
        return $this
                   ->component
                   ->exchanges
                   ->filterByName($exchangeName)
                   ->filter(function (Exchange $item) {
                       return $item->declare();
                   })
                   ->length() > 0;
    }

    /**
     * Makes binding between exchange and target (toExchange or queue).
     *
     * @param string $type   Binding to type (exchange or queue)
     * @param string $target Binding target (queue name or exchange name)
     */
    protected function bindExchangeTo(string $type, string $target): void
    {
        if (!in_array($type, [static::BIND_TARGET_QUEUE, static::BIND_TARGET_EXCHANGE], true)) {
            throw new RuntimeException("Unknown binding type `{$type}`.");
        }
        $bindingMethod = "{$type}_bind";
        $routingKeys = $this->routingKeys;
        if (empty($routingKeys)) {
            $routingKeys = [''];
        }
        foreach ($routingKeys as $routingKey) {
            /** @see AMQPChannel::queue_bind(), AMQPChannel::exchange_bind() */
            $this->connection->channel->{$bindingMethod}($target, $this->sourceExchange, $routingKey);
        }
    }
}
