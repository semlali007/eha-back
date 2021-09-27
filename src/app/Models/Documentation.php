<?php

namespace App\Models;

/**
 * Description of Documentation
 *
 * @author HGDDEDBN
 */
class Documentation extends Page {

    protected $tableName = 'menu_documentations';

    public function getList() {

        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table . ' ORDER BY label asc';
            $sth = $this->db->getPDO()->prepare($query);

            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

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
