<?php

namespace App\Controllers;
use App\Manager\PageManager;
use App\Repository\PageRepository;

class PageController extends Controller
{
    private  $pageRepository;
    private  $pageManager;
    
    public function __construct()
    {
        $this->pageRepository = new PageRepository();
        $this->pageManager = new PageManager();
    }

    public function get($page)
    {
        return  $this->response($this->pageRepository->getContent($page));
    }

    public function update($page)
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->pageManager->update($page,$options));
    }
}
