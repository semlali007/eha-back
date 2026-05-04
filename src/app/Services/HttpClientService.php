<?php

namespace App\Services;

use Exception;
use Core\Util\Json;
use App\Exceptions\HttpException ;
 

class HttpClientService {

    
    
    /**
     * 
     * @param string $url
     * @param array $query
     * @param array $headers
     * @return type
     * @throws Exception
     */
    public static function exec_post(string $url, array $query, array $headers = Json::DEFAULT_HEADER) {
        $curl = self::init($url, $headers, $query);
        $responses = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new HttpException($error);
        } 
        
        $json = json_decode($responses, true);
        curl_close($curl);
       
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new HttpException('json non valide', $httpCode);
        }
        return $json;
    }

    /**
     * 
     * @param string $url
     * @param array $data
     * @return string
     */
    public static function get(string $url, array $data = []) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_PROXY, '');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception($error);
        } else {
            curl_close($ch);
        }

        return $response;
    }

     /**
      * 
      * @param string $url
      * @param array $query
      * @param array $headers
      * @return mixed
      */
    static function post (string $url, array $query, array $headers = Json::DEFAULT_HEADER):mixed {
        $curl = self::init($url, $headers, $query);
        $responses = curl_exec($curl);
        $json = json_decode($responses, true);
        curl_close($curl);
        return $json ;
    }
    
    /**
     * 
     * @param string|null $url
     * @param mixed $headers
     * @param mixed $query
     * @return mixed
     */
    static function init (string|null $url, mixed $headers,mixed $query): mixed{
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($query));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($curl, CURLOPT_TCP_KEEPIDLE, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_PROXY, '');

        return $curl ;
        
    }

    public static function exec_get(string $url, array $query, array $headers = Json::DEFAULT_HEADER) {
        $headers = array_merge(Json::DEFAULT_HEADER,$headers);
        $curl = self::initGet($url, $headers, $query);
        $responses = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new HttpException($error);
        } 
        
        $json = json_decode($responses, true);
        curl_close($curl);
       
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new HttpException('json non valide', $httpCode);
        }
        return $json;
    }

    static function initGet (string|null $url, mixed $headers,mixed $query): mixed{
        $queryUrl = http_build_query($query);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "$url?$queryUrl");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TCP_KEEPALIVE, 1);
        curl_setopt($curl, CURLOPT_TCP_KEEPIDLE, 2);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);
        curl_setopt($curl, CURLOPT_PROXY, '');

        return $curl ;

    }
}
