<?php

namespace Core\Util;

/**
 * Description of Date
 *
 * @author HGDDEDBN
 */
class Date {

    /**
     * 
     * @param int $days
     * @return string|null
     */
    public static function generateDatePlus(int $days = 30, string $format = "d-m-Y"): ?string {
        $today = (new \DateTimeImmutable())
                ->modify("+{$days} day"); // generer today plus nbr jours
        return $today->format($format);
    }
    
    /**
     * 
     * @param int $days
     * @return string|null
     */
    public static function generateDateMoins(int $days = 1, string $format = "d-m-Y"): ?string {

        // Initialising a interval of 2 days
        $interval = "P$days"."D";
        $today = (new \DateTimeImmutable())
                ->sub(new \DateInterval($interval));
         // generer today moins de nbr jours
        return $today->format($format);
    }

}
