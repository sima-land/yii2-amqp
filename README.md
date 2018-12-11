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
        'amqp' => [
            'class' => \simaland\amqp\Component::class,
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
    ],
];
```

#### Testing
You must tests your changes by running this command:
```shell
composer test
```


#### Example
```php
class AmpqController {

    public function actionSend()
    {
        $msg = \Yii::$app->amqp->createMessage('Test');
        $exchange = \Yii::$app->amqp->exchanges->current();
        $exchange->declare();
        \Yii::$app->amqp->producer->publish($msg, $exchange);
    }

    public function actionListen()
    {
        \Yii::$app->amqp->consumer->declare();
        \Yii::$app->amqp->consumer->consume();
    }
```
