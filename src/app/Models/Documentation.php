<?php

namespace App\Models;

use Exception;
use PDO ;
/**
 * Description of Documentation
 *
 * @author HGDDEDBN
 */
class Documentation extends Model {

    protected $tableName = 'menu_documentations';

    /**
     * 
     * @param type $parentId
     */
    public function getByParentId($parentId) {
        $response= [];
        $documents = $this->getItems($parentId); 
        
        return $response ;
    }

    /**
     * get details
     */
    public function details(int $id) {
        try {
            $detail = $this->getMenu($id);
            $detail['items'] = $this->getMenus($id);

            $ItemParentId = $detail['parent'] ?? 0;
            $position = 1;
            $parents = [];
            while ($ItemParentId !== 0) {
                $item = $this->getById($ItemParentId);
                $item['position'] = $position;
                $position++;
                $parents[] = $item;
                $ItemParentId = $item['parent'];
            }

            $detail['parents'] = $parents;
            return $detail;
        } catch (Exception $ex) {
            http_response_code(500);
            echo json_encode([
                'error' => $ex->getCode(),
                'message' => 'un problème est survenu. réessayez plus tard',
            ]);
        }
    }

    /**
     * 
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getItems(int $id): array {
        try {
            $sth = $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE parent = :parent_id ORDER BY position asc');
            $sth->bindParam(':parent_id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getById(int $id) {
        try {
            $sth = $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id');
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $parentId
     * @return array
     * @throws Exception
     */
    public function getMenus($parentId): array {
        try {
            $sth = $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE parent = :parent_id ORDER BY position asc');
            $sth->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $data
     * @return type
     * @throws Exception
     */
    public function add(array $data) {
      
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (label, shortdescription ,position, parent, link, breadcrumb, external, warning, description) VALUES (:label,:shortdescription, (select coalesce(max(position), 0) + 1 FROM ' . $this->table . ' WHERE  parent=:parent_id), :parent, :link, :breadcrumb, :external, :warning, :description);
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':label', $data['label'], PDO::PARAM_STR);
            $sth->bindParam(':shortdescription', $data['shortdescription'], PDO::PARAM_STR);
            $sth->bindParam(':parent_id', $data['parent'], PDO::PARAM_STR);
            $sth->bindParam(':parent', $data['parent'], PDO::PARAM_STR);
            $sth->bindParam(':link', $data['link'], PDO::PARAM_STR);
            $sth->bindParam(':breadcrumb', $data['breadcrumb'], PDO::PARAM_STR);
            $sth->bindParam(':external', $data['external'], PDO::PARAM_BOOL);
            $sth->bindParam(':warning', $data['warning'], PDO::PARAM_STR);
            $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $sth->execute();
            return $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new \Exception('erreur d\'ajout de contact' . $e->getMessage());
        }
    }

    /**
     * 
     * @param type $data
     * @throws Exception
     */
    public function update(array $data) {
        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set label=:label, shortdescription=:shortdescription, link=:link, breadcrumb=:breadcrumb, external=:external, warning=:warning, description=:description WHERE id=:id;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':label', $data['label'], PDO::PARAM_STR);
                $sth->bindParam(':shortdescription', $data['shortdescription'], PDO::PARAM_STR);
                $sth->bindParam(':link', $data['link'], PDO::PARAM_STR);
                $sth->bindParam(':breadcrumb', $data['breadcrumb'], PDO::PARAM_STR);
                $sth->bindParam(':external', $data['external'], PDO::PARAM_BOOL);
                $sth->bindParam(':warning', $data['warning'], PDO::PARAM_STR);
                $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
                $sth->bindParam(':id', $data['id'], PDO::PARAM_INT);
                $sth->execute();
            } catch (\Exception $e) {
                throw new Exception('erreur de modification de contact' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param int $id
     * @param int $newPosition
     * @throws Exception
     */
    public function updatePosition(int $id, int $newPosition) {
        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set position=:position WHERE id=:id;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':position', $newPosition, PDO::PARAM_INT);
                $sth->bindParam(':id', $id, PDO::PARAM_INT);
                $sth->execute();
            } catch (\Exception $e) {
                throw new Exception('erreur de modification de contact' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $id
     * @return type
     * @throws Exception
     */
    public function getMenu($id) {
        try {
            $sth = $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id');
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param int $id
     * @throws Exception
     */
    public function delete(int $id) {
        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            $query = 'DELETE FROM ' . $this->table . ' WHERE parent != 0 and parent not in (select id from ' . $this->table . ' )';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            throw new Exception('erreur de suppression de contact');
        }
    }

     /**
     * 
     * @return array
     * @throws Exception
     */
    public function getAll(): array {
        try {
            $sth = $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table .' ORDER BY position asc');
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


}
