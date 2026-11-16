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
            if (strlen($w) >= 2) {
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

        // 1) Tokens "composites" : on split uniquement sur les espaces.
        //    Préserve les références à séparateur (ex. "22-009", "ref-001", "l'achat").
        $compositeList = preg_split('/\s+/', $searchText, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // 2) Tokens "atomiques" : comportement historique — tout caractère non
        //    alphanumérique devient un espace, puis on split. Décompose les composites
        //    pour permettre les matches partiels (ex. "22-009" → "22" + "009").
        $stripped = preg_replace('/[^A-Za-z0-9]/', ' ', $searchText);
        $atomicList = preg_split('/\s+/', $stripped, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        // Union : on garde les deux versions pour maximiser les matches côté TS_QUERY.
        // array_values pour ré-indexer proprement (TS_QUERY n'aime pas les clés string).
        return array_values(array_unique(array_merge($compositeList, $atomicList)));
    }

    public static function getWithOrWithoutPlural($worldsList)
    {
        $list = [];
        foreach ($worldsList as $w) {
            if ($w === '' || $w === null) {
                continue;
            }
            // On garde toujours le mot original — il ne doit jamais être perdu
            // (ex: codes courts type "22", "IT", "RH", "5G"...)
            $list[] = $w;
            // La variante singulier/pluriel n'est générée que pour les mots > 2 chars
            // (sur 1-2 chars ça produit du bruit : "22s", "is" pour "i", etc.)
            if (strlen($w) > 2) {
                if (!str_ends_with($w, 's')) {
                    $list[] = $w . 's';
                } else {
                    $list[] = substr($w, 0, -1);
                }
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
