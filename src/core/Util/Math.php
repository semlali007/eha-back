<?php

namespace Core\Util;

/**
 * Description of Math
 *
 * @author HGDDEDBN
 */
class Math {

    /**
     * 
     * @param mixed $n
     * @return bool
     */
    static function isDecimal(mixed $n): bool {
        // Note that floor returns a float 
        return is_numeric($n) && floor($n) != $n;
    }

    
    /**
     * 
     * @param int $number
     * @param int $percent
     * @return mixed
     */
    static function getPercentOfNumber(int $number, int $percent = 50):mixed {
        return ($percent / 100) * $number;
    }

}
