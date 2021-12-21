<?php

namespace App\Manager;

use App\Models\Page;
use Exception;

class PageManager extends AbstractManager
{
    public function update($pageCode, $data)
    {
        try {
            $page = new Page();
            $id =  $page->update($pageCode, $data);
            return [
                'status' => true,
                'id' => $id
            ];
        } catch (Exception $ex) {
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }
}
