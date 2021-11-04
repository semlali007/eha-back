<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;

use App\Manager\ExternalManager;
use App\Repository\ExternalRepository;
use Exception;

class ExternalController extends Controller
{
    private  $externalRepository;
    private  $externalManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->externalRepository = new ExternalRepository();
        $this->externalManager = new ExternalManager();
    }

    public function index()
    {
        try {
            return  $this->response(
                [
                    'status' => true,
                    'rows' => $this->externalRepository->getList()
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

    public function create()
    {
        try {

            $options = json_decode(file_get_contents("php://input"), true) ?? [];
            $from = (isset($options['from'])) ? $options['from'] : null;
            $to = (isset($options['to'])) ? $options['to'] : null;
            if ($from !== null && !filter_var($from, FILTER_VALIDATE_EMAIL)) {
                $from = null;
            }
            if ($from !== null && strpos($from, '-externe.') !== false) {
                $from = null;
            }
            if ($to !== null && !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                $to = null;
            }
            if ($to !== null && strpos($to, '-externe.') === false) {
                $to = null;
            }

            if ($from !== null && $to !== null) {
                $end = explode('-', date('Y-m-d'));
                $end[0] += 1;
                $end = implode('-', $end);
                $this->externalManager->create([
                    'to' => $to,
                    'from' => $from,
                    'end' => $end
                ]);

                $this->response($this->externalManager->create($options));
                return [
                    'status' => true,
                    'message' => 'email ajouté'
                ];
            } else {
                return [
                    'status' => false,
                    'message' => 'erreur'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function delete($email)
    {
        return  $this->response($this->externalManager->delete($email));
    }
}
