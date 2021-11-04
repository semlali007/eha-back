<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Models\HotlineFil;
use Exception;
use PDO;

class Hotline extends Model {

    protected $tableName = 'hotline';
    protected $hotelineFileTable;

    public function __construct() {
        parent::__construct();
        $this->hotelineFileTable = (new HotlineFil())->table;
    }

    public function getList($options) {
        try {
            $email = $options['email'];
            $categoryWhere = '';
            if (!empty($options['category'])) {
                $categoryWhere = " WHERE h.category_id =:category ";
            }

            $query = "
                    SELECT hf.discussion, hf.is_hotline, h.opened_at, h.closed_at, h.question, h.sha_demandeur, h.sha_hotline, hf.natures
                    FROM (
                        SELECT DISTINCT hf.discussion, hf.is_hotline, n.natures
                        FROM  {$this->hotelineFileTable}  AS hf
                        LEFT JOIN (
                            SELECT discussion, string_agg(nature, '') as natures
                            FROM  {$this->hotelineFileTable}  
                            WHERE discussion IN (
                                SELECT DISTINCT discussion
                                FROM  {$this->hotelineFileTable} 
                                WHERE email = :email
                                AND quand > :date 
                            )
                            GROUP BY discussion
                        ) AS n
                        ON hf.discussion = n.discussion
                        WHERE hf.email = :emailValue 
                    ) AS hf 
                    LEFT JOIN  {$this->table}   AS h
                    ON hf.discussion = h.discussion
                     $categoryWhere 
                    ORDER BY h.opened_at DESC
                ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $email, PDO::PARAM_STR);
            $sth->bindParam(':date', $options['date'], PDO::PARAM_STR);
            $sth->bindParam(':emailValue', $email, PDO::PARAM_STR);
            if (!empty($options['category'])) {
                $sth->bindParam(':category', $category, PDO::PARAM_STR);
            }

            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getHotline($id) {
        try {
            $query = '
        SELECT a.discussion as id, 
               a.opened_at,
               a.email_from,
               a.info_entity,
               c.label as info_theme,
               b.category as info_category,
               a.question,
               a.closed_at,
               a.closed_note FROM ' . $this->table . ' AS a 
               LEFT JOIN ' . (new HotlineEmail())->table . ' AS b ON a.category_id = b.id 
               LEFT JOIN ' . (new HotlineTheme())->table . ' AS c ON a.theme_id = c.id 
               WHERE a.discussion = :id 
       ';

            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getInfos($id, $sha) {
        try {
            $res = null;
            $query = '
            SELECT h.sha_demandeur, h.sha_hotline, he.groupe
            FROM ' . $this->table . ' AS h
            LEFT JOIN ' . (new HotlineEmail())->table . ' AS he
            ON h.category_id = he.id
            WHERE h.discussion = :id AND h.closed_at IS NULL
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            $row = $sth->fetch(PDO::FETCH_ASSOC);

            // we send the data
            if (!empty($row)) {
                // we need to check the key and to define if hotline or not
                if ($sha === $row['sha_demandeur']) {
                    $res = false;
                } else if ($sha === $row['sha_hotline']) {
                    $res = true;
                }
            }
            // we send the data
            return array(
                'status' => $res, // null => ???, true => Hotline, false => demandeur
                'sha_demandeur' => (empty($row['sha_demandeur'])) ? null : $row['sha_demandeur'],
                'sha_hotline' => (empty($row['sha_hotline'])) ? null : $row['sha_hotline'],
                'groupe' => (empty($row['groupe'])) ? null : $row['groupe'], // pour Hotline
            );
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function insertRequest($options) {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (opened_at, email_from, info_entity, theme_id, category_id, question, sha_demandeur, sha_hotline) VALUES (now(), :email, :entity, :theme, :category, :post, :sha_demandeur, :sha_hotline)
    ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':email', $options['email'], PDO::PARAM_STR);
            $sth->bindParam(':entity', $options['entity'], PDO::PARAM_STR);
            $sth->bindParam(':theme', $options['theme'], PDO::PARAM_STR);
            $sth->bindParam(':category', $options['category'], PDO::PARAM_STR);
            $sth->bindParam(':post', $options['post'], PDO::PARAM_STR);
            $sth->bindParam(':sha_demandeur', $options['sha_demandeur'], PDO::PARAM_STR);
            $sth->bindParam(':sha_hotline', $options['sha_hotline'], PDO::PARAM_STR);
            $sth->execute();
            return $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    function getCounterparts($options) {
        if (isset($options['is_hotline']) && $options['is_hotline']) {
            // La contrepartie de la hotline est le demandeur.
            try {
                $query = "
                SELECT DISTINCT u.email FROM (
                    (SELECT email_from AS email FROM {$this->table} WHERE discussion = :id)
                    UNION ALL
                    (SELECT email FROM {$this->hotelineFileTable} WHERE discussion = :discussion AND is_hotline = false)
                ) AS u
            ";

                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':id', $options['id'], PDO::PARAM_INT);
                $sth->bindParam(':discussion', $options['id'], PDO::PARAM_INT);
            } catch (PDOException $Exception) {
                // PHP Fatal Error. Second Argument Has To Be An Integer, But PDOException::getCode Returns A
                // String.
                throw new MyDatabaseException($Exception->getMessage(), $Exception->getCode());
            }
        } else {
            try {
                // La contrepartie du demandeur est la hotline.
                $query = "
                SELECT DISTINCT email FROM {$this->hotelineFileTable} WHERE discussion = :id AND is_hotline = true
            ";
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':id', $options['id'], PDO::PARAM_INT);
            } catch (PDOException $Exception) {
                // PHP Fatal Error. Second Argument Has To Be An Integer, But PDOException::getCode Returns A
                // String.
                throw new MyDatabaseException($Exception->getMessage(), $Exception->getCode());
            }
        }

        $sth->execute();
        $res = $sth->fetchall(PDO::FETCH_ASSOC);

        $to = '';
        foreach ($res as $row) {
            $to .= $row['email'] . ', ';
        }
        $to = substr($to, 0, -2);
        return $to;
    }

}
