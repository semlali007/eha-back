<?php

namespace App\Repository;

use App\Models\Page;

class PageRepository extends AbstractRepository
{
    public function getContent($pageCode)
    {
        $page = new Page();
        return $page->getContent($pageCode);
    }
}