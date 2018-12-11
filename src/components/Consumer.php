<?php

namespace simaland\amqp\components;

use BadFunctionCallException;
use simaland\amqp\components\consumer\CallbackInterface;
use simaland\amqp\events\AMQPConsumeEvent;
use simaland\amqp\exceptions\InvalidConfigException;
use simaland\amqp\exceptions\RuntimeException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use simaland\amqp\logger\ConsoleLogger;
use simaland\amqp\logger\LoggerInterface;
use yii\console\ExitCode;
use yii\di\Instance;
use Yii;
use Throwable;
use function is_bool;
use function is_string;
use function is_array;
use function is_callable;
use function count;
use function array_keys;
use function call_user_func;
use function extension_loaded;
use function function_exists;
use function defined;
use function microtime;
use function memory_get_usage;
use function sprintf;
use function uniqid;

/**
 * Service that receives AMQP Messages
 *
 * @property-read int $consumed
 */
class Consumer extends AMQPObject
{
    /**
     * @var array Format 'queue name' => CallbackInterface::class
     */
    public $callbacks = [];

    /**
     * @var array QOS configuration
     */
    public $qos = [
        'prefetch_size' => 0,
        'prefetch_count' => 0,
        'global' => false,
    ];

    /**
     * @var int Idle timeout in milliseconds
     */
    public $idleTimeout;

    /**
     * @var int Exit code on timeout
     */
    public $idleTimeoutExitCode = ExitCode::UNSPECIFIED_ERROR;

    /**
     * @var bool While consumer can proceed on exception thrown
     */
    public $proceedOnException = false;

    /**
     * @var callable Queue message deserializer
     */
    public $deserializer = 'unserialize';

    /**
     * @var int Memory limit
     */
    public $memoryLimit = 0;

    /**
     * @var string Unique id generator prefix
     * @see \uniqid()
     */
    public $uniqueIdPrefix = 'amqp.consumer';

    /**
     * @var LoggerInterface
     */
    public $logger = [
        'class' => ConsoleLogger::class,
    ];

    /**
     * @var string Id
     */
    private $_id;

    /**
     * @var int Current message amount
     */
    private $_messagesLimit;

    /**
     * @var int Consume count
     */
    private $_consumed = 0;

    /**
     * @var bool State of consuming force stop
     */
    private $_forceStop = false;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        if (empty($this->callbacks)) {
            throw new InvalidConfigException('No callbacks specified for this consumer.');
        }
        if (!is_array($this->qos)) {
            throw new InvalidConfigException('Consumer option `qos` should be of type array.');
        }
        if (!is_bool($this->proceedOnException)) {
            throw new InvalidConfigException('Consumer option `proceedOnException` should be of type boolean.');
        }
        foreach ($this->callbacks as $queueName => $callback) {
            if ($this->component->queues->filterByName($queueName)->length() === 0) {
                throw new InvalidConfigException("Queue `{$queueName}` is not configured.");
            }
            if (!(is_string($callback) || is_callable($callback))) {
                throw new InvalidConfigException('Consumer `callback` parameter value should be a class name or service name in DI container or callable.');
            }
            if (!is_callable($callback)) {
                $callback = [Instance::ensure($callback, CallbackInterface::class), 'execute'];
            }
            $this->callbacks[$queueName] = $callback;
        }
        if (!is_callable($this->deserializer)) {
            throw new InvalidConfigException('Consumer `deserializer` option should be a callable.');
        }
        $this->logger = Instance::ensure($this->logger, LoggerInterface::class);
        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function declare(): bool
    {
        if (!$this->_isDeclared && parent::declare()) {
            $this->component->routing->each(function (Routing $routing) {
                $this->_isDeclared = $this->_isDeclared && $routing->declare();
            });
        }

        return $this->_isDeclared;
    }

    /**
     * Returns consumed value
     */
    public function getConsumed(): int
    {
        return $this->_consumed;
    }

    /**
     * Resets the consumed property.
     * Use when you want to call start() or consume() multiple times.
     */
    public function resetConsumed(): void
    {
        $this->_consumed = 0;
    }

    /**
     * Consume designated number of messages (0 means infinite)
     *
     * @param int $messagesLimit Consuming message limit (0 - infinite)
     * @return int
     * @throws Throwable
     */
    public function consume(int $messagesLimit = 0): int
    {
        if (!$this->isDeclared()) {
            throw new RuntimeException('One of defined routes wasn\'t successfully declared.');
        }
        $this->applyQosOptions();
        $this->_messagesLimit = $messagesLimit;
        $this->startConsuming();
        // At the end of the callback execution
        while (count($this->connection->channel->callbacks)) {
            if ($this->maybeStopConsumer()) {
                break;
            }
            try {
                $this->connection->channel->wait(null, false, $this->idleTimeout);
            } catch (AMQPTimeoutException $e) {
                if (null !== $this->idleTimeoutExitCode) {
                    return $this->idleTimeoutExitCode;
                }

                throw $e;
            }
        }

        return ExitCode::OK;
    }

    /**
     * Stop consuming messages
     */
    public function stopConsuming(): void
    {
        foreach (array_keys($this->callbacks) as $queueName) {
            $this->connection->channel->basic_cancel($this->getConsumerTag($queueName), false, true);
        }
    }

    /**
     * Force stop the consumer
     */
    public function stopDaemon(): void
    {
        $this->_forceStop = true;
        $this->stopConsuming();
        $this->logger->info('Consumer stopped by user.');
    }

    /**
     * Force restart the consumer
     *
     * @throws Throwable
     */
    public function restartDaemon(): void
    {
        $this->stopConsuming();
        $this->connection->reconnect();
        $this->resetConsumed();
        $this->logger->info('Consumer has been restarted.');
        $this->consume($this->_messagesLimit);
    }

