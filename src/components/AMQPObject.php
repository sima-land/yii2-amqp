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
     * Disable component declaration.
     */
    public const DECLARATION_DISABLE = 0;

    /**
     * Enable component declaration.
     */
    public const DECLARATION_ENABLE = 1;

    /**
     * Component auto declaration.
     */
    public const DECLARATION_AUTO = 2;

    /**
     * @var int Declaration mode
     */
    public $declaration = self::DECLARATION_ENABLE;

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
        if (
            $this->declaration === static::DECLARATION_AUTO
            && $this->connection->amqpConnection->connectOnConstruct()
        ) {
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
     * Checks whether AMQP component declaration is disabled
     *
     * @return bool
     */
    public function isDeclarationDisabled(): bool
    {
        return $this->declaration === static::DECLARATION_DISABLE;
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
