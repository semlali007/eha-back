<?php

namespace App\Repository;

use App\Models\Documentation ;
/**
 * Description of DocumentationRepository
 *
 * @author HGDDEDBN
 */
class DocumentationRepository extends AbstractRepository{
 
    /**
     * 
     * @return array|null
     */
    public function getList(int $parent = 0):?array
    {
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
    
}