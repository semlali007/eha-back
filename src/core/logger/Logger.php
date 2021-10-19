<?php

namespace Core\Logger;

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
     * 
     * @param \SplSubject $subject
     * @return void
     */
    public function update(\SplSubject $subject, int $level = Level::INFO): void {

        match ($level) {
            0 => $this->logger->info($subject->getMessage()), // create Info logs
            1 => $this->logger->err ($subject->getMessage()), // create error logs
            2 => $this->logger->fatal($subject->getMessage()), // create fatal logs
            3 => $this->logger->warn ($subject->getMessage()), // create warning logs
        };
    }

}
