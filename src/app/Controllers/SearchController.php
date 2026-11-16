<?php

namespace App\Controllers;

use App\Repository\ContractRepository;
use App\Repository\DauphinRepository;
use App\Repository\ItemRepository;
use App\Repository\ProviderRepository;
use Core\Http\Request ;
use Core\Util\Arrays ;
use App\Exceptions\HttpException ;
use App\Services\HttpClientService;
use Core\Util\HealthCheck as health;

use function PHPSTORM_META\type;

class SearchController extends Controller
{
    private  $providerRepository;
    private  $contractRepository;
    private  $dauphinRepository;
    private  $itemRepository;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->providerRepository = new ProviderRepository();
        $this->contractRepository = new ContractRepository();
        $this->dauphinRepository = new DauphinRepository();
        $this->itemRepository = new ItemRepository();
    }



    public function search()
    {
        $options = Request::input();

        // Certaines recherches larges (ex. "bouygues" via HLK V2 supplier_mdm)
        // renvoient de gros volumes. 256 MB laisse une marge sur le 128 MB
        // par défaut ; le cap dur reste dans HttpClientService::sendViaStream.
        @ini_set('memory_limit', '256M');

        // Résilience : chaque source HLK est appelée dans son propre try/catch.
        // Une source qui échoue n'annule plus les autres — le client reçoit ce
        // qui a fonctionné + un log server-side pour investigation.
        $providers = [];
        $contracts = [];
        $dauphins  = [];
        $items     = [];
        $sourcesFailed = 0;
        $sourcesTotal  = 4;

        try {
            $data = $this->providerRepository->serachMdmProviders($options);
            foreach ($data as $p) {
                if ($p['supplier_business_unit_source_name'] === 'edf_pgi') {
                    $providers[] = [
                        'code' => $p['supplier_business_unit_app_id'],
                        'label' => $p['supplier_business_legal_registration_name'],
                        'other' => 'SIRET: ' . $p['supplier_business_unit_registration_id'] . ', Adresse: ' . $p['supplier_business_unit_address_street']
                         .' '. $p['supplier_business_unit_address_postal_zone'].' '. $p['supplier_business_unit_address_town']
                    ];
                }
            }
        } catch (\Throwable $ex) {
            $sourcesFailed++;
            error_log('[search] provider FAIL : ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
        }

        try {
            $rawContracts = $this->contractRepository->searchFromApi($options);
            $contracts = Arrays::filterByValue($rawContracts ?? [], 'code', $options['system'] ?? '');
        } catch (\Throwable $ex) {
            $sourcesFailed++;
            error_log('[search] contract FAIL : ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
        }

        try {
            $dauphins = $this->dauphinRepository->searchFromApi($options) ?? [];
        } catch (\Throwable $ex) {
            $sourcesFailed++;
            error_log('[search] dauphin FAIL : ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
        }

        try {
            if (self::isCodeItem($options['words'] ?? '')) {
                $hlk = $GLOBALS['configuration']['hlk'];
                $queryData = [
                    'password' => $hlk['pwd'],
                    'action_code' => $hlk["actions"]["items"]["get"],
                    'action_info' => $options['words']
                ];
                $itemsData = HttpClientService::exec_post($hlk['url'], $queryData);
                if (is_array($itemsData) && isset($itemsData['infos'])) {
                    $items[] = [
                        'code'  => $itemsData['infos']['item_code']       ?? null,
                        'label' => $itemsData['infos']['item_label']      ?? null,
                        'other' => $itemsData['infos']['item_label_long'] ?? null,
                    ];
                }
            } else {
                $items = $this->itemRepository->searchFromApi($options) ?? [];
            }
        } catch (\Throwable $ex) {
            $sourcesFailed++;
            error_log('[search] item FAIL : ' . $ex->getMessage() . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
        }

        // Si TOUTES les sources HLK échouent → 503 (comportement historique).
        // Sinon on renvoie les résultats partiels.
        if ($sourcesFailed === $sourcesTotal) {
            return $this->hlkErrorResponse();
        }

        return $this->response([
            'provider'     => $providers,
            'contract'     => $contracts,
            'dauphin'      => $dauphins,
            'item_generic' => $items,
        ]);
    }

   static function isCodeItem(string $searchInput): ?bool {
        if (strlen($searchInput) != 10) {
            return false;
        }

        preg_match_all("/106[0-9]{7}/", $searchInput, $matches, PREG_PATTERN_ORDER);
        foreach (reset($matches) as $code) {
            if (preg_match("#\d#", $code)) {
               return true ;
            }
        }
        
        return false;
    } 
    /**
     * 
     * @return mixed
     */
    public function getAnchors():mixed
    {
       
        $options = Request::input();
        try{
        $providers = $this->providerRepository->searchAnchors($options) ;
        $result = [];
      
        // dans le cas ou l'element n'existe pas
        foreach ($providers as $provider) {
            if (isset($options['nace']) && !empty($options['nace'])) {
                if(!in_array($provider['provider_nace'], $options['nace'] ))
                {
                    continue;
                }
            }

            if (isset($options['articles']) && !empty($options['articles'])) {
                $articles = explode(',',$options['articles']);
                if (!is_array($articles) || !in_array($provider['provider_code'], $articles)) {
                    continue;
                }
            }

            if(!array_key_exists("activities", $provider)):
                $provider["activities"] = ["TP:1"]; // en dur
            endif ;

            $result[]=$provider;
        }
        return  $this
                ->response(["statut" => true,"providers" => $result]);
        
         } catch (HttpException $ex) {
             return  $this
                ->hlkErrorResponse();
        } 
    }
    
}
