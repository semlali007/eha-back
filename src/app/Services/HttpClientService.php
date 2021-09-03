<?php

namespace App\Services;

use Exception;
use Core\Util\Json;

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
        curl_setopt($curl, CURLOPT_TIMEOUT, 360);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 360);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);

        $responses = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new Exception($error);
        } else {
            curl_close($curl);
        }
        return json_decode($responses, true);
    }

    /**
     * 
     * @param string $url
     * @param array $data
     * @return string
     */
    public static function get(string $url, array $data) {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $response = curl_exec($ch);
        curl_close($ch); // Close the connection
        
        return json_decode($response) ;
    }

}
