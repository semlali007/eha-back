<?php

namespace App\Repository;

use App\Models\NewsCast;
use App\Models\NewsCastCategory;

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
}