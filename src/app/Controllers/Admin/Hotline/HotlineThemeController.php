<?php

namespace App\Controllers\Admin\Hotline;

use App\Controllers\Controller;
use App\Manager\Hotline\HotlineThemeManager;
use App\Repository\Hotline\HotlineThemeRepository;


class HotlineThemeController extends Controller
{
    private  $hotlineThemeRepository;
    private  $hotlineThemeManager;
    public function __construct($roles = [])
    {
        parent::__construct( $roles);
        $this->hotlineThemeRepository = new HotlineThemeRepository();
        $this->hotlineThemeManager = new HotlineThemeManager();
    }

    public function index()
    {
        try {
            return  $this->response(
                [
                    'status' => true,
                    'rows' => $this->hotlineThemeRepository->getList()
                ]
            );
        } catch (\Throwable $th) {
            return  $this->response(
                [
                    'status' => false,
                ]
            );
        }
    }

    public function update()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->hotlineThemeManager->update($options));
    }

    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->hotlineThemeManager->add($options));
    }

    public function edit($id)
    {
        return $this->response($this->hotlineThemeRepository->get($id));
    }
    public function delete($id)
    {
        return $this->response($this->hotlineThemeRepository->delete($id));
    }
}
