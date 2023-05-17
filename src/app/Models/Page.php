<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use PDO;

class Page extends Model
{
    protected $tableName = 'static_page';
    
    static $SHEET_MODAL_NEWS = 'sheet_modal_news';
    static $PURCHASE_MODAL_NEWS = 'purchase_modal_news';


    public function getContent($page)
    {
        try {
            $query = 'SELECT * FROM ' . $this->table  . ' WHERE page=:page';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':page', $page, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {  
            throw new InternalServerException();
        }
    }
  
    /**
     * 
     * @param array $data
     * @throws Exception
     */

    public function update($page, array $data)
    {
        try {
            try {
                $query = '
                UPDATE ' . $this->table . ' set content=:content, active=:active WHERE page=:page;
        ';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':page', $page, PDO::PARAM_STR);
                $sth->bindParam(':content', $data['content'], PDO::PARAM_STR);
                $sth->bindParam(':active', $data['active'], PDO::PARAM_BOOL);
                $sth->execute();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de page' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function insert($page, array $data)
    {
       
        try {
            try {
                $query = '
                INSERT INTO ' . $this->table . ' (page, content, active, title) VALUES (:page, :content, :active, :title) ON CONFLICT DO NOTHING;
                ';
                    $sth = $this->db->getPDO()->prepare($query);
                    $sth->bindParam(':page', $page, PDO::PARAM_STR);
                    $sth->bindParam(':content', $data['content'], PDO::PARAM_STR);
                    $sth->bindParam(':active', $data['active'], PDO::PARAM_BOOL);
                    $sth->bindParam(':title', $page, PDO::PARAM_STR);
                    $sth->execute();
                    return  $this->db->getPDO()->lastInsertId();
            } catch (\Exception $e) {
                throw new \Exception('erreur de modification de page' . $e->getMessage());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

}
