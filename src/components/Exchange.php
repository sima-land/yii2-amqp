<?php

namespace simaland\amqp\components;

use simaland\amqp\exceptions\InvalidConfigException;

/**
 * Exchange object
 *
 * @property-read array $allowedTypes
 */
class Exchange extends AMQPObject
{
    /**
     * @var string Type
     */
    public $type;

    /**
     * @var bool Is passive
     */
    public $passive = false;

    /**
     * @var bool Is durable
     */
    public $durable = true;

    /**
     * @var bool Is auto delete
     */
    public $autoDelete = false;

    /**
     * @var bool Is internal
     */
    public $internal = false;

    /**
     * @var bool Is no wait
     */
    public $noWait = false;

    /**
     * @var array Arguments
     */
    public $arguments;

    /**
     * @var string Ticket
     */
    public $ticket;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        if (empty($this->name)) {
            throw new InvalidConfigException('Exchange name should be specified.');
        }
        if (empty($this->type)) {
            throw new InvalidConfigException('Exchange type should be specified.');
        }
        if (!\in_array($this->type, $this->allowedTypes, true)) {
            $exchangeTypes = \implode(', ', $this->allowedTypes);
            throw new InvalidConfigException("Unknown exchange type `{$this->type}`. Allowed values are: {$exchangeTypes}.");
        }
        parent::init();
    }

    /**
     * Returns allowed types
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return [
            'direct',
            'topic',
            'fanout',
            'headers',
        ];
    }

    /**
     * @inheritdoc
     */
    public function declare(): bool
    {
        if (!$this->_isDeclared && parent::declare()) {
            $this->connection->channel->exchange_declare(
                $this->name,
                $this->type,
                $this->passive,
                $this->durable,
                $this->autoDelete,
                $this->internal,
                $this->noWait,
                $this->arguments,
                $this->ticket
            );
        }

        return $this->_isDeclared;
    }
}
