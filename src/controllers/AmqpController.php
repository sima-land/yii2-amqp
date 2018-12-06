<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace simaland\amqp\controllers;

use BadFunctionCallException;
use InvalidArgumentException;
use simaland\amqp\components\Consumer;
use simaland\amqp\components\Exchange;
use simaland\amqp\Component;
use simaland\amqp\components\Queue;
use simaland\amqp\components\Routing;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\di\Instance;
use yii\helpers\Console;
use function array_merge;
use function array_values;
use function defined;
use function define;
use function function_exists;
use function extension_loaded;
use function is_numeric;
use function posix_isatty;
use function feof;
use function fread;

/**
 * AMQP extension functionality
 */
class AmqpController extends Controller
{
    /**
     * @var array Options aliases
     */
    protected const OPTIONS = [
        'm' => 'messagesLimit',
        'l' => 'memoryLimit',
        'd' => 'debug',
        'w' => 'withoutSignals',
        'c' => 'configuration',
    ];

    /**
     * @var int Memory limit
     */
    public $memoryLimit = 0;

    /**
     * @var int Messages limit
     */
    public $messagesLimit = 0;

    /**
     * @var bool Debug flag
     */
    public $debug = false;

    /**
     * @var bool Without pcntl signals
     */
    public $withoutSignals = false;

    /**
     * @var Component Configuration component id
     */
    public $component = 'amqp';

    /**
     * @inheritdoc
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->component = Instance::ensure($this->component, Component::class);
    }

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), array_values(static::OPTIONS));
    }

    /**
     * @inheritdoc
     */
    public function optionAliases(): array
    {
        return array_merge(parent::optionAliases(), static::OPTIONS);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($event): bool
    {
        if (!defined('AMQP_WITHOUT_SIGNALS')) {
            define('AMQP_WITHOUT_SIGNALS', $this->withoutSignals);
        }
        if (!defined('AMQP_DEBUG')) {
            if ($this->debug === 'false') {
                $this->debug = false;
            }
            define('AMQP_DEBUG', (bool)$this->debug);
        }

        return parent::beforeAction($event);
    }

    /**
     * Run a consumer
     *
     * @return int
     * @throws \Throwable
     */
    public function actionConsume(): int
    {
        $consumer = $this->component->consumer;
        $this->validateConsumerOptions($consumer);
        /** @noinspection NotOptimalIfConditionsInspection */
        if (
            (null !== $this->memoryLimit)
            && \ctype_digit((string)$this->memoryLimit)
            && $this->memoryLimit > 0
        ) {
            $consumer->memoryLimit = $this->memoryLimit;
        }

        return $consumer->consume($this->messagesLimit);
    }

    /**
     * Publish a message from STDIN to the queue
     *
     * @param string $exchangeName
     * @param string $routingKey
     * @return int
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function actionPublish(string $exchangeName, string $routingKey = ''): int
    {
        $producer = $this->component->producer;
        $data = '';
        if (posix_isatty(\STDIN)) {
            $this->stderr(Console::ansiFormat("Please pipe in some data in order to send it.\n", [Console::FG_RED]));

            return ExitCode::UNSPECIFIED_ERROR;
        }
        while (!feof(\STDIN)) {
            $data .= fread(\STDIN, 8192);
        }
        /** @var Exchange $exchange */
        $exchange = $this->component->exchanges->filterByName($exchangeName)->current();
        $producer->publish($this->component->createMessage($data), $exchange, $routingKey);
        $this->stdout("Message was successfully published.\n", Console::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Create AMQP exchanges, queues and bindings based on configuration
     *
     * @return int
     * @throws \RuntimeException
     */
    public function actionDeclareAll(): int
    {
        $result = $this
                      ->component
                      ->routing
                      ->filter(function (Routing $item) {
                          return $item->declare();
                      })
                      ->length() > 0;
        if ($result) {
            $this->stdout(
                Console::ansiFormat("All configured entries was successfully declared.\n", [Console::FG_GREEN])
            );

            return ExitCode::OK;
        }
        $this->stderr(Console::ansiFormat("No queues, exchanges or bindings configured.\n", [Console::FG_RED]));

        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Delete all messages from the queue
     *
     * @param string $queueName
     * @return int
     * @throws \RuntimeException
     */
    public function actionPurgeQueue(string $queueName): int
    {
        if ($this->interactive) {
            $input = Console::prompt(
                'Are you sure you want to delete all messages inside that queue?',
                ['default' => 'yes']
            );
            if ($input !== 'yes') {
                $this->stderr(Console::ansiFormat("Aborted.\n", [Console::FG_RED]));

                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $this->component->queues->filterByName($queueName)->each(function (Queue $item) {
            $item->purge();
        });
        $this->stdout(Console::ansiFormat("Queue `{$queueName}` was purged.\n", [Console::FG_GREEN]));

        return ExitCode::OK;
    }

    /**
     * Validate options passed by user
     *
     * @param Consumer $consumer
     */
    private function validateConsumerOptions(Consumer $consumer): void
    {
        if (!AMQP_WITHOUT_SIGNALS && extension_loaded('pcntl')) {
            if (!function_exists('pcntl_signal')) {
                throw new BadFunctionCallException("Function 'pcntl_signal' is referenced in the php.ini 'disable_functions' and can't be called.");
            }
            \pcntl_signal(SIGTERM, [$consumer, 'stopDaemon']);
            \pcntl_signal(SIGINT, [$consumer, 'stopDaemon']);
            \pcntl_signal(SIGHUP, [$consumer, 'restartDaemon']);
        }

        $this->messagesLimit = (int)$this->messagesLimit;
        $this->memoryLimit = (int)$this->memoryLimit;
        if (!is_numeric($this->messagesLimit) || 0 > $this->messagesLimit) {
            throw new InvalidArgumentException('The -m, --messagesLimit option should be null or greater than 0');
        }
        if (!is_numeric($this->memoryLimit) || 0 > $this->memoryLimit) {
            throw new InvalidArgumentException('The -l, --memoryLimit option should be null or greater than 0');
        }
    }
}
