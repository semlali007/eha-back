<?php

namespace App\Command;

use Symfony\Component\Console\Application;
use App\Command\Command\{ArchivageFicheMarche,AutoNotifArchivageFiche};



/**
 * @author HGDDEDBN
 */
class Bootstrap {

    /**
     * 
     * @var Application
     */
    private Application $application;

    /**
     * @var Bootstrap
     * @access private
     * @static
     */
    private static $_instance = null;
    
    /**
     * 
     * @var array
     */
    private array $commands =[] ;

    /**
     * Constructeur
     */
    public function __construct() {
        $this->application = new Application();
        $this->commands = [
            new ArchivageFicheMarche(),
            new AutoNotifArchivageFiche()
        ] ;
    }

    /**
     * charger l'instance de command
     * @return self|null
     */
    public static function getInstance(): ?self {

        if (is_null(self::$_instance)) {
            self::$_instance = new Bootstrap();
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
