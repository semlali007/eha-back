<?php

namespace Core\Router;

use Database\DBConnection;
use Core\Util\HealthCheck as health ;
use Core\Http\Response;
class Route
{
    public $path;
    public $action;
    public $matches;
    public $roles;
    public $provider;

    public function __construct($path, $action, $options = [])
    {
        $this->path = trim($path, '/');
        $this->action = $action;
        $this->roles = (!array_key_exists("roles", $options)?[]:$options["roles"]);
        $this->provider = (!array_key_exists("provider", $options)?[]:$options["provider"]);
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
        $controller = new $params[0]($this->roles);
        $method = $params[1];
        // URL-décodage des paramètres de path. $_SERVER['REQUEST_URI'] est brute
        // (ex: "ORACLE%20AMERICA"), il faut décoder avant de passer au contrôleur
        // sinon les espaces, accents, caractères spéciaux cassent la recherche.
        // On utilise rawurldecode (et non urldecode) pour ne PAS transformer "+" en
        // espace : dans un path, "+" est littéral.
        switch (count($this->matches)) {
            case 1:
                return $controller->$method();
                break;
            case 2:
                return $controller->$method(rawurldecode($this->matches[1]));
                break;
            case 3:
                return $controller->$method(rawurldecode($this->matches[1]), rawurldecode($this->matches[2]));
                break;
            case 4:
                return $controller->$method(rawurldecode($this->matches[1]), rawurldecode($this->matches[2]), rawurldecode($this->matches[3]));
                break;
            default:
                return $controller->$method();
                break;
        }
    }
    
    
    /**
     * Check Availability of HLK
     * @return void
     */
    public function checkHLK():void {
        if (in_array(health::HLK_SOURCE,$this->provider)) :
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
