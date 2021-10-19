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

    private $log;

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

}
