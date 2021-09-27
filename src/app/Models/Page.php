<?php

namespace App\Models;
use PDO ;
class Page extends Model{

    protected $table = 'menu_documentations';
    
    /**
     * 
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getItems(int $id):array {
        try {
            $sth =  $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE parent = :parent_id ORDER BY position asc');
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
            $sth =  $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id');
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
    public function getMenus($parentId):array {
        try {
            $sth =  $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE parent = :parent_id ORDER BY position asc');
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
    public function add($data) {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (label, position, parent, link, breadcrumb, external, warning, description) VALUES (:label, (select coalesce(max(position), 0) + 1 FROM ' . $this->table . ' WHERE  parent=:parent_id), :parent, :link, :breadcrumb, :external, :warning, :description);
        ';
            $sth =  $this->db->getPDO()->prepare($query);
            $sth->bindParam(':label', $data['label'], PDO::PARAM_STR);
            $sth->bindParam(':parent_id', $data['parent'], PDO::PARAM_STR);
            $sth->bindParam(':parent', $data['parent'], PDO::PARAM_STR);
            $sth->bindParam(':link', $data['link'], PDO::PARAM_STR);
            $sth->bindParam(':breadcrumb', $data['breadcrumb'], PDO::PARAM_STR);
            $sth->bindParam(':external', $data['external'], PDO::PARAM_BOOL);
            $sth->bindParam(':warning', $data['warning'], PDO::PARAM_STR);
            $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new Exception('erreur d\'ajout de contact' . $e->getMessage());
        }
    }

    /**
     * 
     * @param type $data
     * @throws Exception
     */
    public function update($data) {
        try {
            $id = $data['id'];
            try {
                $query = '
                UPDATE ' . $this->table . ' set label=:label, link=:link, breadcrumb=:breadcrumb, external=:external, warning=:warning, description=:description WHERE id=:id;
        ';
                $sth =  $this->db->getPDO()->prepare($query);
                $sth->bindParam(':label', $data['label'], PDO::PARAM_STR);
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
     * @param type $id
     * @param type $newPosition
     * @throws Exception
     */
    public function updatePosition($id, $newPosition) {
        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set position=:position WHERE id=:id;
        ';
                $sth =  $this->db->getPDO()->prepare($query);
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
            $sth =  $this->db->getPDO()->prepare('SELECT * FROM ' . $this->table . ' WHERE id = :id');
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 
     * @param type $id
     * @throws Exception
     */
    public function delete($id) {
        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
            $sth =  $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            $query = 'DELETE FROM ' . $this->table . ' WHERE parent != 0 and parent not in (select id from ' . $this->table . ' )';
            $sth =  $this->db->getPDO()->prepare($query);
            $sth->execute();
        } catch (\Exception $e) {
            throw new Exception('erreur de suppression de contact');
        }
    }

}
