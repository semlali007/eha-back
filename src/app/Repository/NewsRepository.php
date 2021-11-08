<?php

namespace App\Repository;

use App\Models\News;
use App\Models\UserNews;

class NewsRepository extends AbstractRepository
{
    public function getNews()
    {
        $news = new News($this->db);
        return $news->getNews();
    }

    public function checkNni(int $idNews, string $userNni)
    {
        $userNews = new UserNews($this->db);
        return $userNews->checkNni($idNews,$userNni);
    }

    public function insertUserNews($id,$monNNI) {
        $userNews = new UserNews($this->db);
        return $userNews->insertUserNews($id,$monNNI);
    }

    public function getList()
    {
        $news = new News($this->db);
        return $news->getNews();
    }
    public function add($data)
    {
        $news = new News($this->db);
        return $news->addNews($data);
    }

    public function update($data)
    {
        $news = new News($this->db);
        return $news->updateNews($data);
    }

    public function delete($id) {

        $news = new News($this->db);
        return $news->deleteNews($id);
    }

    public function getElement($id)
    {
        $news = new News($this->db);
        return $news->getElement($id);
    }
}