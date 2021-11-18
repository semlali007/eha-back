<?php

namespace Core\Logger\Observer;

use Core\Logger\{
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
