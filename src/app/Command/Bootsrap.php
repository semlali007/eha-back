<?php

namespace App\Command;

use Symfony\Component\Console\Application;
use App\Command\Command\CreateUserCommand;

/**
 * @author HGDDEDBN
 */
class Bootsrap {

    /**
     * 
     * @var Application
     */
    private Application $application;

    /**
     * @var LoggerHandler
     * @access private
     * @static
     */
    private static $_instance = null;
    
    private array $commands = [
        CreateUserCommand
    ];

    /**
     * Constructeur
     */
    public function __construct() {
        $this->application = new Application();
    }

    /**
     * charger l'instance de command
     * @return self|null
     */
    public static function getInstance(): ?self {

        if (is_null(self::$_instance)) {
            self::$_instance = new Bootsrap();
        }

        return self::$_instance;
    }

    /**
     * 
     * @param array $commands
     * @return void
     */
    public function register(array $commands): self {
        foreach ($commands as $command) {
            $this->application->add($command);
        }
        return $this;
    }

    /**
     * 
     * @return void
     */
    public function run(): self {
        $this->application->run();
        return $this;
    }

    /**
     * Load Commands
     */
    public function init() {
        self::getInstance();
        $this->register($this->commands)
                ->run();
    }

}
