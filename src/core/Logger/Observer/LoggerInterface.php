<?php

namespace Core\Logger\Observer;

/**
 * Description of LoggerInterface
 *
 * @author HGDDEDBN
 */
interface LoggerInterface {

    /**
     * 
     * @param string $message
     */
    function info(string $message);

    /**
     * 
     * @param string $message
     */
    function err(string $message);

    /**
     * 
     * @param string $message
     */
    function warn(string $message);

    /**
     * 
     * @param string $message
     */
    function fatal(string $message);

    /**
     * 
     * @param string $message
     */
    function notice(string $message);
}
