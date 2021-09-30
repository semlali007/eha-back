<?php

namespace Core\Router;

use Database\DBConnection;

class Route
{
    public $path;
    public $action;
    public $matches;
    public $roles;

    public function __construct($path, $action, $roles = [])
    {
        $this->path = trim($path, '/');
        $this->action = $action;
        $this->roles = $roles;
    }

    public function matches(string $url)
    {
        $path = preg_replace('#:([\w]+)#', '([^/]+)', $this->path);
        $pathToMatch = "#^$path$#";

        if (preg_match($pathToMatch, $url, $matches)) {
            $this->matches = $matches;
            return true;
        } else {
            return false;
        }
    }

    public function execute()
    {

        $params = explode('@', $this->action);
        $controller = new $params[0](new DBConnection(),$this->roles);
        $method = $params[1];
        switch (count($this->matches)) {
            case 1:
                return $controller->$method();
                break;
            case 2:
                return $controller->$method($this->matches[1]);
                break;
            case 3:
                return $controller->$method($this->matches[1], $this->matches[2]);
                break;
            case 4:
                return $controller->$method($this->matches[1], $this->matches[2], $this->matches[3]);
                break;
            default:
                return $controller->$method();
                break;
        }
    }
}
