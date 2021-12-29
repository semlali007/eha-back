<?php

namespace App\Controllers;
use App\Manager\InformationManager;
use App\Repository\InformationRepository;
use Core\Http\Request;

class InformationController extends Controller
{
    private  $informationRepository;
    private  $informationManager;
    
    public function __construct()
    {
        $this->informationRepository = new InformationRepository();
        $this->informationManager = new InformationManager();
    }

    public function get($type)
    {
        return  $this->response($this->informationRepository->getContent($type));
    }

    public function update($id)
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->informationManager->update($id, $options));
    }

       /**
     * 
     * @return mixed
     */
    public function getList($type='home_page'):mixed {
        
        $list = $this->informationRepository->getList($type);
        return $this->response($list);
    }

        /**
     * 
     * @param type $id
     * @return type
     */
    public function getElement($id)
    {
        return $this->response($this->informationRepository->getElement($id));
    }

    /**
     * 
     * @return mixed
     */
    public function add($type):mixed {
        
        $list = $this->informationManager->add($type, Request::input());
        return $this->response($list);
    }


    /**
     * 
     * @param int $id
     * @return mixed
     */
    public function delete($id):mixed {
        
        $list = $this->informationRepository->delete($id);
        return $this->response($list);
    }

}
