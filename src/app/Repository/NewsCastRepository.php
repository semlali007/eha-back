<?php

namespace App\Repository;

use App\Models\NewsCast;
use App\Models\NewsCastCategory;
use Exception;

class NewsCastRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
       
    }
    public function getNews($id){
        $news = new NewsCast();
        return
        $news->get($id);
    }

    public function list($options)
    {
        $news = new NewsCast();
        if(isset($options['is_home']) && $options['is_home'] ==1){
            return
            $news->getHomePaginatedList($options);
        }
        return
            $news->getPaginatedList($options);
    }
    public function getCategories()
    {
        $news = new NewsCastCategory();
        return
            $news->getList();
    }

    public function create($options)
    {
        $news = new NewsCast();
        return
            $news->create($options);
    }

    public function update($options)
    {
        $news = new NewsCast();
        return
            $news->update($options);
    }
    public function sort(array $options = []): void
    {
        try {
            $news = new NewsCast();
            foreach ($options['items'] as $item) {
                $news->updatePosition($item['id'], $item['position']);
            }
        } catch (Exception $ex) {
            http_response_code(500);
            echo json_encode([
                'error' => $ex->getCode(),
                'message' => 'un problème est survenu. réessayez plus tard',
            ]);
        }
    }
    public function delete(array $options = []): void
    {
        try {
            $news = new NewsCast();
            if(is_numeric($options['id']))
            $news->delete($options['id']);
        } catch (Exception $ex) {
            http_response_code(500);
            echo json_encode([
                'error' => $ex->getCode(),
                'message' => 'un problème est survenu. réessayez plus tard',
            ]);
        }
    }

    
}