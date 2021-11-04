<?php

namespace App\Controllers\Admin\Hotline;

use App\Controllers\Controller;
use App\Manager\Hotline\HotlineGroupManager;
use App\Repository\Hotline\HotlineGroupRepository;


class HotlineGroupController extends Controller
{
    private  $hotlineGroupRepository;
    private  $hotlineGroupManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->hotlineGroupRepository = new HotlineGroupRepository();
        $this->hotlineGroupManager = new HotlineGroupManager();
    }

    public function index()
    {
        try {
            return  $this->response(
                [
                    'status' => true,
                    'rows' => $this->hotlineGroupRepository->getList()
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
        return  $this->response($this->hotlineGroupManager->update($options));
    }
}
