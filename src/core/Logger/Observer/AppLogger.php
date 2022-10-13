<?php

namespace Core\Logger\Observer;

use Core\Logger\Observer\{
    LoggerInterface
} ;

/**
 * Description of AppLogger
 *
 * @author HGDDEDBN
 */
class AppLogger extends BaseLogger implements LoggerInterface {
    
    protected $log;
}
