<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Manager\Migrate\DaitManager;
use App\Manager\Migrate\DapiManager;
use App\Manager\Migrate\DatpaManager;
use App\Manager\Migrate\DatpbManager;
use App\Repository\Migrate\DaitRepository;
use App\Repository\Migrate\DapiRepository;
use App\Repository\Migrate\DatpRepository;
use Database\DBConnection;
use Exception;

class MigrateController extends Controller
{
    private  $daitManager;
    private  $dapiManager;
    private  $datpaManager;
    private  $datpbManager;
    private  $daitRepository;
    private  $dapiRepository;
    private  $datpRepository;

    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->daitManager = new DaitManager($db);
        $this->dapiManager = new DapiManager($db);
        $this->datpaManager = new DatpaManager($db);
        $this->datpbManager = new DatpbManager($db);

        $this->daitRepository = new DaitRepository($db);
        $this->dapiRepository = new DapiRepository($db);
        $this->datpRepository = new DatpRepository($db);
    }

    public function index()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        try {
            $type = $options['type'];
            $list = [];
            switch ($type) {
                case 'dait':
                    $list = $this->daitRepository->getList();
                    break;
                case 'dapi':
                    $list = $this->dapiRepository->getList();
                    break;
                case 'datp':
                    $list = $this->datpRepository->getList();
                    break;
                default:
                    $this->response([
                        'status' => 403,
                        'message' => 'type de contact incunnu'
                    ]);
                    exit;
                    break;
            }
            return $this->response([
                'data' => $list
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function saveData()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $type = $options['type'];
        switch ($type) {
            case 'dait':
                return $this->daitManager->saveData($options);
                break;
            case 'dapi':
                return $this->dapiManager->saveData($options);
                break;
            case 'datpa':
                return $this->datpaManager->saveData($options);
                break;
            case 'datpb':
                $this->datpbManager->saveData($options);
                break;
            default:
                return $this->response([
                    'status' => 403,
                    'message' => 'type de contact incunnu'
                ]);
                exit;
                break;
        }
    }

    public function migrate()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $type = $options['type'];
        switch ($type) {
            case 'dait':
                return $this->daitManager->migrate($options);
                break;
            case 'dapi':
                return $this->dapiManager->migrate($options);
                break;
            case 'datp':
                return $this->datpaManager->migrate($options);
                break;
            default:
                return $this->response([
                    'status' => 403,
                    'message' => 'type des données incunnu'
                ]);
                exit;
                break;
        }
    }
}
