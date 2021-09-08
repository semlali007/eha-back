<?php

namespace Core\Cache;

use Core\Cache\Common\PhpCacheItem ;

/**
 * Description of Cache
 *
 * @author HGDDEDBN
 */
class ApcuCachePool {

    /**
     * @type bool
     */
    public const skipOnCli = false;

    /**
     * 
     * @param type $key
     * @return type
     */
    public static function fetchObjectFromCache(string $key) {
        if (self::skipIfCli()) {
            return [false, null, [], null];
        }
        
        return apcu_fetch($key);
    }

    /**
     * 
     * @return mixed
     */
    public static function clearAllObjectsFromCache() {
        return apcu_clear_cache();
    }

    /**
     * 
     * @param type $key
     * @return boolean
     */
    public function clearOneObjectFromCache($key) {
        apcu_delete($key);

        return true;
    }

    /**
     * 
     * @param PhpCacheItem $item
     * @param int $ttl
     * @return boolean
     */
    public static function storeItemInCache(PhpCacheItem $item, int $ttl = null) {

        if ($ttl < 0) {
            return false;
        }
       
        return apcu_store($item->getKey(), $item->getValue(), (is_null($ttl) ? $item->getExpirationTimestamp() : $ttl));
    }

    /**
     * Returns true if CLI and if it should skip on cli.
     *
     * @return bool
     */
    private static function skipIfCli() {
        return self::skipOnCli && php_sapi_name() === 'cli';
    }

    public function fetchValue(string $name) {
        return apcu_fetch($name);
    }

    /**
     * 
     * @param string $name
     * @param string $value
     */
    public function setDirectValue(string $name, string $value) {
        apcu_store($name, $value);
    }

    /**
     * 
     * @param string $key
     * @return boolean
     */
    public static function isExpired(string $key): bool {
        return false === apcu_fetch($key);
    }

}
