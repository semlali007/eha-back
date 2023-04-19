<?php

namespace App\Controllers;

use App\Manager\NewsCastManager;
use App\Repository\NewsCastRepository;

/**
 * Class AreaController
 * @package App\Controllers
 */
class NewsCastController extends Controller
{

    private  $newsRepository;

    private  $newsCastManager;

    /**
     * AreaController constructor.
     * 
     */
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->newsRepository = new NewsCastRepository();
        $this->newsCastManager = new NewsCastManager();
    }
    /**
     *
     */
    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->newsCastManager->create($options));
    }

    public function list()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->newsRepository->list($options));
    }

    public function getCategories()
    {
        return  $this->response($this->newsRepository->getCategories());
    }

    public function create()
    {
        $options = $this->decode(file_get_contents("php://input"));
        $options['created_date'] =  date('Y-m-d H:i:s');
        return  $this->response($this->newsRepository->create($options));
    }

    public function update()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->newsRepository->update($options));
    }

    public function getNews($id){
        return  $this->response($this->newsRepository->getNews($id));
    }

    public function sort() {
        $options = $this->decode(file_get_contents("php://input"));
        return $this->response($this->newsRepository->updateHomePage($options));
    }

    public function delete() {
        $options = $this->decode(file_get_contents("php://input"));
        return $this->response($this->newsRepository->delete($options));
    }

    public function frontList()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->newsRepository->frontList($options));
    }

}
