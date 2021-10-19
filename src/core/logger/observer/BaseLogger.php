<?php

namespace Core\Logger\Observer ;

use Logger;

/**
 * Description of BaseLogger
 *
 * @author HGDDEDBN
 */
class BaseLogger {

    protected $log;

    /**
     * Constructeur Logger
     */
    function __construct(string $chanel = "DEFAULT") {

        $this->log = Logger::getLogger($chanel);
    }

}
