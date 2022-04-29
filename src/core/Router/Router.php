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

    public function get(string $path, string $action, $options = [])
    {
        $this->routes['GET'][] = new Route($path, $action, $options);
    }

    public function post(string $path, string $action, $options = [])
    {
        $this->routes['POST'][] = new Route($path, $action, $options);
    }

    public function run()
    {   
        foreach ($this->routes[$_SERVER['REQUEST_METHOD']] as  $route) {
            if ($route->matches($this->url)) {
                $route->checkHLK();
                return $route->execute();
            }
        }

        throw new NotFoundException();
    }
    
    
}
