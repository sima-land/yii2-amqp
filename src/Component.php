<?php

namespace Simaland\Amqp;

use yii\base\Component as BaseComponent;
use yii\base\InvalidConfigException;
use yii\di\Instance;
use Yii;
use function sprintf;
use function is_array;
use function array_merge;
use function array_merge_recursive;
use function uniqid;
use function is_int;
use function array_walk;
use function md5;

/**
 * Yii2 AMQP component
 *
 * @property-read Components\Producer                        $producer   Producer component
 * @property-read Components\Connection                      $connection Connection component
 * @property-read Components\Consumer                        $consumer   Consumer component
 * @property-read Collections\Queue|Components\Queue[]       $queues     Queues collection
 * @property-read Collections\Exchange|Components\Exchange[] $exchanges  Exchanges collection
 * @property-read Collections\Routing|Components\Routing[]   $routing    Routing collection
 */
class Component extends BaseComponent
{
    /**
     * Singleton alias name template
     */
    public const SINGLETON_ALIAS_NAME_TEMPLATE = 'ext.amqp.%s.%s';

    /**
     * Extension configuration default values
     *
     * @var array
     */
    protected const DEFAULTS = [
        'exchanges' => [
            'class' => Components\Exchange::class,
        ],
        'queues' => [
            'class' => Components\Queue::class,
        ],
        'routing' => [
            'class' => Components\Routing::class,
        ],
    ];

    /**
     * @var string AMQP configuration id, autogenerated if null
     */
    public $id;

    /**
     * @var int Sub-components declaration mode
     */
    public $declaration = Components\AMQPObject::DECLARATION_ENABLE;

    /**
     * @var Components\Message|array Message template
     */
    public $messageDefinition = [
        'class' => Components\Message::class,
    ];

    /**
     * @var Components\Connection|array
     */
    protected $_connection = [
        'class' => Components\Connection::class,
    ];

    /**
     * @var Components\Producer|array
     */
    protected $_producer = [
        'class' => Components\Producer::class,
    ];

    /**
     * @var Components\Consumer|array
     */
    protected $_consumer = [
        'class' => Components\Consumer::class,
    ];

    /**
     * @var Collections\Queue|Components\Queue[]
     */
    protected $_queues = [
        'class' => Collections\Queue::class,
    ];

    /**
     * @var Collections\Exchange|Components\Exchange[]
     */
    protected $_exchanges = [
        'class' => Collections\Exchange::class,
    ];

    /**
     * @var Collections\Routing|Components\Routing[]
     */
    protected $_routing = [
        'class' => Collections\Routing::class,
    ];

    /**
     * @inheritdoc
     */
    public function __construct(array $config = [])
    {
        $config = $this->configureComponents($config);
        $this->configureCollections();
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     * @throws exceptions\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        if ($this->id === null) {
            $this->id = $this->generateId();
        }
        $this->registerConnection();
        $this->registerProducer();
        $this->registerConsumer();
        $this->registerMessageDefinition();
        $this->registerCollections();
    }

    /**
     * Returns service name specified on component
     *
     * @param string $component Component name
     * @return string
     */
    public function getServiceName(string $component): string
    {
        return sprintf(static::SINGLETON_ALIAS_NAME_TEMPLATE, $this->id, $component);
    }

    /**
     * Get connection component
     *
     * @return Components\Connection
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function getConnection(): Components\Connection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::$container->get($this->getServiceName('connection'));
    }

    /**
     * @throws Exceptions\InvalidConfigException
     */
    public function setConnection(): void
    {
        throw new Exceptions\InvalidConfigException('Setter not allowed for this property.');
    }

    /**
     * Get producer component
     *
     * @return Components\Producer
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function getProducer(): Components\Producer
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::$container->get($this->getServiceName('producer'));
    }

    /**
     * @throws Exceptions\InvalidConfigException
     */
    public function setProducer(): void
    {
        throw new Exceptions\InvalidConfigException('Setter not allowed for this property.');
    }

    /**
     * Get consumer component
     *
     * @return Components\Consumer
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function getConsumer(): Components\Consumer
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::$container->get($this->getServiceName('consumer'));
    }

    /**
     * @throws Exceptions\InvalidConfigException
     */
    public function setConsumer(): void
    {
        throw new Exceptions\InvalidConfigException('Setter not allowed for this property.');
    }

    /**
     * Returns queue collection
     *
     * @return Collections\Queue|Components\Queue[]
     */
    public function getQueues(): Collections\Queue
    {
        return $this->_queues;
    }

    /**
     * @throws Exceptions\InvalidConfigException
     */
    public function setQueues(): void
    {
        throw new Exceptions\InvalidConfigException('Setter not allowed for this property.');
    }

    /**
     * Returns exchange collection
     *
     * @return Collections\Exchange|Components\Exchange[]
     */
    public function getExchanges(): Collections\Exchange
    {
        return $this->_exchanges;
    }

