<?php

namespace App\Repository\Hotline;

use App\Models\HotlineTheme;
use App\Repository\AbstractRepository;

class HotlineThemeRepository extends AbstractRepository
{
    public function getList()
    {
        $theme = new HotlineTheme();
        return $theme->getAll();
    }
    public function get($id)
    {
        $theme = new HotlineTheme();
        return $theme->get($id);
    }
    public function delete(mixed $id):mixed {
        $theme = new HotlineTheme();
        return $theme->delete($id);
    }
}