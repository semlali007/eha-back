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
        // Check call if HLK is enabled
        if (strtoupper(getenv("ENV")) === "DEV" && (str_contains($url, getenv("HLK_API_BASE_URL")))) {
            return json_decode(file_get_contents(__DIR__ . "/../../public/data-mock/" . $query["action_code"] . '.json'), true);
        }
        
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
    public static function get(string $url, array $data = []) {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
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

}
