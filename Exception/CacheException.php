<?php

namespace EPWT\Cache\Exception;

use Psr\Cache\CacheException as CacheExceptionInterface;

/**
 * Class CacheException
 * @package EPWT\Cache\Exception
 * @author Aurimas Niekis <aurimas.niekis@gmail.com>
 */
class CacheException extends \Exception implements CacheExceptionInterface
{
    public static function keyNotExist($key, $method = '')
    {
        if ($method) {
            $method = ' for method "' . $method .'"';
        }

        return new static(sprintf(
            'Key "%s" does not exists%s',
            $key,
            $method
        ));
    }
}
