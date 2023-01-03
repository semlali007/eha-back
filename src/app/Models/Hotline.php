<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Models\HotlineFil;
use Exception;
use PDO;

class Hotline extends Model {

    protected $tableName = 'hotline';
    protected $hotelineFileTable;
    static $status = [
        "AUTOMATIQUE" => 1,
        "GESTIONNAIRE" => 2,
        "TIERS" => 3
    ] ;
    public function __construct() {
        parent::__construct();
        $this->hotelineFileTable = (new HotlineFil())->table;
    }
    
    public function getAll(string $date=""){
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
                    ) AS hf 
                    LEFT JOIN  {$this->table}   AS h
                    ON hf.discussion = h.discussion
                    ORDER BY h.opened_at DESC
                ";
                    
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':date', $date, PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
    }
    
    public function getList($options) {
        try {
            $email = strtolower($options['email']);
            $categoryWhere = (array_key_exists('category', $options)&& !empty($options['category'])) ?" WHERE h.category_id =:category " : '' ;
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
                $sth->bindParam(':category', $options['category'], PDO::PARAM_STR);
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
            } catch (\PDOException $Exception) {
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
            } catch (\PDOException $Exception) {
                // PHP Fatal Error. Second Argument Has To Be An Integer, But PDOException::getCode Returns A
                // String.
                throw new MyDatabaseException($Exception->getMessage(), $Exception->getCode());
            }
        }

        $sth->execute();
        $recepients =  $sth->fetchall(PDO::FETCH_ASSOC);
        $emails = [];
        foreach ($recepients as $recepient) {
            if (isset($recepient['email']) && !empty($recepient['email'])) {
                $emails[$recepient['email']] = $recepient['email'];
            }
        }
        return $emails;
    }


   /**
    * 
    * @param int|null $id
    * @param string|null $note
    * @param int|null $type
    * @return void
    * @throws InternalServerException
    */
    public function close(?int $id, ?string $note,?int $type):void
    {
        try {

            $query = ' UPDATE ' . $this->table . ' SET closed_at = now(), closed_note =:note, closed_type=:type WHERE discussion =:id AND closed_at IS NULL ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':note', $note, PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->bindParam(':type', $type, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function getAllRecepients($id) {
        try {
            $query = '
            SELECT DISTINCT u.email FROM (
                (SELECT email_from as email FROM  ' . $this->table . ' WHERE discussion =:id)
                UNION ALL
                (SELECT email FROM ' . $this->hotelineFileTable . ' WHERE discussion =:idd)
            ) as u
       ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->bindParam(':idd', $id, PDO::PARAM_INT);
            $sth->execute();
            $recepients = $sth->fetchall(PDO::FETCH_ASSOC);
            $emails = [];
            foreach ($recepients as $recepient) {
                if (isset($recepient['email']) && !empty($recepient['email'])) {
                    $emails[$recepient['email']] = $recepient['email'];
                }
            }
            return $emails;

        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getHotlines(){

            try {
                $query = "
                   SELECT f.discussion, t.label as theme,e.groupe,e.category, h.info_entity, date_trunc('second',f.quand) as quand,'en cours' as status , 
                  f.nature,   h.closed_type, f.email, f.message, f.row, f.is_hotline, h.closed_at  FROM "
                        .$this->hotelineFileTable." AS f LEFT JOIN " . $this->table . 
                        " AS h ON f.discussion = h.discussion LEFT JOIN ".(new HotlineEmail())->table.
                        " AS e ON h.category_id = e.id LEFT JOIN ".(new HotlineTheme())->table." AS t ON h.theme_id = t.id 
                            ORDER BY f.discussion, quand
           ";
    
                $sth = $this->db->getPDO()->prepare($query);
                $sth->execute();
                return $sth->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                var_dump($e->getMessage()) ;
                
                throw new InternalServerException();
            }

    }
    
    
     /**
     * 
     * @param string $endDate
     * @return mixed
     */
    public function getExpiredDiscussion(mixed $endDate = 30): mixed {
        $maxDays = $endDate;
        if ($maxDays < 1) {
            exit();
        }
        // On va identifier l'ensemble des fils de discussion ouverts
        // et du côté de l'utilisateur, et sans réponse de ce dernier depuis
        // au moins "$maxdays" jours.
        $now = date('Y-m-d');
        $query = "SELECT d.discussion, h.question, concat(h.email_from, ',', d.emails) as emails
	FROM (
		SELECT d.discussion, string_agg(DISTINCT hf.email, ',') as emails
		FROM (
			SELECT h.discussion
			FROM {$this->table} AS h
			LEFT JOIN (
				SELECT discussion, max(quand) as quand
				FROM {$this->hotelineFileTable}
				GROUP BY discussion
			) AS md 
			ON md.discussion = h.discussion
			LEFT JOIN {$this->hotelineFileTable}  AS hf
			ON hf.discussion = md.discussion AND hf.quand = md.quand
			WHERE h.closed_at IS NULL 
			
                        AND DATE_PART('day', '$now' - md.quand) >= {$maxDays}
		) AS d
		LEFT JOIN {$this->hotelineFileTable} AS hf
		ON hf.discussion = d.discussion
		GROUP BY d.discussion
	) AS d
	LEFT JOIN {$this->table} AS h
	ON h.discussion = d.discussion
";
        $sth = $this->db->getPDO()  ->prepare($query);
        $sth->execute(); // Lancer la requete
        return $sth->fetchAll(\PDO::FETCH_ASSOC); // Recupérer les infos sous forme une table
    }
    
    function update($id){
        try{
        $query=" UPDATE  ". $this->table . "  SET closed_at = now(), closed_note = null "
                . "WHERE discussion =:id  AND closed_at IS NULL" ;
        $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

}
