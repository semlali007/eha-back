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
   public function __construct($roles = []) {
        parent::__construct($roles);
        $this->currentNni = $this->getCurrentUser()->getNni();
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
            }
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

   /**
     * 
     * @return mixed
     */
    public function getList():mixed {
        
        $list = $this->newsRepository->getList();
        return $this->response($list);
    }

     /**
     * 
     * @return mixed
     */
    public function add():mixed {
        
        $list = $this->newsRepository->add(Request::input());
        return $this->response($list);
    }
    
    /**
     * 
     * @return mixed
     */
    public function update():mixed {
        
        $list = $this->newsRepository->update(Request::input());
        return $this->response($list);
    }

    /**
     * 
     * @param int $id
     * @return mixed
     */
    public function delete($id):mixed {
        
        $list = $this->newsRepository->delete($id);
        return $this->response($list);
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public function getElement($id)
    {
        return $this->response($this->newsRepository->getElement($id));
    }

}
