<?php

namespace App\Controllers;

use App\Manager\AreaManager;
use App\Repository\AreaRepository;

/**
 * Class AreaController
 * @package App\Controllers
 */
class AreaController extends Controller
{
    /**
     * @var AreaRepository
     */
    private  $areaRepository;
    /**
     * @var AreaManager
     */
    private  $areaManager;

    /**
     * AreaController constructor.
     * 
     */
    public function __construct()
    {
        $this->areaRepository = new AreaRepository();
        $this->areaManager = new AreaManager();
    }

    /**
     * @param $word
     */
    public function autocomplete($word)
    {
        return  $this->response($this->areaRepository->autocomplete($word));
    }

    /**
     *
     */
    public function search()
    {
        $options = $this->decode(file_get_contents("php://input"));
        return  $this->response($this->areaRepository->search($options));
    }

    /**
     *
     */
    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->areaManager->create($options));
    }
}
