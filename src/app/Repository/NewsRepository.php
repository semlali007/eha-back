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
}