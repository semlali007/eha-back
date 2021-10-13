<?php

namespace App\Security;

/**
 * Description of Role
 *
 * @author HGDDEDBN
 */
class Role {

    const DEFAULT = "USER";

    /**
     * 
     * @param array $params
     * @return array
     */
    public static function format(array $params): array {
        
        return array_map(function($val) {
            $arrLine = explode(",", $val);
            if (is_array($arrLine)):
                $roleLine = explode("=", reset($arrLine));
                return (is_array($roleLine) && (count($roleLine) > 0)) ? $roleLine[1] : false;
            endif;
        }, $params);
    }

}
