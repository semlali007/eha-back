<?php

namespace App\Controllers;

use App\Repository\NewsRepository;
use Core\Http\Request ;

class NewsController extends Controller
{
  
    private  $newsRepository;
    private $currentNni  ;
    /**
     * AreaController constructor.
     * 
     */
    public function __construct()
    {
        $this->currentNni = $this->getCurrentUser()->getNni() ;
        $this->newsRepository = new NewsRepository();
    }

    /**
     * 
     * @return mixed
     */
    public function getNews():mixed {
        
        $test = $this->newsRepository->getNews();
        $result = [];
        foreach ($test as $n) {
            if ($this->newsRepository->checkNni($n['id'], $this->currentNni) <= 0) {
                $result[] = $n;
            };
        }
        return $this->response($result);
    }

    /**
     * 
     */
    public function createUserNews()
    {
        $response = Request::input();
        foreach($response as $n) {
            $this->newsRepository->insertUserNews($n['id'],$this->currentNni);
        }
        return  $this->response($response);
    }

   
}
