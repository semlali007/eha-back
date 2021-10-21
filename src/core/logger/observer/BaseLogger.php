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
    
        /**
     * 
     * @param string $message
     * @return void
     */
    public function err(string $message): void {
        $this->log->error($message);
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function fatal(string $message): void {
        $this->log->fatal($message);
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function info(string $message): void {
        $this->log->info($message);
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function warn(string $message): void {
        $this->log->warn($message);
    }
    
     /**
     * 
     * @param string $message
     * @return void
     */
    public function notice(string $message): void {
        $this->log->trace($message);
    }

}
