<?php

namespace App\Controllers;

use App\Manager\SheetManager;
use App\Models\SearchLog;
use App\Models\SearchLogItem;
use App\Repository\SheetRepository;
use App\Services\FileService;
use App\Services\ApiSheetValidator;
use App\Repository\NotificationsRepository ;
use App\Security\User;
use Core\Http\Request;
use Core\Util\Arrays;
use Exception;

class SheetController extends Controller {

    private $sheetRepository;
    private $sheetManager;
    private $notificationsRepository;
    private $currentNni  ;

    public function __construct($roles = [])
    {
        parent::__construct($roles);

        $this->sheetRepository = new SheetRepository();
        $this->sheetManager = new SheetManager();
        $this->notificationsRepository = new NotificationsRepository();
    }

    public function index()
    {
        $this->currentNni = $this->getCurrentUser()->getNni();
        $options = $this->decode(file_get_contents("php://input"));
        if (isset($options['search']['searchText']) 
                && !empty($options['search']['searchText']) 
                && isset($options['is_search']) 
                &&  $options['is_search'] == true
                ) {
            $log = new SearchLog();
            $idSearch = $log->add($options);
            $list =  Arrays::getWords($options['search']['searchText']);
            foreach ($list as $w) {
                $logItem = new SearchLogItem();
                $logItem->add(['word' => $w, 'id_search' => $idSearch]);
            }
        }

        $user = $this->getCurrentUser();
        if (!$user->hasRole(['EHA_ADMIN', 'EHA_GESTIONNAIRE'])) {
            $options['search']['status'] = ['1'];
        }
        return $this->response($this->sheetRepository->search($options, $this->currentNni));
    }

    public function show(int $id) {
        $this->currentNni = $this->getCurrentUser()->getNni();
        return $this->response(['sheet' => $this->sheetRepository->getDetails($id, $this->currentNni)]);
    }

