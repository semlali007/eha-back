<?php

namespace App\Controllers\Admin\Hotline;

use App\Controllers\Controller;
use App\Manager\Hotline\HotlineEmailManager;
use App\Repository\Hotline\HotlineEmailRepository;


class HotlineEmailController extends Controller
{
    /**
     * @var HotlineEmailRepository
     */
    private  $hotlineEmailRepository;
    /**
     * @var HotlineEmailManager
     */
    private  $hotlineEmailManager;

    /**
     * HotlineEmailController constructor.
     * @param DBConnection $db
     */
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->hotlineEmailRepository = new HotlineEmailRepository();
        $this->hotlineEmailManager = new HotlineEmailManager();
    }

    /**
     *
     */
    public function index()
    {
        try {
            return  $this->response(
                [
                    'status' => true,
                    'rows' => $this->hotlineEmailRepository->getList()
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

    /**
     *
     */
    public function update()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $email = $options['email'] ?? '';
        $id = $options['id'] ?? '';
    
        if (!is_numeric($id)) {
            return  $this->response([
                'status' => false,
                'message' => 'vérifiez le paramètre id.'
            ]);
        } 
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)){
            return  $this->response([
                'status' => false,
                'message' => 'Veuillez entrer une adresse e-mail valide.'
            ]);
        }
        
        return  $this->response($this->hotlineEmailManager->update($options));
    }

    /**
     *
     */
    public function livingUpdate()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->hotlineEmailManager->livingUpdate($options));
    }
    
}
