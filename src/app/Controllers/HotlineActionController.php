<?php

namespace App\Controllers;

use App\Exceptions\EmailException;
use App\Exceptions\InvalidParameterException;
use App\Manager\Hotline\HotlineActionManager;
use App\Manager\Hotline\HotlineManager;
use App\Repository\Hotline\HotlineRepository;
use App\Services\StaticTextService;
use App\Validation\Validator;
use Exception;

class HotlineActionController extends Controller
{
    private  $hotlineRepository;
    private  $hotlineManager;
    private  $hotlineActionManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->hotlineRepository = new HotlineRepository();
        $this->hotlineManager = new HotlineManager();
        $this->hotlineActionManager = new HotlineActionManager();
    }

    public function forward()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            return $this->response($this->hotlineManager->forward($options));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function post()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            return $this->response($this->hotlineManager->post($options));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function upload()
    {
        try {

            $answer = array(
                'status'  =>  false,
                'message' => '',
            );
    
            $validator = new Validator($_POST);
            $errors = $validator->validate([
                'id' => ['required'],
                'sha' => ['required'],
                'name' => ['name'],
            ]);
    
            if ($errors) {
                throw (new InvalidParameterException())->setErrors($errors);
            }
    
            if (empty($_FILES['file'])) {
                $answer['message'] = '<p>' . StaticTextService::$_HOTLINE_TEXT['02'] . '</p>';
                return $this->response($answer);
            }

            return $this->response($this->hotlineActionManager->upload($_POST, $_FILES['file']));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function close()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            $currentUser = $this->currentUser;
            return $this->response($this->hotlineActionManager->close($options, $currentUser));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function dump()
    {
        return  $this->response([
            'status' => true,
            'message' => 'Fonctionnalité en attente'
        ]);
    }

    public function recovery()
    {
        return  $this->response([
            'status' => true,
            'message' => 'Fonctionnalité en attente'
        ]);
    }
    
}
