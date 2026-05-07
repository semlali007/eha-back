<?php

namespace App\Controllers;

use App\Manager\SheetManager;
use App\Models\SearchLog;
use App\Models\SearchLogItem;
use App\Repository\SheetRepository;
use App\Services\FileService;
use App\Services\ApiSheetValidator;
use App\Services\CockpitJwtService;
use App\Services\AuthService;
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
        $sheet = $this->sheetRepository->getDetails($id, $this->currentNni);
        
        // Vérifier les droits d'accès selon le statut de la fiche
        $user = $this->getCurrentUser();
        if (isset($sheet['status']) && ($sheet['status'] == 0 || $sheet['status'] == -1)) {
            // Les fiches en brouillon (0) ou archivées (-1) ne sont visibles que par ADMIN et GESTIONNAIRE
            if (!$user->hasRole(['EHA_ADMIN', 'EHA_GESTIONNAIRE'])) {
                return $this->response(['error' => 'Accès non autorisé à cette fiche'], 403);
            }
        }
        
        return $this->response(['sheet' => $sheet]);
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

    /**
     * Authentification pour obtenir un JWT token
     * Route: POST /cockpit/auth/login
     */
    public function login()
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

            // Validation des champs requis
            if (!isset($data['username']) || !isset($data['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Username et password requis']);
                return;
            }

            // Authentification
            $authService = new AuthService();
            if (!$authService->authenticate($data['username'], $data['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Identifiants incorrects']);
                return;
            }

            // Récupération des infos utilisateur
            $userInfo = $authService->getUserInfo($data['username']);
            if (!$userInfo) {
                http_response_code(500);
                echo json_encode(['error' => 'Erreur lors de la récupération des informations utilisateur']);
                return;
            }

            // Génération du token JWT
            $jwtService = new CockpitJwtService();
            $token = $jwtService->generateToken(
                $userInfo['user_id'],
                $userInfo['user_nni'],
                $userInfo['user_full_name']
            );

            // Réponse avec le token
            echo json_encode([
                'success' => true,
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 3600, // 1 heure
                'user_info' => [
                    'username' => $userInfo['username'],
                    'user_nni' => $userInfo['user_nni'],
                    'user_full_name' => $userInfo['user_full_name']
                ]
            ]);

        } catch (Exception $e) {
            error_log("Erreur login: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Erreur interne du serveur']);
        }
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

            // Validation du token JWT
            $jwtService = new CockpitJwtService();
            $token = $jwtService->extractTokenFromHeader();
            
            if (!$token) {
                http_response_code(401);
                echo json_encode(['error' => 'Token manquant. Utilisez Authorization: Bearer <token>']);
                return;
            }

            $tokenPayload = $jwtService->validateToken($token);
            if (!$tokenPayload) {
                http_response_code(401);
                echo json_encode(['error' => 'Token invalide ou expiré']);
                return;
            }

            // Récupérer les informations utilisateur depuis le token
            $userNni = $tokenPayload['user_nni'] ?? null;
            $userFullName = $tokenPayload['user_full_name'] ?? null;

            // Validation et transformation des données avec ApiSheetValidator
            $validator = new ApiSheetValidator();
            $validationResult = $validator->transformAndValidate($data);
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

            // Utiliser les données transformées (booléens normalisés)
            $transformedData = $this->transformJsonForAdd($validationResult['data']);

            // Création de la fiche via SheetManager
            $sheetManager = new SheetManager();
            
            // Pour les appels API externes, on crée un utilisateur depuis le token
            $user = new User();
            $user->setNni($userNni ?? 'COCKPIT_API_USER');
            
            // Ajouter les informations utilisateur aux données transformées (depuis le token JWT)
            $transformedData['user_nni'] = $userNni;
            $transformedData['user_full_name'] = $userFullName;
            $transformedData['external_api_call'] = true;
            
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

        // Champs directs (user_nni et user_full_name viennent maintenant du token JWT)
        $directFields = [
            'label', 'description', 'words', 'start_date', 'end_date', 
            'status', 'dauphin', 'edf_open', 'showcase', 'committee', 
            'category', 'stipulation_for_others', 'partnership', 
            'protected_sector', 'contrat_groupe', 'mise_a_jour_donnee',
            'supplier_vat_number', 'contract_manager',
            'entites_beneficiaires_edf_enedis', 
            'geographic_detail', 'acheteur', 'external_api_call'
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

        // Conversion des booléens vers le format attendu par la base de données
        $booleanToStringFields = [
            'dauphin', 'edf_open', 'stipulation_for_others', 
            'partnership', 'protected_sector', 'showcase'
        ];
        
        foreach ($booleanToStringFields as $field) {
            if (isset($transformedData[$field]) && is_bool($transformedData[$field])) {
                $transformedData[$field] = $transformedData[$field] ? 1 : 0;
            }
        }
        
        // Traitement spécial pour contrat_groupe (attend 'true'/'false' comme chaînes)
        if (isset($transformedData['contrat_groupe']) && is_bool($transformedData['contrat_groupe'])) {
            $transformedData['contrat_groupe'] = $transformedData['contrat_groupe'] ? 'true' : 'false';
        }

        // Passer les fichiers kits tels quels - ils seront traités dans overrideUserFieldsFromJson
        if (isset($apiData['kits']) && is_array($apiData['kits'])) {
            $transformedData['kits'] = $this->sanitizeKitsData($apiData['kits']);
        }
        
        // Compatibilité avec l'ancien format 'files' (si encore utilisé)
        if (isset($apiData['files']) && is_array($apiData['files'])) {
            $transformedData['kits'] = $this->sanitizeKitsData($apiData['files']);
        }

        // Gestion du champ draft (optionnel - le status est maintenant obligatoire)
        if (isset($apiData['draft'])) {
            $transformedData['draft'] = $apiData['draft'];
            // Note: le status est maintenant obligatoire et fourni directement via l'api
        }

        return $transformedData;
    }

    /**
     * Assainit les données kits pour bloquer les tentatives de SSRF et Path Traversal.
     * Défense en profondeur au niveau du contrôleur (point d'entrée).
     *
     * @param array $kitsData
     * @return array
     */
    private function sanitizeKitsData(array $kitsData): array
    {
        $sanitized = [];
        foreach ($kitsData as $kit) {
            if (!is_array($kit)) {
                continue;
            }

            // Assainir le nom du fichier
            if (isset($kit['name'])) {
                $kit['name'] = basename($kit['name']);
            }

            // Valider le champ path s'il existe
            if (isset($kit['path']) && !empty($kit['path'])) {
                $path = $kit['path'];

                // Bloquer les URL / wrappers PHP (SSRF)
                if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $path)) {
                    // Supprimer le path dangereux, le fichier sera ignoré en aval
                    unset($kit['path']);
                }

                // Bloquer les séquences de traversée
                if (isset($kit['path']) && strpos($kit['path'], '..') !== false) {
                    unset($kit['path']);
                }
            }

            $sanitized[] = $kit;
        }
        return $sanitized;
    }

    public function add() {
        $sheetData = json_decode($_POST['model'], true);

        if (is_array($sheetData) && isset($sheetData['kits']) && is_array($sheetData['kits'])) {
            $sheetData['kits'] = $this->sanitizeKitsData($sheetData['kits']);
        }

        return $this->sheetManager->add($this->currentUser, $sheetData);
    }

    public function addDraft() {
        $sheetData = json_decode($_POST['model'], true);

        if (is_array($sheetData) && isset($sheetData['kits']) && is_array($sheetData['kits'])) {
            $sheetData['kits'] = $this->sanitizeKitsData($sheetData['kits']);
        }

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
