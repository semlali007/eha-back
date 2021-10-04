<?php

namespace App\Repository;

use App\Models\Documentation;

/**
 * Description of DocumentationRepository
 *
 * @author HGDDEDBN
 */
class DocumentationRepository extends AbstractRepository {

    /**
     * Ajout des nouvels elements
     * @return array
     */
    public function add() {
        try {
            return [
                'id' =>(new Documentation($this->db))->add(\Core\Http\Request::input())
            ];
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
     * @return array|null
     */
    public function getList(int $parent = 0): ?array {
        return (new Documentation($this->db))->getByParentId($parent);
    }

    /**
     * 
     * @param array $options
     * @return void
     */
    public function edit(array $options = []): void {
        try {
            $documentation = (new Documentation($this->db))->update($options);
            foreach ($options['items'] as $item) {
                $documentation->updatePosition($item['id'], $item['position']);
            }
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
     * @return type
     */
    public function delete(int $id) {

        return json_encode(new Documentation($this->db))->delete($id);
    }
    
     /**
     * 
     * @param int $id
     * @return type
     */
    public function details(int $id) {

        return (new Documentation($this->db))->details($id);
    }

}