    /**
     * @throws Exceptions\InvalidConfigException
     */
    public function setExchanges(): void
    {
        throw new Exceptions\InvalidConfigException('Setter not allowed for this property.');
    }

    /**
     * Returns routing collection
     *
     * @return Collections\Routing|Components\Routing[]
     */
    public function getRouting(): Collections\Routing
    {
        return $this->_routing;
    }

    /**
     * @throws Exceptions\InvalidConfigException
     */
    public function setRouting(): void
    {
        throw new Exceptions\InvalidConfigException('Setter not allowed for this property.');
    }

    /**
     * Create new message
     *
     * @param mixed $body Message body
     * @return Components\Message
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\di\NotInstantiableException
     */
    public function createMessage($body): Components\Message
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Yii::$container->get($this->getServiceName('message'), [
            $body,
            $this,
        ]);
    }

    /**
     * Register AMQP connection
     */
    protected function registerConnection(): void
    {
        Yii::$container->setSingleton(
            $this->getServiceName('connection'),
            $this->_connection,
            [$this]
        );
    }

    /**
     * Register producer
     */
    protected function registerProducer(): void
    {
        Yii::$container->setSingleton(
            $this->getServiceName('producer'),
            array_merge([
                'declaration' => $this->declaration,
            ], $this->_producer),
            [Instance::of($this->getServiceName('connection')), $this]
        );
    }

    /**
     * Register consumer
     */
    protected function registerConsumer(): void
    {
        Yii::$container->setSingleton(
            $this->getServiceName('consumer'),
            array_merge([
                'declaration' => $this->declaration,
            ], $this->_consumer),
            [Instance::of($this->getServiceName('connection')), $this]
        );
    }

    /**
     * Register message definition
     */
    protected function registerMessageDefinition(): void
    {
        Yii::$container->set(
            $this->getServiceName('message'),
            $this->messageDefinition
        );
    }

    /**
     * Configure components (producer, consumer, connection)
     *
     * @param array $config Configuration array
     * @return array
     */
    protected function configureComponents(array $config = []): array
    {
        $defaultConfig = [];
        $configProperties = array_merge(
            $this->listConfigurationComponents(),
            $this->listConfigurationCollections()
        );
        foreach ($configProperties as $property) {
            $defaultConfig[$property] = $this->{'_' . $property};
        }
        $config = array_merge_recursive($defaultConfig, $config);
        foreach ($configProperties as $property) {
            $this->{'_' . $property} = $config[$property];
            unset($config[$property]);
        }

        return $config;
    }

    /**
     * List components allowed for configure
     *
     * @return array
     */
    protected function listConfigurationComponents(): array
    {
        return [
            'connection',
            'producer',
            'consumer',
        ];
    }

    /**
     * Configure collections (queues, exchanges, routing)
     */
    protected function configureCollections(): void
    {
        $collections = $this->listConfigurationCollections();
        foreach ($collections as $property) {
            if (is_array($propertyConfiguration = $this->{'_' . $property})) {
                $collectionItems = [];
                foreach ($propertyConfiguration as $propertyKey => $propertyValue) {
                    if (is_int($propertyKey)) {
                        $collectionItems[] = $propertyValue;
                        unset($propertyConfiguration[$propertyKey]);
                    }
                }
                $propertyConfiguration['_items'] = $collectionItems;
                $this->{'_' . $property} = $propertyConfiguration;
            }
        }
    }

    /**
     * List collections allowed for configure
     *
     * @return array
     */
    protected function listConfigurationCollections(): array
    {
        return [
            'queues',
            'exchanges',
            'routing',
        ];
    }

    /**
     * Register collections
     *
     * @throws Exceptions\InvalidConfigException
     */
    protected function registerCollections(): void
    {
        $collections = $this->listConfigurationCollections();
        try {
            foreach ($collections as $property) {
                if (is_array($propertyConfiguration = $this->{'_' . $property})) {
                    $collectionItems = $propertyConfiguration['_items'] ?? [];
                    unset($propertyConfiguration['_items']);
                    array_walk($collectionItems, function (&$item) use ($property) {
                        if (is_array($item)) {
                            $item = Yii::createObject(array_merge(
                                static::DEFAULTS[$property],
                                [
                                    'declaration' => $this->declaration,
                                ],
                                $item
                            ), [
                                Instance::of($this->getServiceName('connection')),
                                $this,
                            ]);
                        }
                    });
                    $this->{'_' . $property} = Yii::createObject($propertyConfiguration, [
                        $collectionItems,
                    ]);
                }
            }
        } catch (InvalidConfigException $e) {
            throw new Exceptions\InvalidConfigException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Generates unique id for component
     *
     * @return string
     */
    private function generateId(): string
    {
        return md5(uniqid(__CLASS__, true));
    }
}
