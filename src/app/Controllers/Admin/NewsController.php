<?php

namespace App\Controllers\Admin;

use App\Repository\NewsRepository;
use Core\Http\Request ;
use App\Controllers\Controller;

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
    public function getNews($type):mixed {
        
        $test = $this->newsRepository->getNews($type);
        $result = [];
        foreach ($test as $n) {
            if ($this->newsRepository->checkNni($n['id'], $this->currentNni, $type) <= 0) {
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
        foreach($response['items'] as $n) {
            $this->newsRepository->insertUserNews($n['id'],$this->currentNni, $response['type']);
        }
        return  $this->response($response);
    }

   /**
     * 
     * @return mixed
     */
    public function getList($type):mixed {
        
        $list = $this->newsRepository->getList($type);
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
