<?php

namespace Core\Logger\Observer;

use Core\Logger\{
    LoggerInterface
} ;

/**
 * Description of DatabaseLogger
 *
 * @author HGDDEDBN
 */
class DatabaseLogger extends BaseLogger implements LoggerInterface {

    protected $log;
}
