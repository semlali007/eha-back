<?php

namespace App\Repository;

use App\Models\News;
use App\Models\UserNews;

class NewsRepository extends AbstractRepository
{
    public function getNews($type)
    {
        $news = new News();
        return $news->getNews($type);
    }

    public function checkNni(int $idNews, string $userNni, $page)
    {
        $userNews = new UserNews();
        return $userNews->checkNni($idNews,$userNni, $page);
    }

    public function insertUserNews($id,$monNNI, $page) {
        $userNews = new UserNews();
        return $userNews->insertUserNews($id,$monNNI, $page);
    }
    
     public function getList($type)
    {
        $news = new News();
        return $news->getNews($type);
    }
    public function add($data)
    {
        $news = new News();
        return $news->addNews($data);
    }

    public function update($data)
    {
        $news = new News();
        return $news->updateNews($data);
    }

    public function delete($id) {

        $news = new News();
        return $news->deleteNews($id);
    }

    public function getElement($id)
    {
        $news = new News();
        return $news->getElement($id);
    }

    public function getNewsCountOfPage($type, $userNni){
        $news = new News();
        return $news->getNewsCountOfPage($type, $userNni);
    }

}