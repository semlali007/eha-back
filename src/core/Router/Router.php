<?php

namespace Core\Router;

use App\Exceptions\NotFoundException;
use Core\Util\HealthCheck as health ;
use Core\Http\Response;
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
        $this->checkHLK();
        foreach ($this->routes[$_SERVER['REQUEST_METHOD']] as  $route) {
            if ($route->matches($this->url)) {
                return $route->execute();
            }
        }

        throw new NotFoundException();
    }
    
    /**
     * 
     * Check Availability of HLK
     */
    public function checkHLK() {
        $routes = $GLOBALS['configuration']['hlk']["routes"];
        if (array_filter($routes, function ($k) {
                    return str_contains($k, $this->url);
                }, ARRAY_FILTER_USE_KEY)) :
            if (!health::isActiveHlk()) :
                http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
                return json_encode(["error" => health::HLK_ERROR_MSG]);
            endif;
        endif;
    }

}
