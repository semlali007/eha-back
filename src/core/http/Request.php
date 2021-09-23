<?php

namespace Core\Http;

/**
 * Description of Request
 *
 * @author HGDDEDBN
 * 
 * Class Request
 * @package Ws\Shared
 */
class Request {

    private const MAX_DECODE_ITERATIONS = 3;

    /**
     * Get: renvoie la valeur d'une clé spécifique du super-global GET
     * Valeur par défaut si la variable n'est pas définie / is NULL.
     * @access public
     * @param string $key
     * @param mixed $default
     * @return mixed
     *
     */
    public static function get(string $key = "", string $default = null) {
        if (self::exists('get')) {
            return (isset($_GET[$key]) && (!empty($_GET[$key])) ? $_GET[$key] : $default);
        }
        return $_GET;
    }

    /**
     * Existe: détermine si une demande existe en vérifiant si le GET ou le POST
     * super-global is empty.
     * @access public
     * @param string $source [optional]
     * @return boolean
     *
     */
    public static function exists(string $source = "post"): bool {
        switch ($source) {
            case 'post':
                return (!empty($_POST));
            case 'get':
                return (!empty($_GET));
        }
        return false;
    }

    /**
     * Post:renvoie la valeur d'une clé spécifique du super-global
     * Valeur par défaut si la variable n'est pas définie / is NULL.
     * @access public
     * @param string $key
     * @param mixed $default
     * @return mixed
     *
     */
    public static function post(string $key, string $default = ""): bool {
        if (self::exists('post')) {
            return (isset($_POST[$key]) && (!empty($_POST[$key])) ? $_POST[$key] : $default);
        }
        return false;
    }

    /**
     * Request:renvoie la valeur d'une clé spécifique du super-global
     * Valeur par défaut si la variable n'est pas définie / is NULL
     * @access public
     * @param string $key
     * @param mixed $default
     * @return mixed
     *
     */
    public static function request(string $key, string $default = ""): bool {
        return (isset($_REQUEST[$key]) && (!empty($_REQUEST[$key])) ? $_REQUEST[$key] : $default);
    }

    /**
     * @param $row
     * @return bool
     */
    public static function if_null($row): bool {
        return is_null($row) || empty($row);
    }

    /**
     * 
     * @param string $option
     * @param int $MAX_DECODE_ITERATIONS
     * @return type
     */
    public static function decodeParam(string $option, int $MAX_DECODE_ITERATIONS = self::MAX_DECODE_ITERATIONS) {

        $name = $option;
        $decodeIteration = 0;
        $decoded = html_entity_decode($option);
        while ($name !== $decoded && $decodeIteration < $MAX_DECODE_ITERATIONS) { // Ensures data is decoded a certain amount of times
            $name = $decoded;
            $decoded = html_entity_decode($name);
            $decodeIteration++;
        }
        return urldecode(htmlspecialchars($name, ENT_QUOTES, 'UTF-8')); // Ensure name is encoded once, before use in HTML
    }

}
