<?php

namespace App\Models;

/**
 * Description of Documentation
 *
 * @author HGDDEDBN
 */
class Documentation extends Page {

    protected $tableName = 'menu_documentations';
 

    /**
     * 
     * @param int $parentId
     * @return type
     */
    public function getByParentId(int $parentId = 0) {
        $parents = [];
        $current = $this->getById($parentId);
        $ItemParentId = $current['parent'] ?? 0;
        $position = 1;
        while ($ItemParentId !== 0) {
            $item = $this->getById($ItemParentId);
            $item['position'] = $position;
            $position++;
            $parents[] = $item;
            $ItemParentId = $item['parent'];
        }
        return [
            'current' => $current,
            'data' => $this->getItems($parentId),
            'parents' => $parents
        ];
    }


}
