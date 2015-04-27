<?php

namespace EPWT\Cache\Core;

use EPWT\UtilsBundle\Traits\SerializerTrait;
use Psr\Cache\CacheItemInterface;

/**
 * Class CacheItem
 * @package EPWT\Cache\Core
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
class CacheItem implements CacheItemInterface
{
    use SerializerTrait;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed;
     */
    protected $value;

    /**
     * @var bool
     */
    protected $isHit;

    /**
     * @var \DateTime;
     */
    protected $expiration;

    /**
     * @var CacheItemPool
     */
    protected $cacheItemPool;

    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @return CacheItemPool
     */
    public function getCacheItemPool()
    {
        return $this->cacheItemPool;
    }

    /**
     * @param CacheItemPool $cacheItemPool
     *
     * @return $this
     */
    public function setCacheItemPool($cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;

        return $this;
    }

    /**
     * Returns the key for the current cache item.
     *
     * The key is loaded by the Implementing Library, but should be available to
     * the higher level callers when needed.
     *
     * @return string
     *   The key string for this cache item.
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Retrieves the value of the item from the cache associated with this objects key.
     *
     * The value returned must be identical to the value original stored by set().
     *
     * if isHit() returns false, this method MUST return null. Note that null
     * is a legitimate cached value, so the isHit() method SHOULD be used to
     * differentiate between "null value was found" and "no value was found."
     *
     * @return mixed
     *   The value corresponding to this cache item's key, or null if not found.
     */
    public function get()
    {
        if (null !== $this->value) {
            return $this->value;
        }

        $key = [$this->getCacheItemPool()->getPoolName(), $this->getKey()];
        $key = $this->getCacheItemPool()->getDriver()->buildKey($key);

        $response = $this->getCacheItemPool()->getDriver()->get($key);

        if (false === $response) {
            $this->isHit = false;

            return null;
        }

        $this->value = $this->phpUnserialize($response);
        $this->isHit = true;

        return $this->value;
    }

    /**
     * Sets the value represented by this cache item.
     *
     * The $value argument may be any item that can be serialized by PHP,
     * although the method of serialization is left up to the Implementing
     * Library.
     *
     * Implementing Libraries MAY provide a default TTL if one is not specified.
     * If no TTL is specified and no default TTL has been set, the TTL MUST
     * be set to the maximum possible duration of the underlying storage
     * mechanism, or permanent if possible.
     *
     * @param mixed $value
     *   The serializable value to be stored.
     *
     * @return static
     *   The invoked object.
     */
    public function set($value)
    {
        $this->value = $value;
        $this->expiresAfter($this->getCacheItemPool()->getPoolDefaultTTL());

        return $this;
    }

    /**
     * Confirms if the cache item lookup resulted in a cache hit.
     *
     * Note: This method MUST NOT have a race condition between calling isHit()
     * and calling get().
     *
     * @return boolean
     *   True if the request resulted in a cache hit.  False otherwise.
     */
    public function isHit()
    {
        return $this->isHit;
    }

    /**
     * Confirms if the cache item exists in the cache.
     *
     * Note: This method MAY avoid retrieving the cached value for performance
     * reasons, which could result in a race condition between exists() and get().
     * To avoid that potential race condition use isHit() instead.
     *
     * @return boolean
     *  True if item exists in the cache, false otherwise.
     */
    public function exists()
    {
        $key = [$this->getCacheItemPool()->getPoolName(), $this->getKey()];
        $key = $this->getCacheItemPool()->getDriver()->buildKey($key);

        return $this->getCacheItemPool()->getDriver()->exists($key);
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param \DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return static
     *   The called object.
     */
    public function expiresAt($expiration = null)
    {
        $this->expiration = $expiration;
    }

    /**
     * Sets the expiration time for this cache item.
     *
     * @param int|\DateInterval $time
     *   The period of time from the present after which the item MUST be considered
     *   expired. An integer parameter is understood to be the time in seconds until
     *   expiration.
     *
     * @return static
     *   The called object.
     */
    public function expiresAfter($time)
    {
        if (is_int($time)) {
            $this->expiration = new \DateTime('+ ' . $time . ' seconds', new \DateTimeZone('UTC'));
        } else if (is_a($time, '\DateInterval')) {
            $this->expiration = new \DateTime('now', new \DateTimeZone('UTC'));
            $this->expiration->add($time);
        }

        return $this;
    }

    /**
     * Returns the expiration time of a not-yet-expired cache item.
     *
     * If this cache item is a Cache Miss, this method MAY return the time at
     * which the item expired or the current time if that is not available.
     *
     * @return \DateTime
     *   The timestamp at which this cache item will expire.
     */
    public function getExpiration()
    {
        if ($this->isHit() && null === $this->expiration) {
            $key = [$this->getCacheItemPool()->getPoolName(), $this->getKey()];
            $key = $this->getCacheItemPool()->getDriver()->buildKey($key);

            $this->expiration = $this->getCacheItemPool()->getDriver()->getExpiration($key);
        }

        return $this->expiration;
    }

}
