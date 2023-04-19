<?php

namespace App\Repository;

use App\Models\NewsCast;
use App\Models\NewsCastCategory;
use App\Models\NewsCastConfig;
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
        if (isset($options['is_home']) && $options['is_home'] == 1) {
            $newsData = $news->getHomePaginatedList($options);
            $newsConfig = new NewsCastConfig();
            $newsConfigData = $newsConfig->getByConfigLabel('news_home_page');
            $newsData['active'] = (is_array($newsConfigData) && isset($newsConfigData['config_value'])) ? filter_var($newsConfigData['config_value'], FILTER_VALIDATE_BOOL) : false;
            return $newsData;
        }
        return
            $news->getPaginatedList($options);
    }

    public function frontList($options)
    {
        $news = new NewsCast();
        if(isset($options['is_home']) && $options['is_home'] ==1){
           $newsConfig = new NewsCastConfig();
           $config = $newsConfig->getByConfigLabel('news_home_page');
           if(is_array($config) && isset($config['config_value'])  && filter_var($config['config_value'], FILTER_VALIDATE_BOOL)  == true){
            return
            $news->getHomePaginatedFrontList($options);
           }else{
            return ['data'=>[], 'total' => 0];
           }
        }
        return
            $news->getPaginatedFrontList($options);
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
    public function updateHomePage(array $options = []): void
    {
       
        try {
            $news = new NewsCast();
            if(is_bool($options['active'])){
                $newsConfig = new NewsCastConfig();
                $newsConfig->updateByConfig('news_home_page',$options['active'] );
            }

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