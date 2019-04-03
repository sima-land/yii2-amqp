<?php

namespace Simaland\Amqp\Logger;

/**
 * Interface for logger component
 */
interface LoggerInterface
{
    public function info($message, array $options = []): void;

    public function error($message, array $options = []): void;

    public function success($message, array $options = []): void;
}
