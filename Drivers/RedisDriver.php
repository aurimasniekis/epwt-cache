<?php

namespace EPWT\Cache\Drivers;

use EPWT\Cache\Core\BaseDriver;
use EPWT\Cache\Exception\CacheException;


/**
 * Class RedisDriver
 * @package EPWT\CacheBundle\Drivers
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
class RedisDriver extends BaseDriver
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @return \Redis
     */
    public function getRedis()
    {
        return $this->redis;
    }

    /**
     * @param \Redis $redis
     *
     * @return $this
     */
    public function setRedis($redis)
    {
        $this->redis = $redis;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->getRedis()->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTime|null $ttl
     *
     * @return $this
     */
    public function set($key, $value, $ttl = null)
    {
        if ($ttl) {
            $ttl = $ttl->getTimestamp() - (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();
            $this->getRedis()->setex($key, $ttl, $value);
        } else {
            $this->getRedis()->set($key, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return (bool) $this->getRedis()->exists($key);
    }

    /**
     * @param string $key1
     * @param string $key2
     * @param string $key3
     * @param string $key4
     *
     * @return int Number of keys deleted.
     *
     */
    public function delete($key1, $key2 = null, $key3 = null, $key4 = null)
    {
        $keys = func_get_args();

        return call_user_func_array([$this->getRedis(), 'delete'], $keys);
    }

    /**
     * @param string $prefix
     *
     * @return int Number of keys deleted.
     */
    public function deletePrefix($prefix)
    {
        $script = 'return redis.call(\'del\', unpack(redis.call(\'keys\', ARGV[1])))';

        return $this->getRedis()->eval($script, [$prefix], 0);
    }

    /**
     * @param array $parts
     *
     * @return string
     */
    public function buildKey(array $parts)
    {
        return implode(':', $parts);
    }

    /**
     * @param string $key
     * @param bool $dateTimeObject
     *
     * @return \DateTime|int
     * @throws CacheException
     */
    public function getExpiration($key, $dateTimeObject = true)
    {
        $ttl = $this->getRedis()->ttl($key);

        if (-2 === $ttl) {
            throw CacheException::keyNotExist($key, 'RedisDriver::getExpiration');
        } elseif (-1 === $ttl) {
            return null;
        } else {
            if ($dateTimeObject) {
                return new \DateTime('+ ' . $ttl . ' seconds', new \DateTimeZone('UTC'));
            } else {
                return $ttl;
            }
        }
    }
}
