<?php

namespace simaland\amqp\tests\_mock;

use simaland\amqp\logger\LoggerInterface;

/**
 * Application logger mock
 */
class TestLogger implements LoggerInterface
{
    /**
     * @var array List of info messages
     */
    public $infoArray = [];

    /**
     * @var array List of error messages
     */
    public $errorArray = [];

    /**
     * @var array List of success messages
     */
    public $successArray = [];

    /**
     * @inheritdoc
     */
    public function info($message, array $options = []): void
    {
        $this->infoArray[] = $message;
    }

    /**
     * @inheritdoc
     */
    public function error($message, array $options = []): void
    {
        $this->errorArray[] = $message;
    }

    /**
     * @inheritdoc
     */
    public function success($message, array $options = []): void
    {
        $this->successArray[] = $message;
    }
}
