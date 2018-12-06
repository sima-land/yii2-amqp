<?php

namespace simaland\amqp;

use Iterator;
use yii\base\BaseObject;
use function array_key_exists;
use function count;
use function array_walk;
use function array_values;
use function array_filter;

/**
 * Base component collection
 */
abstract class Collection extends BaseObject implements Iterator
{
    /**
     * @var components\ConfigurationObject[] Collection items
     */
    protected $items;

    /**
     * @var int Iterator key
     */
    private $_iteratorKey = 0;

    /**
     * @inheritdoc
     * @param components\ConfigurationObject[] $items Initial collection items
     */
    public function __construct(array $items = [], array $config = [])
    {
        foreach ($items as $item) {
            $this->append($item);
        }
        parent::__construct($config);
    }

    /**
     * Append item to collection
     *
     * @param components\ConfigurationObject $item Collection item
     */
    public function append(components\ConfigurationObject $item): void
    {
        $this->items[] = $item;
    }

    /**
     * Filter collection
     *
     * @param callable $callback Filter callable
     * @return $this
     */
    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Filter collection by name property in configuration object
     *
     * @param string $name Configuration object name for filtering
     * @return $this
     */
    public function filterByName(string $name): self
    {
        return $this->filter(function (components\ConfigurationObject $item) use ($name) {
            return $item->name === $name;
        });
    }

    /**
     * Returns collection length (items count)
     *
     * @return int
     */
    public function length(): int
    {
        return count($this->items);
    }

    /**
     * Apply callback to each item in collection
     *
     * @param callable $callback Iterator callable
     * @return $this
     */
    public function each(callable $callback): self
    {
        array_walk($this->items, $callback);

        return $this;
    }

    /**
     * @inheritdoc
     * @return components\ConfigurationObject
     */
    public function current(): components\ConfigurationObject
    {
        return $this->items[$this->_iteratorKey];
    }

    /**
     * @inheritdoc
     */
    public function next(): void
    {
        $this->_iteratorKey++;
    }

    /**
     * @inheritdoc
     */
    public function key(): int
    {
        return $this->_iteratorKey;
    }

    /**
     * @inheritdoc
     */
    public function valid(): bool
    {
        return array_key_exists($this->_iteratorKey, $this->items);
    }

    /**
     * @inheritdoc
     */
    public function rewind(): void
    {
        $this->_iteratorKey = 0;
    }
}
