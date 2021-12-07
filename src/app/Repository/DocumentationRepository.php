<?php

namespace App\Repository;

use App\Models\Documentation;
use Exception;
use PDO ;

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
                'id' =>(new Documentation())->add(\Core\Http\Request::input())
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
    public function getList(): ?array {
        return (new Documentation())->getAll();
    }

    /**
     * 
     * @param array $options
     * @return void
     */
    public function edit(array $options = []): void {
        try {
            (new Documentation())->update($options);
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

        return (new Documentation())->delete($id);
    }
    
     /**
     * 
     * @param int $id
     * @return type
     */
    public function details(int $id) {

        return (new Documentation())->details($id);
    }


     /**
     * 
     * @param array $options
     * @return void
     */
    public function sort(array $options = []): void
    {
        try {
            $documentation = new Documentation();
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
}
