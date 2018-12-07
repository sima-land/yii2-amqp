# yii2-amqp
Yii2 AMQP extension

#### Installation
You need run just this command:
```shell
composer require sima-land/yii2-amqp
```

#### Configuration
Example configuration:
```php
return [
    'components' => [
        'amqp' => \simaland\amqp\Component::class,
        'connection' => [
            'dsn' => 'amqp://user:password@host:port/vHost?<param>=<value>'
        ],
        'queues' => [
            [
                'name' => 'queueName',
            ],
        ],
        'exchanges' => [
            [
                'name' => 'exchangeName',
            ],
        ],
        'routing' => [
            [
                'sourceExchange' => 'exchangeName',
                'targetQueue' => 'queueName',
            ],
        ],
        'consumer' => [
            'callbacks' => [
                'queueName' => <implement of \simaland\amqp\components\consumer\CallbackInterface::class>,
            ],
        ],
    ],
];
```

#### Testing
You must tests your changes by running this command:
```shell
composer test
```
