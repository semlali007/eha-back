<?php

namespace Core\Logger\Observer ;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
 

/**
 * Description of BaseLogger
 *
 * @author HGDDEDBN
 */
class BaseLogger {

    protected $log;
    protected $fullpath ; 
    private const DEFAULT_EXTENSTION = ".log";
    /**
     * Constructeur Logger
     */
    function __construct(string $channel = APP_CHANNEL) {
        
        $this->log = new Logger($channel);
        $this->fullpath = getenv("DEFAULT_LOGS_PATH") . 
                DIRECTORY_SEPARATOR . $channel . self::DEFAULT_EXTENSTION;
    }
    
    /**
     * 
     * @param string $message
     * @return void
     */
    public function err(string $message): void {
        $this->log->pushHandler(new RotatingFileHandler($this->fullpath,getenv("MAX_FILE"), Logger::ERROR));
        $this->log->error($message);
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function fatal(string $message): void {
        $this->log->pushHandler(new RotatingFileHandler($this->fullpath,getenv("MAX_FILE"), Logger::CRITICAL));
        $this->log->critical($message);
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function info(string $message): void {
        $this->log->pushHandler(new RotatingFileHandler($this->fullpath,getenv("MAX_FILE"), Logger::INFO));
        $this->log->info($message);
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function warn(string $message): void {
        $this->log->pushHandler(new RotatingFileHandler($this->fullpath,getenv("MAX_FILE"), Logger::WARNING));
        $this->log->warning($message);
    }
    
     /**
     * 
     * @param string $message
     * @return void
     */
    public function notice(string $message): void {
        $this->log->pushHandler(new RotatingFileHandler($this->fullpath,getenv("MAX_FILE"), Logger::NOTICE));
        $this->log->notice($message);
    }

}
