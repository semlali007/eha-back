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

    /**
     * 
     * @param array $my_array
     * @return array
     */
    static function getRandomElements(array $my_array,int $nbr = 2):array {
        $result = [] ;
        foreach (array_rand($my_array, $nbr) as $key) {
            array_push($result, $my_array[$key]);
        }
        return $result ;
    }

    public static function getFormattedWords($words)
    {
        $searchText = strtolower(str_replace("\n", "", $words));
        $searchText = str_replace("\r", "", $searchText);
        $searchText = str_replace("\t", "", $searchText);
        $unwanted_array = array(
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
        );
        $searchText = strtr($searchText, $unwanted_array);
        $searchText = preg_replace('/[^A-Za-z0-9]/', ' ', $searchText);
        $worldsList =  explode(" ", $searchText);
        $worldsList = str_replace(' ', "", $worldsList);
        $articles = array("les", "des", "pour", "avec", "sans");
        $worldsList = str_replace($articles, "", $worldsList);
        $list = [];
        foreach ($worldsList as $w) {
            if (strlen($w) > 2) {
                if (!str_ends_with($w, 's')) {
                    $list[] = $w . 's';
                } else {
                    $list[] = substr($w, 0, -1);
                }
                $list[] = $w;
            }
        }
        return implode("|", $list);
    }

    public static function getWords($words)
    {
        $searchText = strtolower(str_replace("\n", "", $words));
        $searchText = str_replace("\r", "", $searchText);
        $searchText = str_replace("\t", "", $searchText);
        $unwanted_array = array(
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
        );
        $searchText = strtr($searchText, $unwanted_array);
	    $searchText = preg_replace('/[^A-Za-z0-9]/', ' ', $searchText);
        $worldsList =  explode(" ", $searchText);
        $worldsList = str_replace(' ', "", $worldsList);
        $list = [];
        foreach ($worldsList as $w) {
            if (strlen($w) > 2) {
                $list[] = strtolower($w);
            }
        }

        return $list;
    }


    public static function getWordsFromText($words)
    {
        $searchText = strtolower(str_replace("\n", "", $words));
        $searchText = str_replace("\r", "", $searchText);
        $searchText = str_replace("\t", "", $searchText);
        $unwanted_array = array(
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
        );
        $searchText = strtr($searchText, $unwanted_array);
        $searchText = preg_replace('/[^A-Za-z0-9]/', ' ', $searchText);
        $worldsList =  explode(" ", $searchText);
        $worldsList = str_replace(' ', "", $worldsList);
        $list = [];
        foreach ($worldsList as $w) {
            if (strlen($w) > 0) {
                $list[] = $w;
            }
        }
        return $list;
    }

    public static function getWithOrWithoutPlural($worldsList)
    {
        $list = [];
        foreach ($worldsList as $w) {
            if (strlen($w) > 2) {
                if (!str_ends_with($w, 's')) {
                    $list[] = $w . 's';
                } else {
                    $list[] = substr($w, 0, -1);
                }
                $list[] = $w;
            }
        }
        return $list;
    }

    public static function arrayDiffAssocRecursive($array1, $array2)
    {
        foreach ($array1 as $key => $value) {
            if (is_array($value)) {
                if (!isset($array2[$key])) {
                    $difference[$key] = $value;
                } elseif (!is_array($array2[$key])) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = self::arrayDiffAssocRecursive($value, $array2[$key]);
                    if ($new_diff != FALSE) {
                        $difference[$key] = $new_diff;
                    }
                }
            } elseif (!isset($array2[$key]) || $array2[$key] != $value) {
                $difference[$key] = $value;
            }
        }
        return !isset($difference) ? 0 : $difference;
    }
}
