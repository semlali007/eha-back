<?php

namespace App\Repository;

use App\Models\News;
use App\Models\UserNews;

class NewsRepository extends AbstractRepository
{
    public function getNews()
    {
        $news = new News();
        return $news->getNews();
    }

    public function checkNni(int $idNews, string $userNni)
    {
        $userNews = new UserNews();
        return $userNews->checkNni($idNews,$userNni);
    }

    public function insertUserNews($id,$monNNI) {
        $userNews = new UserNews();
        return $userNews->insertUserNews($id,$monNNI);
    }
    
     public function getList()
    {
        $news = new News();
        return $news->getNews();
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

}