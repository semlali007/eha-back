<?php

namespace App\Controllers;
use App\Repository\NewsRepository;
use Database\DBConnection;

class NewsController extends Controller
{
  
    private  $newsRepository;
    static $monNNI = "TVD2C8BN";
    /**
     * AreaController constructor.
     * @param DBConnection $db
     */
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->newsRepository = new NewsRepository($db);
    }

    public function getNews()
    {
        $test = $this->newsRepository->getNews();
        $result = [];
        foreach($test as $n) {
            if($this->newsRepository->checkNni($n['id'],self::$monNNI) <= 0) {
                $result[] = $n;
            };
        }
        return $this->response($result);
    }


    public function postCheck()
    {
        $resp = $this->decode(file_get_contents("php://input"));
        $response = $resp;
        foreach($resp as $n) {
            $this->newsRepository->insertUserNews($n['id'],self::$monNNI);
        }
        return  $this->response($response);
    }

   
}
