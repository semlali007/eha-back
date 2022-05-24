<?php

namespace App\Services;

/**
 * Description of SheetService
 *
 * @author HGDDEDBN
 */
class SheetService {
  
    /**
     * 
     * @param string $searchInput
     * @return bool|null
     */
    static function isCodeContract(string $searchInput): ?bool {
        preg_match_all("/c[a-zA-Z_0-9]{9}/", $searchInput, $matches, PREG_PATTERN_ORDER);
        foreach (reset($matches) as $code) {
            if (preg_match("#\d#", $code)) {
               return true ;
            }
        }
        return false;
    }

}
