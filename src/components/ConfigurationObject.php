<?php

namespace simaland\amqp\components;

use simaland\amqp\Component;
use yii\base\BaseObject;

/**
 * Common configuration object
 */
abstract class ConfigurationObject extends BaseObject
{
    /**
     * @var string Configuration object name
     */
    public $name;

    /**
     * @var Component AMQP configuration component
     */
    protected $component;

    /**
     * @inheritdoc
     * @param Component $component
     */
    public function __construct(Component $component, array $config = [])
    {
        $this->component = $component;
        parent::__construct($config);
    }
}
