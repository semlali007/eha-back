<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Committee;
use App\Models\Provider;
use App\Models\Beneficiary;
use App\Models\Stipulation;
use App\Models\Area;
use App\Models\Group;
use App\Models\Domain;
use App\Models\SubDomain;

class ApiReferentialController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * GET /api/categories
     */
    public function getCategories()
    {
        try {
            $category = new Category();
            $categories = $category->getList();
            
            $result = [];
            foreach ($categories as $cat) {
                $result[] = [
                    'id' => $cat['code'] ?? $cat['id'],
                    'code' => $cat['code'] ?? $cat['id'],
                    'label' => $cat['label'] ?? $cat['name'],
                    'description' => $cat['description'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des catégories']);
        }
    }

    /**
     * GET /api/committees
     */
    public function getCommittees()
    {
        try {
            $committee = new Committee();
            $committees = $committee->getList();
            
            $result = [];
            foreach ($committees as $com) {
                $result[] = [
                    'id' => $com['code'] ?? $com['id'],
                    'code' => $com['code'] ?? $com['id'],
                    'label' => $com['label'] ?? $com['name'],
                    'description' => $com['description'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des comités']);
        }
    }

    /**
     * GET /api/groups
     */
    public function getGroups()
    {
        try {
            $group = new Group();
            $groups = $group->getList([]);
            
            $result = [];
            foreach ($groups as $grp) {
                $result[] = [
                    'id' => $grp['id'],
                    'name' => $grp['name'],
                    'description' => $grp['description'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des groupes']);
        }
    }

    /**
     * GET /api/providers
     */
    public function getProviders()
    {
        try {
            $provider = new Provider();
            $providers = $provider->getList([]);
            
            $result = [];
            foreach ($providers as $prov) {
                $result[] = [
                    'id' => $prov['id'],
                    'code' => $prov['id'],
                    'name' => $prov['name'],
                    'label' => $prov['name']
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des fournisseurs']);
        }
    }

    /**
     * GET /api/beneficiaries
     */
    public function getBeneficiaries()
    {
        try {
            $beneficiary = new Beneficiary();
            $beneficiaries = $beneficiary->getList([]);
            
            $result = [];
            foreach ($beneficiaries as $ben) {
                $result[] = [
                    'id' => $ben['id'],
                    'name' => $ben['name'],
                    'description' => $ben['description'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des bénéficiaires']);
        }
    }

    /**
     * GET /api/stipulations
     */
    public function getStipulations()
    {
        try {
            $stipulation = new Stipulation();
            $stipulations = $stipulation->getList([]);
            
            $result = [];
            foreach ($stipulations as $stip) {
                $result[] = [
                    'id' => $stip['id'],
                    'name' => $stip['name'],
                    'description' => $stip['description'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des stipulations']);
        }
    }

    /**
     * GET /api/areas
     */
    public function getAreas()
    {
        try {
            $area = new Area();
            $areas = $area->getList(null);
            
            $result = [];
            foreach ($areas as $ar) {
                $result[] = [
                    'id' => $ar['code'] ?? $ar['id'],
                    'code' => $ar['code'] ?? $ar['id'],
                    'label' => $ar['label'] ?? $ar['name'],
                    'description' => $ar['description'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des zones']);
        }
    }

    /**
     * GET /api/domains
     */
    public function getDomains()
    {
        try {
            $domain = new Domain();
            $domains = $domain->getList([]);
            
            $result = [];
            foreach ($domains as $dom) {
                $result[] = [
                    'id' => $dom['id'],
                    'code' => $dom['id'],
                    'name' => $dom['name'],
                    'type' => $dom['type'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des domaines']);
        }
    }

    /**
     * GET /api/subdomains
     */
    public function getSubdomains()
    {
        try {
            $subdomain = new SubDomain();
            $subdomains = $subdomain->getList([]);
            
            $result = [];
            foreach ($subdomains as $sub) {
                $result[] = [
                    'id' => $sub['id'],
                    'code' => $sub['id'],
                    'name' => $sub['name'],
                    'type' => $sub['type'] ?? null
                ];
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result,
                'count' => count($result)
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des sous-domaines']);
        }
    }

    /**
     * GET /api/referentials - Endpoint consolidé qui retourne tous les référentiels
     */
    public function getAllReferentials()
    {
        try {
            $result = [
                'categories' => [],
                'committees' => [],
                'providers' => [],
                'beneficiaries' => [],
                'stipulations' => [],
                'areas' => [],
                //'groups' => [],
                //'domains' => [],
                //'subdomains' => []
            ];

            // Categories
            $category = new Category();
            $categories = $category->getList();
            foreach ($categories as $cat) {
                $result['categories'][] = [
                    'id' => $cat['code'] ?? $cat['id'],
                    'label' => $cat['label'] ?? $cat['name']
                ];
            }

            // Committees  
            $committee = new Committee();
            $committees = $committee->getList();
            foreach ($committees as $com) {
                $result['committees'][] = [
                    'id' => $com['code'] ?? $com['id'],
                    'label' => $com['label'] ?? $com['name']
                ];
            }

/*             // Groups
            $group = new Group();
            $groups = $group->getList([]);
            foreach ($groups as $grp) {
                $result['groups'][] = [
                    'id' => $grp['id'],
                    'name' => $grp['name']
                ];
            } */

            // Providers
            $provider = new Provider();
            $providers = $provider->getList([]);
            foreach ($providers as $prov) {
                $result['providers'][] = [
                    'id' => $prov['id'],
                    'name' => $prov['name']
                ];
            }

            // Beneficiaries
            $beneficiary = new Beneficiary();
            $beneficiaries = $beneficiary->getList([]);
            foreach ($beneficiaries as $ben) {
                $result['beneficiaries'][] = [
                    'id' => $ben['id'],
                    'name' => $ben['name']
                ];
            }

            // Stipulations
            $stipulation = new Stipulation();
            $stipulations = $stipulation->getList([]);
            foreach ($stipulations as $stip) {
                $result['stipulations'][] = [
                    'id' => $stip['id'],
                    'name' => $stip['name']
                ];
            }

            // Areas
            $area = new Area();
            $areas = $area->getList(null);
            foreach ($areas as $ar) {
                $result['areas'][] = [
                    'id' => $ar['code'] ?? $ar['id'],
                    'name' => $ar['label'] ?? $ar['name']
                ];
            }

/*             // Domains
            $domain = new Domain();
            $domains = $domain->getList([]);
            foreach ($domains as $dom) {
                $result['domains'][] = [
                    'id' => $dom['id'],
                    'name' => $dom['name'],
                    'type' => $dom['type'] ?? null
                ];
            }

            // Subdomains
            $subdomain = new SubDomain();
            $subdomains = $subdomain->getList([]);
            foreach ($subdomains as $sub) {
                $result['subdomains'][] = [
                    'id' => $sub['id'],
                    'name' => $sub['name'],
                    'type' => $sub['type'] ?? null
                ];
            } */
            
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Erreur lors de la récupération des référentiels']);
        }
    }
}