<?php

namespace EPWT\Cache\Core;

/**
 * Interface DriverInterface
 * @package EPWT\Cache\Drivers
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
interface DriverInterface
{
    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key);

    /**
     * @param string $key
     * @param mixed $value
     * @param \DateTime|null $ttl
     *
     * @return mixed
     */
    public function set($key, $value, $ttl = null);

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key);

    /**
     * @param string $key1
     * @param string $key2
     * @param string $key3
     * @param string $key4
     *
     * @return int Number of keys deleted.
     *
     */
    public function delete($key1, $key2 = null, $key3 = null, $key4 = null);

    /**
     * @param string $prefix
     *
     * @return int Number of keys deleted.
     */
    public function deletePrefix($prefix);

    /**
     * @param array $parts
     *
     * @return string
     */
    public function buildKey(array $parts);

    /**
     * @param string $key
     * @param bool $dateTimeObject
     *
     * @return \DateTime|int
     */
    public function getExpiration($key, $dateTimeObject = true);
}
