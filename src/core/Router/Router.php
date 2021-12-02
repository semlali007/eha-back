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
     * Check Availability of HLK
     * @return void
     */
    public function checkHLK():void {
        $routes = $GLOBALS['configuration']['hlk']["routes"];
        $checkRoute = array_filter($routes, function ($k,$v) {
                    return ((trim($v) === trim($this->url)) || (str_contains($v, $this->url)));
                }, ARRAY_FILTER_USE_BOTH) ;
        if ($checkRoute) :
            if (!health::isActiveHlk()) :
                http_response_code(Response::HTTP_SERVICE_UNAVAILABLE);
                print_r(json_encode([
                    "source" => health::HLK_SOURCE,
                    "error" => health::HLK_ERROR_MSG,
                    "status" => Response::HTTP_SERVICE_UNAVAILABLE
                ]));
                exit;
            endif;
        endif;
    }

}
