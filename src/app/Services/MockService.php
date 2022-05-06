<?php

namespace App\Services;

/**
 * Description of MockService
 *
 * @author HGDDEDBN
 */
class MockService {

    const DATA_MOCK_PATH = __DIR__ . "/../../public/data-mock/" ;
    
    /**
     * 
     * @return mixed
     */
    static function createStatic(mixed $actionCode): mixed {
        return json_decode(file_get_contents(self::DATA_MOCK_PATH . $actionCode . '.json'), true);
    }

}
