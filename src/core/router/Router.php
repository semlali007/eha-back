<?php

namespace Core\Router;

use App\Exceptions\NotFoundException;
class Router {

    public $url;
    public $routes = [];

    public function __construct($url)
    {
        
        $this->url = trim($url, '/');
    }

    public function get(string $path, string $action, $roles = [])
    {
        $this->routes['GET'][] = new Route($path, $action, $roles);
    }

    public function post(string $path, string $action, $roles = [])
    {
        $this->routes['POST'][] = new Route($path, $action, $roles);
    }

    public function run()
    {
        foreach ($this->routes[$_SERVER['REQUEST_METHOD']] as  $route) {
            if ($route->matches($this->url)) {
                return $route->execute();
            }
        }

        throw new NotFoundException();
    }
}
