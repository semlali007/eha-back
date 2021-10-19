<?php

namespace Core\Logger;

/**
 * Description of LoggerInterface
 *
 * @author HGDDEDBN
 */
interface LoggerInterface {

    /**
     * 
     * @param string $type
     * @param string $message
     */
    function info(string $message);
    
     /**
     * 
     * @param string $type
     * @param string $message
     */
    function err(string $message);

     /**
     * 
     * @param string $type
     * @param string $message
     */
    function warn(string $message);
    
     /**
     * 
     * @param string $type
     * @param string $message
     */
    function fatal(string $message);
    
}
