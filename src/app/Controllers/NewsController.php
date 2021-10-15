<?php

namespace App\Controllers;
use App\Repository\NewsRepository;
use Database\DBConnection;
use Core\Http\Request ;

class NewsController extends Controller
{
  
    private  $newsRepository;
    private $currentNni  ;
    /**
     * AreaController constructor.
     * @param DBConnection $db
     */
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
       
        $this->currentNni = $this->getCurrentUser()->getNni() ;
        $this->newsRepository = new NewsRepository($db);
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
    public function postCheck()
    {
        $response = Request::input();
        foreach($response as $n) {
            $this->newsRepository->insertUserNews($n['id'],$this->currentNni);
        }
        return  $this->response($response);
    }

   
}
