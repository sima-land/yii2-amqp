<?php

namespace simaland\amqp\components;

use simaland\amqp\exceptions\InvalidConfigException;

/**
 * Queue object
 */
class Queue extends AMQPObject
{
    /**
     * @var bool Is passive
     */
    public $passive = false;

    /**
     * @var bool Is durable
     */
    public $durable = true;

    /**
     * @var bool Is exclusive
     */
    public $exclusive = false;

    /**
     * @var bool Allow auto delete
     */
    public $autoDelete = false;

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
            throw new InvalidConfigException('Queue name is required.');
        }
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function declare(): bool
    {
        if (
            !$this->_isDeclared
            && parent::declare()
            && !$this->isDeclarationDisabled()
        ) {
            $this->connection->channel->queue_declare(
                $this->name,
                $this->passive,
                $this->durable,
                $this->exclusive,
                $this->autoDelete,
                $this->noWait,
                $this->arguments,
                $this->ticket
            );
        }

        return $this->_isDeclared;
    }

    /**
     * Purge queue
     *
     * @return mixed|null
     */
    public function purge()
    {
        return $this->connection->channel->queue_purge($this->name, $this->noWait, $this->ticket);
    }

    /**
     * Delete queue
     *
     * @param bool $isUnused If queue is unused
     * @param bool $isEmpty  If queue is empty
     *
     * @return mixed|null
     */
    public function delete($isUnused = true, $isEmpty = true)
    {
        return $this->connection->channel->queue_delete($this->name, $isUnused, $isEmpty, $this->noWait, $this->ticket);
    }
}
