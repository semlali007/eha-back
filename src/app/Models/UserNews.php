<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use Exception;
use PDO;

class UserNews extends Model
{
    protected $tableName = 'user_news';

    public function checkNni(int $idNews, string $userNni, string $page)
    {
        $pdo = $this->db->getPDO();
        try {
            $query = 'SELECT DISTINCT * FROM ' . $this->table  . '  WHERE id_news = :idNews AND nni_utilisateur = :userNni AND page=:page';

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':idNews', $idNews, $pdo::PARAM_INT);
            $sth->bindParam(':userNni', $userNni, $pdo::PARAM_STR);
            $sth->bindParam(':page', $page, $pdo::PARAM_STR);
            $sth->execute();
            return  $sth->rowCount();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function insertUserNews($idNews,$userNni, string $page)
    {
        $pdo = $this->db->getPDO();
        try {
            $query = 'INSERT INTO ' . $this->table  . ' (id_news, nni_utilisateur, page) VALUES (:id_news, :nni_utilisateur, :page)';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id_news', $idNews, $pdo::PARAM_INT);
            $sth->bindParam(':nni_utilisateur', $userNni, $pdo::PARAM_STR);
            $sth->bindParam(':page', $page, $pdo::PARAM_STR);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
