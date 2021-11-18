<?php

namespace Core\Logger;

/**
 * Description of ErrorNotifier
 *
 * @author HGDDEDBN
 */
class LoggerNotifier implements \SplSubject {

    private $observers = [];
    private $message;

    /**
     * 
     * @param \SplObserver $observer
     * @return void
     */
    public function attach(\SplObserver $observer): self {

        if (!in_array($observer, $this->observers)):
            $this->observers[] = $observer;
        endif;
        
        return $this;
    }

    /**
     * 
     * @param \SplObserver $observer
     * @return void
     */
    public function detach(\SplObserver $observer): void {

        $key = array_search($observer, $this->observers, true);
        if ($key) {
            unset($this->observers[$key]);
        }
    }

    /**
     * 
     * @param string $message
     * @return void
     */
    public function send(string $message, int $level = Level::INFO): self {

        $this->message = $message;
        $this->notify($level);
        
        return $this ;
    }

    /**
     * 
     * @return string
     */
    public function getMessage(): string {

        return $this->message;
    }

    /**
     * 
     * @return void
     */
    public function notify(int $level = Level::INFO): void {

        foreach ($this->observers as $value) {
            $value->update($this, $level);
        }
    }

    /**
     * 
     * @param \SplObserver $observer
     * @return bool
     */
    public function isAttached(\SplObserver $observer): bool {

        return (in_array($observer, $this->observers));
    }

}
