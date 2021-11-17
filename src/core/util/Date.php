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

}
