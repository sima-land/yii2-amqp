<?php

namespace simaland\amqp\components;

use simaland\amqp\Component;
use PhpAmqpLib\Channel\AMQPChannel;

/**
 * Base AMQP connection object
 */
abstract class AMQPObject extends ConfigurationObject
{
    /**
     * @var bool Is needed object auto-declaration
     */
    public $autoDeclare = false;

    /**
     * @var Connection Connection component property
     */
    protected $connection;

    /**
     * @var bool State of object declaration
     */
    protected $_isDeclared = false;

    /**
     * @inheritdoc
     * @param Connection $connection
     */
    public function __construct(Connection $connection, Component $component, array $config = [])
    {
        $this->connection = $connection;
        parent::__construct($component, $config);
    }

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        if ($this->autoDeclare && $this->connection->amqpConnection->connectOnConstruct()) {
            $this->declare();
        }
    }

    /**
     * Declare and returns its state
     *
     * @return bool
     */
    public function declare(): bool
    {
        if (!$this->_isDeclared) {
            $this->_isDeclared = $this->connection->channel instanceof AMQPChannel;
        }

        return $this->_isDeclared;
    }

    /**
     * Returns object declaration state
     *
     * @return bool
     */
    public function isDeclared(): bool
    {
        return $this->_isDeclared;
    }
}
