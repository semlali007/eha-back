<?php

namespace App\Repository;

use App\Models\Category;

class CategoryRepository extends AbstractRepository
{
    public function getList()
    {
        return (new Category())->getList();
    }
}
