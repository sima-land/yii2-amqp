<?php

namespace simaland\amqp\logger;

use yii\base\BaseObject;

/**
 * Console logger
 */
class ConsoleLogger extends BaseObject implements LoggerInterface
{
    public function info($message, array $options = []): void
    {

    }

    public function error($message, array $options = []): void
    {

    }

    public function success($message, array $options = []): void
    {

    }
}