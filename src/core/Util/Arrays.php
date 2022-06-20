<?php

namespace Core\Util;

/**
 * Description of Arrays
 *
 * @author HGDDEDBN
 */
class Arrays {

    /**
     * 
     * @param array $array
     * @param int $index
     * @param mixed $niddle
     * @return array
     */
    static function filterByValue(array $array, string $index, mixed $niddle): array {
        $len = strlen($niddle);
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $element) {
                if (substr($element[$index], -$len) === $niddle || $len == 0) {
                    $new_array[] = $element;
                }
            }
             return $new_array;
        }
        return [];
    }

}
