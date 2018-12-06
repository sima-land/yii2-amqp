<?php

namespace simaland\amqp\components;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use simaland\amqp\Component;
use simaland\amqp\exceptions\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Message object
 *
 * @property AMQPMessage $amqpMessage
 */
class Message extends ConfigurationObject
{
    /**
     * "Serialized" header name
     */
    public const HEADER_NAME_SERIALIZED = 'amqp.serialized';

    /**
     * @var string Content type
     */
    public $contentType = 'text/plain';

    /**
     * @var int Delivery mode
     */
    public $deliveryMode = AMQPMessage::DELIVERY_MODE_PERSISTENT;

    /**
     * @var callable Serializer
     */
    public $serializer = 'serialize';

    /**
     * @var array AMQP message properties
     * @see AMQPMessage::$propertyDefinitions
     */
    public $properties = [];

    /**
     * @var array AMQP message headers
     */
    public $headers = [];

    /**
     * @var mixed Message body
     */
    private $_body;

    /**
     * @var AMQPMessage
     */
    private $_amqpMessage;

    /**
     * @inheritdoc
     * @param mixed $body
     */
    public function __construct($body, Component $component, array $config = [])
    {
        $this->_body = $body;
        parent::__construct($component, $config);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        if (!\is_callable($this->serializer)) {
            throw new InvalidConfigException('Producer `serializer` option should be a callable.');
        }
    }

    /**
     * AMQP message for sending
     *
     * @param bool $renew Renew flag
     * @return AMQPMessage
     */
    public function getAmqpMessage($renew = false): AMQPMessage
    {
        if (!$this->_amqpMessage || $renew) {
            $messageBody = $this->_body;
            $headers = $this->headers;
            if (!\is_string($messageBody)) {
                $messageBody = \call_user_func($this->serializer, $messageBody);
                $headers[static::HEADER_NAME_SERIALIZED] = true;
            }
            $this->_amqpMessage = new AMQPMessage($messageBody, ArrayHelper::merge(
                [
                    'content_type' => $this->contentType,
                    'delivery_mode' => $this->deliveryMode,
                ],
                $this->properties
            ));
            if (!empty($headers)) {
                $messageHeaders = new AMQPTable($headers);
                $this->_amqpMessage->set('application_headers', $messageHeaders);
            }
        }

        return $this->_amqpMessage;
    }
}
