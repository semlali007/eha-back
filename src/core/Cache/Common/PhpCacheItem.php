<?php

namespace Core\Cache\Common;

/**
 * Description of PhpCacheItem
 *
 * @author HGDDEDBN
 */
class PhpCacheItem {

    public $key;
    public $value;
    public $expirationTimestamp;

    /**
     * 
     * @param type $key
     * @param type $value
     * @param type $expirationTimestamp
     */
    function __construct(string $key, $value, int $expirationTimestamp = 0) {
        $this->key = $key;
        $this->value = $value;
        $this->expirationTimestamp = (0 === $expirationTimestamp ? getenv('CACHE_TTL') : $expirationTimestamp);
    }

    /**
     * 
     * @return type
     */
    function getKey() {
        return $this->key;
    }

    /**
     * 
     * @return type
     */
    function getValue() {
        return $this->value;
    }

    /**
     * 
     * @return type
     */
    function getExpirationTimestamp() {
        return $this->expirationTimestamp;
    }

    /**
     * 
     * @param type $key
     * @return void
     */
    function setKey($key): void {
        $this->key = $key;
    }

    /**
     * 
     * @param type $value
     * @return void
     */
    function setValue($value) {
        $this->value = $value;
        return $this;
    }


    /**
     * 
     * @param type $expirationTimestamp
     * @return void
     */
    function setExpirationTimestamp($expirationTimestamp): void {
        $this->expirationTimestamp = $expirationTimestamp;
    }

}
