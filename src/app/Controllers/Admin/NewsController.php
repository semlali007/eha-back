<?php

namespace App\Controllers\Admin;
use App\Repository\NewsRepository;
use Database\DBConnection;
use Core\Http\Request ;
use App\Controllers\Controller;


class NewsController extends Controller
{
  
    private  $newsRepository;
    /**
     * AreaController constructor.
     * @param DBConnection $db
     */
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->newsRepository = new NewsRepository($db);
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

    public function update():mixed {
        
        $list = $this->newsRepository->update(Request::input());
        return $this->response($list);
    }

    public function delete($id):mixed {
        
        $list = $this->newsRepository->delete($id);
        return $this->response($list);
    }

    public function getElement($id)
    {
        return $this->response($this->newsRepository->getElement($id));
    }
   
}
