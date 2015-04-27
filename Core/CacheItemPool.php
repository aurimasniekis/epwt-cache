<?php

namespace EPWT\Cache\Core;

use EPWT\UtilsBundle\Traits\SerializerTrait;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class CacheItemPool
 * @package EPWT\Cache\Core
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
class CacheItemPool implements CacheItemPoolInterface
{
    use SerializerTrait;

    /**
     * @var BaseDriver
     */
    protected $driver;

    /**
     * @var string
     */
    protected $poolName;

    /**
     * @var int
     */
    protected $poolDefaultTTL;

    /**
     * @var array;
     */
    protected $deferredItems;

    /**
     * @return string
     */
    public function getPoolName()
    {
        return $this->poolName;
    }

    /**
     * @param string $poolName
     *
     * @return $this
     */
    public function setPoolName($poolName)
    {
        $this->poolName = $poolName;

        return $this;
    }

    /**
     * @return int
     */
    public function getPoolDefaultTTL()
    {
        return $this->poolDefaultTTL;
    }

    /**
     * @param int $poolDefaultTTL
     *
     * @return $this
     */
    public function setPoolDefaultTTL($poolDefaultTTL)
    {
        $this->poolDefaultTTL = $poolDefaultTTL;

        return $this;
    }

    /**
     * @return BaseDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @param BaseDriver $driver
     *
     * @return $this
     */
    public function setDriver($driver)
    {
        $this->driver = $driver;

        return $this;
    }

    public function __construct($name, $defaultTTL = null)
    {
        $this->poolName = $name;
        $this->poolDefaultTTL = $defaultTTL;
        $this->deferredItems = [];
    }

    /**
     * Returns a Cache Item representing the specified key.
     *
     * This method must always return a CacheItemInterface object, even in case of
     * a cache miss. It MUST NOT return null.
     *
     * @param string $key
     *   The key for which to return the corresponding Cache Item.
     *
     * @return \Psr\Cache\CacheItemInterface
     *   The corresponding Cache Item.
     * @throws \Psr\Cache\InvalidArgumentException
     *   If the $key string is not a legal value a \Psr\Cache\InvalidArgumentException
     *   MUST be thrown.
     */
    public function getItem($key)
    {
        $cacheItem = new CacheItem($key);
        $cacheItem->setCacheItemPool($this);

        return $cacheItem;
    }

    /**
     * Returns a traversable set of cache items.
     *
     * @param array $keys
     * An indexed array of keys of items to retrieve.
     *
     * @return array|\Traversable
     * A traversable collection of Cache Items keyed by the cache keys of
     * each item. A Cache item will be returned for each key, even if that
     * key is not found. However, if no keys are specified then an empty
     * traversable MUST be returned instead.
     */
    public function getItems(array $keys = [])
    {
        $cacheItems = [];

        foreach ($keys as $key) {
            $cacheItems[] = $this->getItem($key);
        }

        return $cacheItems;
    }

    /**
     * Deletes all items in the pool.
     *
     * @return boolean
     *   True if the pool was successfully cleared. False if there was an error.
     */
    public function clear()
    {
        $prefix = $this->getDriver()->buildKey([$this->getPoolName(), '*']);

        return (bool)$this->getDriver()->deletePrefix($prefix);
    }

    /**
     * Removes multiple items from the pool.
     *
     * @param array $keys
     * An array of keys that should be removed from the pool.
     *
     * @return static
     * The invoked object.
     */
    public function deleteItems(array $keys)
    {
        call_user_func_array([$this->getDriver(), 'delete'], $keys);

        return $this;
    }

    /**
     * Persists a cache item immediately.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return static
     *   The invoked object.
     */
    public function save(CacheItemInterface $item)
    {
        $key = [$this->getPoolName(), $item->getKey()];
        $key = $this->getDriver()->buildKey($key);

        $this->getDriver()->set($key, $this->phpSerialize($item->get()), $item->getExpiration());

        return $this;
    }

    /**
     * Sets a cache item to be persisted later.
     *
     * @param CacheItemInterface $item
     *   The cache item to save.
     *
     * @return static
     *   The invoked object.
     */
    public function saveDeferred(CacheItemInterface $item)
    {
        $this->deferredItems[] = $item;
    }

    /**
     * Persists any deferred cache items.
     *
     * @return bool
     *   TRUE if all not-yet-saved items were successfully saved. FALSE otherwise.
     */
    public function commit()
    {
        while($item = array_shift($this->deferredItems)) {
            $this->save($item);
        }

        return true;
    }

}