    /**
     * Apply the qos settings for the current channel
     * This method needs a connection to broker
     */
    protected function applyQosOptions(): void
    {
        if (empty($this->qos)) {
            return;
        }
        $prefetchSize = $this->qos['prefetch_size'] ?? null;
        $prefetchCount = $this->qos['prefetch_count'] ?? null;
        $global = $this->qos['global'] ?? null;
        $this->connection->channel->basic_qos($prefetchSize, $prefetchCount, $global);
    }

    /**
     * Start consuming messages
     *
     * @throws RuntimeException
     * @throws Throwable
     */
    protected function startConsuming(): void
    {
        $this->_id = $this->generateUniqueId();
        foreach ($this->callbacks as $queueName => $callback) {
            $this->connection->channel->basic_consume(
                $queueName,
                $this->getConsumerTag($queueName),
                null,
                null,
                null,
                null,
                function (AMQPMessage $message) use ($queueName, $callback) {
                    // Execute user-defined callback
                    $this->onReceive($message, $queueName, $callback);
                }
            );
        }
    }

    /**
     * Decide whether it's time to stop consuming
     *
     * @throws BadFunctionCallException
     */
    protected function maybeStopConsumer(): bool
    {
        if (extension_loaded('pcntl') && (defined('AMQP_WITHOUT_SIGNALS') ? !AMQP_WITHOUT_SIGNALS : true)) {
            if (!function_exists('pcntl_signal_dispatch')) {
                throw new BadFunctionCallException("Function 'pcntl_signal_dispatch' is referenced in the php.ini 'disable_functions' and can't be called.");
            }
            /** @noinspection PhpComposerExtensionStubsInspection */
            \pcntl_signal_dispatch();
        }
        if ($this->_forceStop || ($this->_consumed === $this->_messagesLimit && $this->_messagesLimit > 0)) {
            $this->stopConsuming();

            return true;
        }
        if ($this->isRamAlmostOverloaded()) {
            $this->stopConsuming();

            return true;
        }

        return false;
    }

    /**
     * Callback that will be fired upon receiving new message
     *
     * @param AMQPMessage $message
     * @param string      $queueName
     * @param callable    $callback
     * @return bool
     * @throws Throwable
     */
    protected function onReceive(AMQPMessage $message, string $queueName, callable $callback): bool
    {
        $timeStart = microtime(true);
        $this->component->trigger(AMQPConsumeEvent::BEFORE_CONSUME, Yii::createObject([
            'class' => AMQPConsumeEvent::class,
            'message' => $message,
            'consumer' => $this,
            'queueName' => $queueName,
            'time' => $timeStart,
        ]));

        try {
            // deserialize message back to initial data type
            if (
                $message->has('application_headers')
                && isset($message->get('application_headers')->getNativeData()[Message::HEADER_NAME_SERIALIZED])
            ) {
                $message->setBody(call_user_func($this->deserializer, $message->getBody()));
            }
            // process message and return the result code back to broker
            $processFlag = $callback($message);
            $this->sendResult($message, $processFlag);
            $this->component->trigger(AMQPConsumeEvent::AFTER_CONSUME, Yii::createObject([
                'class' => AMQPConsumeEvent::class,
                'message' => $message,
                'consumer' => $this,
                'queueName' => $queueName,
                'time' => $timeEnd = microtime(true),
                'execTime' => $timeEnd - $timeStart,
            ]));
            $this->logger->success('Message was successfully consumed.', [
                'queueName' => $queueName,
                'processFlag' => $processFlag,
                'timeStart' => $timeStart,
                'timeEnd' => $timeEnd,
                'execTime' => $timeEnd - $timeStart,
            ]);
        } catch (Throwable $e) {
            $this->logger->error($e->getMessage(), [
                'exception' => $e,
                'queueName' => $queueName,
                'message' => $message,
            ]);
            if (!$this->proceedOnException) {
                throw $e;
            }
        }
        $this->_consumed++;

        return true;
    }

    /**
     * Mark message status based on return code from callback
     *
     * @param AMQPMessage $message     Message
     * @param int|null    $processFlag Consuming flag
     */
    protected function sendResult(AMQPMessage $message, $processFlag): void
    {
        // true in testing environment
        if (!isset($message->delivery_info['channel'])) {
            return;
        }
        /** @var AMQPChannel $channel */
        $channel = $message->delivery_info['channel'];

        // respond to the broker with appropriate reply code
        if ($processFlag === CallbackInterface::MESSAGE_REQUEUE || false === $processFlag) {
            // Reject and requeue message to AMQP
            $channel->basic_reject($message->delivery_info['delivery_tag'], true);
        } elseif ($processFlag === CallbackInterface::MESSAGE_REJECT) {
            // Reject and drop
            $channel->basic_reject($message->delivery_info['delivery_tag'], false);
        } else {
            // Remove message from queue only if callback return not false
            $channel->basic_ack($message->delivery_info['delivery_tag']);
        }
    }

    /**
     * Checks if memory in use is greater or equal than memory allowed for this process
     *
     * @return boolean
     */
    protected function isRamAlmostOverloaded(): bool
    {
        if ($this->memoryLimit === 0) {
            return false;
        }

        return memory_get_usage(true) >= ($this->memoryLimit * 1024 * 1024);
    }

    /**
     * Returns consumer tag based on queueName-argument and properties (component id, id)
     *
     * @param string $queueName Queue name
     * @return string
     */
    protected function getConsumerTag(string $queueName): string
    {
        return sprintf('%s-%s-%s', $this->component->id, $queueName, $this->_id);
    }

    /**
     * Generates unique id
     *
     * @return string
     */
    protected function generateUniqueId(): string
    {
        return uniqid($this->uniqueIdPrefix, true);
    }
}