    public function history() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return $this->response(['history' => $this->sheetRepository->getHistory($options)]);
    }

    public function export() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return $this->response(['sheets' => $this->sheetRepository->export($options)], $this->currentUser->getNni());
    }

    public function update() {
        $sheetData = json_decode($_POST['model'], true);
        
        $this->notificationsRepository->insertNotification($sheetData['id']);
        return $this->sheetManager->update($this->currentUser,$sheetData);
    }

    public function updateDraft() {
        $sheetData = json_decode($_POST['model'], true);
        $this->notificationsRepository->insertNotification($sheetData['id']);
        $sheetData['draft'] = true;
        return $this->sheetManager->update($this->currentUser,$sheetData);
    }

    public function addViaCockpit()
    {
        try {
            // Récupération et validation du JSON
            $jsonInput = file_get_contents('php://input');
            $data = json_decode($jsonInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'JSON invalide: ' . json_last_error_msg()]);
                return;
            }

            // Validation du mot de passe Cockpit
            $expectedPassword = getenv('COCKPIT_API_PASS');
            if (!isset($data['cockpit_password']) || $data['cockpit_password'] !== $expectedPassword) {
                http_response_code(401);
                echo json_encode(['error' => 'Mot de passe Cockpit incorrect']);
                return;
            }

            // Validation des données avec ApiSheetValidator
            $validator = new ApiSheetValidator();
            $validationResult = $validator->validate($data);
            if (!$validationResult['valid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Données invalides',
                    'validation_errors' => $validationResult['errors'],
                    'help' => [
                        'categories' => 'GET /api/categories',
                        'committees' => 'GET /api/committees',
                        'providers' => 'GET /api/providers',
                        'beneficiaries' => 'GET /api/beneficiaries',
                        'stipulations' => 'GET /api/stipulations',
                        'areas' => 'GET /api/areas'
                    ]
                ]);
                return;
            }

            // Transformation du JSON pour compatibilité avec la fonction add()
            $transformedData = $this->transformJsonForAdd($data);

            // Création de la fiche via SheetManager
            $sheetManager = new SheetManager();
            
            // Pour les appels API externes, on crée un utilisateur fictif
            $user = new User();
            $user->setNni($data['user_nni'] ?? 'COCKPIT_API_USER');
            
            $result = $sheetManager->add($user, $transformedData);
            
            // Simplifier la réponse pour l'API externe
            $resultData = json_decode($result, true);
            if (isset($resultData['id'])) {
                // Succès - réponse simplifiée
                echo json_encode([
                    'success' => true,
                    'id' => $resultData['id']
                ]);
            } else {
                // Erreur - garder la réponse complète
                echo $result;
            }

        } catch (Exception $e) {
            error_log("Erreur addViaCockpit: " . $e->getMessage());
            http_response_code(500);
            
            echo json_encode([
                'status' => 500,
                'error' => $e->getMessage(),
                'warning' => []
            ]);
        }
    }

    /**
     * Transforme le JSON d'API externe pour être compatible avec SheetManager::add()
     */
    private function transformJsonForAdd($apiData)
    {
        $transformedData = [];

        // Champs directs
        $directFields = [
            'label', 'description', 'words', 'start_date', 'end_date', 
            'status', 'dauphin', 'edf_open', 'showcase', 'committee', 
            'category', 'stipulation_for_others', 'partnership', 
            'protected_sector', 'contrat_groupe', 'mise_a_jour_donnee',
            'supplier_vat_number', 'contract_manager',
            'entites_beneficiaires_edf_enedis', 
            'geographic_detail', 'acheteur', 'external_api_call',
            'user_nni', 'user_full_name'
        ];

        foreach ($directFields as $field) {
            if (isset($apiData[$field])) {
                $transformedData[$field] = $apiData[$field];
            }
        }

        // Champs avec valeurs par défaut requises par add()
        $transformedData['filiales'] = $apiData['filiales']; // Obligatoire - pas de valeur par défaut
        $transformedData['filiales0'] = $apiData['filiales0']; // Obligatoire - pas de valeur par défaut
        $transformedData['providers'] = $apiData['providers']; // Obligatoire - pas de valeur par défaut
        $transformedData['zones'] = $apiData['zones'];
        $transformedData['category'] = $apiData['category']; // Obligatoire - pas de valeur par défaut
        $transformedData['partnership'] = $apiData['partnership']; // Obligatoire - pas de valeur par défaut
        $transformedData['status'] = $apiData['status']; // Obligatoire - pas de valeur par défaut
        $transformedData['contracts'] = $apiData['contracts'] ?? [];
        $transformedData['lots'] = $apiData['lots'] ?? [];
        $transformedData['options'] = $apiData['options'] ?? [];

        // Gestion spéciale du champ mise_a_jour_donnee - conversion booléen vers string
        if (isset($apiData['mise_a_jour_donnee'])) {
            if (is_bool($apiData['mise_a_jour_donnee'])) {
                $transformedData['mise_a_jour_donnee'] = $apiData['mise_a_jour_donnee'] ? 'oui' : 'non';
            } else {
                // Validation des valeurs acceptées
                $validValues = ['oui', 'en_cours', 'non'];
                $value = strtolower($apiData['mise_a_jour_donnee']);
                $transformedData['mise_a_jour_donnee'] = in_array($value, $validValues) ? $value : 'non';
            }
        }

        // Passer les fichiers kits tels quels - ils seront traités dans overrideUserFieldsFromJson
        if (isset($apiData['kits']) && is_array($apiData['kits'])) {
            $transformedData['kits'] = $apiData['kits'];
        }
        
        // Compatibilité avec l'ancien format 'files' (si encore utilisé)
        if (isset($apiData['files']) && is_array($apiData['files'])) {
            $transformedData['kits'] = $apiData['files'];
        }

        // Gestion du champ draft (optionnel - le status est maintenant obligatoire)
        if (isset($apiData['draft'])) {
            $transformedData['draft'] = $apiData['draft'];
            // Note: le status est maintenant obligatoire et fourni directement via l'api
        }

        return $transformedData;
    }

    public function add() {
        $sheetData = json_decode($_POST['model'], true);
        
        return $this->sheetManager->add($this->currentUser, $sheetData);
    }

    public function addDraft() {
        $sheetData = json_decode($_POST['model'], true);
        $sheetData['draft'] = true;
        return $this->sheetManager->add($this->currentUser,$sheetData);
    }

    public function download() {
        $options = $this->decode(file_get_contents("php://input"));
        return FileService::download($options);
    }
    

    public function subscribe() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $this->currentNni = $this->getCurrentUser()->getNni();
        if($this->currentNni!=$options['user']){
            var_dump('problème');
        }

        return $this->response(['sheet' => $this->sheetManager->subscribe($options['sheet'],$options['user'])]);
    }

    public function unsubscribe() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $this->currentNni = $this->getCurrentUser()->getNni();
        if($this->currentNni!=$options['user']){
            var_dump('problème');
        }

        return $this->response(['sheet' => $this->sheetManager->unsubscribe($options['sheet'],$options['user'])]);
    }


    /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    public function remove(int $id) {
        return $this->response($this->sheetManager->remove($id));
    }


    public function filter()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        switch ($options['type']) {
            case 'beneficiary':
                return $this->response($this->sheetRepository->getSheetByBeneficiary($options['code']));
                break;
            case 'stipulation':
                return $this->response($this->sheetRepository->getSheetByStipulation($options['code']));
                break;
            case 'provider':
                return $this->response($this->sheetRepository->getSheetByProvider($options['code']));
                break;
            default:
            //
                break;

        }
        
    }

}
