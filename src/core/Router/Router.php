<?php

namespace Core\Router;

use App\Exceptions\NotFoundException;

class Router {

    public $url;
    public $routes = [];

    public function __construct($url)
    {
        // Strip de la query string avant matching : sinon toute route appelée
        // avec ?param=value produit un 404 (la regex de Route::matches n'accepte
        // pas de trailing content).
        $qPos = strpos($url, '?');
        if ($qPos !== false) {
            $url = substr($url, 0, $qPos);
        }
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
                return $route->execute();
            }
        }

        throw new NotFoundException();
    }
    
    
}
