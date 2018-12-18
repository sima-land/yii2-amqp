<?php

namespace simaland\amqp\components;

use Exception;
use InvalidArgumentException;
use ReflectionException;
use ReflectionClass;
use yii\helpers\Inflector;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use simaland\amqp\exceptions\InvalidConfigException;
use function stream_context_create;
use function is_subclass_of;
use function parse_url;
use function urldecode;
use function ltrim;
use function parse_str;
use function in_array;
use function strpos;
use function is_array;

/**
 * Connection configuration
 *
 * @property-read AMQPChannel          $channel        AMQP channel
 * @property-read AMQPStreamConnection $amqpConnection AMQP connection
 */
class Connection extends ConfigurationObject
{
    /**
     * DSN scheme for AMQP protocol
     */
    public const DSN_SCHEME_AMQP = 'amqp';

    /**
     * @query-assign-protect
     * @var string AMQP connection type class
     */
    public $type = AMQPLazyConnection::class;

    /**
     * @query-assign-protect
     * @var string DSN (ex. amqp://user:password@host:port/vHost?param1=value1)
     */
    public $dsn;

    /**
     * @query-assign-protect
     * @var string AMQP server host
     */
    public $host;

    /**
     * @query-assign-protect
     * @var int AMQP server port
     */
    public $port = 5672;

    /**
     * @query-assign-protect
     * @var string AMQP access user
     */
    public $user = 'guest';

    /**
     * @query-assign-protect
     * @var string AMQP access password
     */
    public $password = 'guest';

    /**
     * @query-assign-protect
     * @var string AMQP virtual host
     */
    public $vHost = '/';

    /**
     * @var bool
     */
    public $insist = false;

    /**
     * @var string AMQP login method
     */
    public $loginMethod = 'AMQPLAIN';

    /**
     * @var string Connection locale
     */
    public $locale = 'en_US';

    /**
     * @var float AMQP server connection timeout
     */
    public $connectionTimeout = 3.0;

    /**
     * @var float AMQP server read-write timeout
     */
    public $readWriteTimeout = 3.0;

    /**
     * @query-assign-protect
     * @var resource|array Stream context
     * @see stream_context_create
     */
    public $streamContext;

    /**
     * @var bool Connection keep-alive
     */
    public $keepAlive = false;

    /**
     * @var int Heartbeat timeout
     */
    public $heartBeat = 0;

    /**
     * @var AMQPStreamConnection
     */
    private $_amqpConnection;

    /**
     * @var AMQPChannel
     */
    private $_channel;

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        $this->parseDsn();
        if (is_array($this->streamContext)) {
            $this->streamContext = stream_context_create($this->streamContext);
        }
        if (empty($this->dsn) && empty($this->host)) {
            throw new InvalidConfigException('Either `dsn` or `host` options required for configuring connection.');
        }
        if (empty($this->type) || !is_subclass_of($this->type, AbstractConnection::class)) {
            throw new InvalidConfigException('Connection type should be a subclass of ' . AbstractConnection::class . '.');
        }
        register_shutdown_function(function () {
            $this->close();
        });
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Returns active AMQP connection
     *
     * @return AMQPStreamConnection
     */
    public function getAmqpConnection(): AMQPStreamConnection
    {
        if (!$this->_amqpConnection) {
            $this->_amqpConnection = new $this->type(
                $this->host,
                $this->port,
                $this->user,
                $this->password,
                $this->vHost,
                (bool)$this->insist,
                $this->loginMethod,
                null,
                $this->locale,
                $this->connectionTimeout,
                $this->readWriteTimeout,
                $this->streamContext,
                $this->keepAlive,
                $this->heartBeat
            );
        }

        return $this->_amqpConnection;
    }

    /**
     * Returns AMQP connection channel
     *
     * @return AMQPChannel
     */
    public function getChannel(): AMQPChannel
    {
        if (
            !$this->_channel instanceof AMQPChannel
            || null === $this->_channel->getChannelId()
        ) {
            $this->_channel = $this->amqpConnection->channel();
        }

        return $this->_channel;
    }

    /**
     * Renew AMQP connection and returns connection state
     *
     * @return bool
     */
    public function reconnect(): bool
    {
        if (!$this->amqpConnection->isConnected()) {
            return false;
        }
        $this->amqpConnection->reconnect();

        return $this->amqpConnection->isConnected();
    }

    /**
     * Close connection and returns connection state
     *
     * @return bool
     */
    public function close(): bool
    {
        if ($this->_channel instanceof AMQPChannel) {
            try {
                $this->_channel->close();
            } catch (Exception $e) {
                \Yii::error('Exception was thrown during AMQP channel closing: ' . $e->getMessage());
            }
        }
        if ($this->amqpConnection->isConnected()) {
            try {
                $this->amqpConnection->close();
            } catch (Exception $e) {
                \Yii::error('Exception was thrown during AMQP connection closing: ' . $e->getMessage());
            }
        }

        return $this->amqpConnection->isConnected();
    }

    /**
     * Parse connection DSN
     */
    protected function parseDsn(): void
    {
        if ($this->dsn) {
            $dsn = parse_url($this->dsn);
            if ($dsn === false || !isset($dsn['scheme']) || $dsn['scheme'] !== static::DSN_SCHEME_AMQP) {
                throw new InvalidArgumentException('Malformed parameter "dsn".');
            }
            if (isset($dsn['host'])) {
                $this->host = urldecode($dsn['host']);
            }
            if (isset($dsn['port'])) {
                $this->port = (int)$dsn['port'];
            }
            if (isset($dsn['user'])) {
                $this->user = urldecode($dsn['user']);
            }
            if (isset($dsn['pass'])) {
                $this->password = urldecode($dsn['pass']);
            }
            if (isset($dsn['path'])) {
                $this->vHost = urldecode($dsn['path']);
                if ($this->vHost !== '/') {
                    $this->vHost = ltrim($this->vHost, '/');
                }
            }
            if (isset($dsn['query'])) {
                $safeAssignedProperties = $this->dsnQuerySafeAssignedProperties();
                if (!empty($safeAssignedProperties)) {
                    $query = [];
                    parse_str($dsn['query'], $query);
                    foreach ($query as $property => $value) {
                        $propertyName = Inflector::variablize($property);
                        if (in_array($propertyName, $safeAssignedProperties, true)) {
                            $this->{$propertyName} = $value;
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns query's safe-assigned class properties
     *
     * @return array
     */
    protected function dsnQuerySafeAssignedProperties(): array
    {
        $properties = [];
        try {
            $reflection = new ReflectionClass($this);
            $reflectionProperties = $reflection->getProperties();
            foreach ($reflectionProperties as $reflectionProperty) {
                if (strpos($reflectionProperty->getDocComment(), '@query-assign-protect') === false) {
                    $properties[] = $reflectionProperty->getName();
                }
            }
        } catch (ReflectionException $e) {
        }

        return $properties;
    }
}
