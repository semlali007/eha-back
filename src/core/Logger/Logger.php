<?php

namespace Core\Logger;
use Core\Logger\Observer\LoggerInterface ;
/**
 * Description of Logger
 *
 * @author HGDDEDBN
 */
class Logger implements \SplObserver {

    private $logger;

    /*
     * 
     */

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * 0=> info
     * 1=> err
     * 2=> fatal
     * 3=> warning
     * 4=> notice
     * 
     * @param \SplSubject $subject
     * @return void
     */
    public function update(\SplSubject $subject, int $level = Level::INFO): void {
        match ($level) {
            Level::INFO => $this->logger->info($subject->getMessage()), // create Info logs
            Level::ERROR => $this->logger->err($subject->getMessage()), // create error logs
            Level::FATAL => $this->logger->fatal($subject->getMessage()), // create fatal logs
            Level::WARNING => $this->logger->warn($subject->getMessage()), // create warning logs
            Level::NOTICE => $this->logger->notice($subject->getMessage()), // create warning logs
        };
    }

}
