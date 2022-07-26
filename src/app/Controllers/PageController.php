<?php

namespace App\Controllers;
use App\Manager\PageManager;
use App\Repository\PageRepository;
use App\Repository\NewsRepository;

class PageController extends Controller
{
    private  $newsRepository;
    private  $pageRepository;
    private  $pageManager;
    private $currentNni  ;

    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->currentNni = $this->getCurrentUser()->getNni();
        $this->newsRepository = new NewsRepository();
        $this->pageRepository = new PageRepository();
        $this->pageManager = new PageManager();
    }

    public function get($page)
    {
        $data = $this->pageRepository->getContent($page);

        if($page == 'home_page' && is_array($data)){
            $data['news'] = $this->newsRepository->getNewsCountOfPage($page, $this->currentNni);
        }

        return  $this->response($data);
    }

    public function update($page)
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->pageManager->update($page,$options));
    }
}
