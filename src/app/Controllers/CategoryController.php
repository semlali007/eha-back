<?php

namespace App\Controllers;

use App\Repository\CategoryRepository;
use Exception;

class CategoryController extends Controller
{
    private  $categoryRepository;


    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->categoryRepository = new CategoryRepository();
    }


    public function index()
    {
        try {
            return $this->response($this->categoryRepository->getList());
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
}
