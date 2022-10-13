<?php

namespace App\Manager\Hotline;

use App\Manager\AbstractManager;
use App\Models\HotlineTheme;
use Exception;

class HotlineThemeManager extends AbstractManager
{
    public function update($options)
    {
        try {
            
            if (!isset($options['id']) || empty($options['id'])) {
                throw new Exception('id est obligatoire');
            }
            $hotlineTheme = new HotlineTheme();
            $hotlineTheme->update($options);
            return [
                'status' => true,
                'message' => 'thème modifié'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function add($options)
    {
        try {
            
            $hotlineTheme = new HotlineTheme();
            $hotlineTheme->add($options);
            return [
                'status' => true,
                'message' => 'thème modifié'
            ];
        } catch (Exception $ex) {
            return [
                'status' => false,
                'message' => $ex->getMessage(),
            ];
        }
    }
}
