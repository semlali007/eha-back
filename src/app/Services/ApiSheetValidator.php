<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Committee;
use App\Models\Provider;
use App\Models\Beneficiary;
use App\Models\Stipulation;
use App\Models\Area;
use App\Models\Group;
use App\Models\Domain;
use App\Models\SubDomain;
use Exception;

class ApiSheetValidator
{
    private $errors = [];
    
    /**
     * Valide les données JSON pour la création d'une fiche via API
     */
    public function validate($data)
    {
        $this->errors = [];
        
        // Validation des champs obligatoires
        $this->validateRequired($data);
        
        // Validation des types de données
        $this->validateTypes($data);
        
        // Validation des références en base de données
        $this->validateReferences($data);
        
        // Validation des formats
        $this->validateFormats($data);
        
        return [
            'valid' => empty($this->errors),
            'errors' => $this->errors
        ];
    }
    
    /**
     * Validation des champs obligatoires
     */
    private function validateRequired($data)
    {
        $requiredFields = [
            'cockpit_password' => 'Mot de passe API obligatoire',
            'external_api_call' => 'Flag external_api_call obligatoire',
            'user_nni' => 'NNI utilisateur obligatoire',
            'providers' => 'Le champ providers est obligatoire',
            'zones' => 'Le champ zones est obligatoire',
            'category' => 'Le champ category est obligatoire',
            'filiales' => 'Le champ filiales est obligatoire',
            'filiales0' => 'Le champ filiales0 est obligatoire',
            'partnership' => 'Le champ partnership est obligatoire',
            'status' => 'Le champ status est obligatoire'
        ];
        
        foreach ($requiredFields as $field => $message) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $this->errors[] = $message;
            }
        }
        
        // Si pas en mode draft, le titre est obligatoire
        if ((!isset($data['draft']) || $data['draft'] !== true) && 
            (!isset($data['label']) || empty(trim($data['label'])))) {
            $this->errors[] = 'Le titre (label) est obligatoire pour une fiche non-brouillon';
        }
    }
    
    /**
     * Validation des types de données
     */
    private function validateTypes($data)
    {
        // Champs booléens
        $booleanFields = ['external_api_call', 'draft', 'dauphin', 'edf_open', 'showcase', 
                         'stipulation_for_others', 'partnership', 'protected_sector', 'contrat_groupe'];
        
        foreach ($booleanFields as $field) {
            if (isset($data[$field]) && !is_bool($data[$field]) && !in_array($data[$field], [0, 1, '0', '1', 'true', 'false'])) {
                $this->errors[] = "Le champ '$field' doit être un booléen (true/false)";
            }
        }
        
        // Champs numériques
        $numericFields = ['committee', 'category', 'id_supplier_size', 'status'];
        
        foreach ($numericFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && !is_numeric($data[$field])) {
                $this->errors[] = "Le champ '$field' doit être numérique";
            }
        }
        
        // Champs dates
        $dateFields = ['start_date', 'end_date'];
        
        foreach ($dateFields as $field) {
            if (isset($data[$field]) && $data[$field] !== null && !$this->isValidDate($data[$field])) {
                $this->errors[] = "Le champ '$field' doit être une date valide au format YYYY-MM-DD";
            }
        }
    }
    
    /**
     * Validation des références en base de données
     */
    private function validateReferences($data)
    {
        // Validation category
        if (isset($data['category']) && $data['category'] !== null && $data['category'] > 0) {
            if (!$this->categoryExists($data['category'])) {
                $this->errors[] = "La catégorie {$data['category']} n'existe pas. Utilisez GET /api/categories pour voir la liste";
            }
        }
        
        // Validation committee
        if (isset($data['committee']) && $data['committee'] !== null && $data['committee'] > 0) {
            if (!$this->committeeExists($data['committee'])) {
                $this->errors[] = "Le comité {$data['committee']} n'existe pas. Utilisez GET /api/committees pour voir la liste";
            }
        }
        
        // Validation providers
        if (isset($data['providers']) && is_array($data['providers'])) {
            foreach ($data['providers'] as $index => $provider) {
                if (!isset($provider['id'])) {
                    $this->errors[] = "providers[$index]: ID manquant";
                } elseif (!$this->providerExists($provider['id'])) {
                    $this->errors[] = "Le fournisseur {$provider['id']} n'existe pas. Utilisez GET /api/providers pour voir la liste";
                }
            }
        }
        
        // Validation filiales (bénéficiaires)
        if (isset($data['filiales']) && is_array($data['filiales'])) {
            foreach ($data['filiales'] as $index => $filiale) {
                if (!isset($filiale['id'])) {
                    $this->errors[] = "filiales[$index]: ID manquant";
                } elseif (!$this->beneficiaryExists($filiale['id'])) {
                    $this->errors[] = "Le bénéficiaire {$filiale['id']} n'existe pas. Utilisez GET /api/beneficiaries pour voir la liste";
                }
            }
        }
        
        // Validation filiales0 (stipulation pour autrui)
        if (isset($data['filiales0']) && is_array($data['filiales0'])) {
            foreach ($data['filiales0'] as $index => $filiale0) {
                if (!isset($filiale0['id'])) {
                    $this->errors[] = "filiales0[$index]: ID manquant";
                } elseif (!$this->stipulationExists($filiale0['id'])) {
                    $this->errors[] = "La stipulation {$filiale0['id']} n'existe pas. Utilisez GET /api/stipulations pour voir la liste";
                }
            }
        }
        
        // Validation zones
        if (isset($data['zones']) && is_array($data['zones'])) {
            foreach ($data['zones'] as $index => $zone) {
                if (!isset($zone['id'])) {
                    $this->errors[] = "zones[$index]: ID manquant";
                } elseif (!$this->areaExists($zone['id'])) {
                    $this->errors[] = "La zone {$zone['id']} n'existe pas. Utilisez GET /api/areas pour voir la liste";
                }
            }
        }
        
/*                 // Validation groups
        if (isset($data['groups']) && is_array($data['groups'])) {
            foreach ($data['groups'] as $index => $group) {
                if (!isset($group['id'])) {
                    $this->errors[] = "groups[$index]: ID manquant";
                } elseif (!$this->groupExists($group['id'])) {
                    $this->errors[] = "Le groupe {$group['id']} n'existe pas. Utilisez GET /api/groups pour voir la liste";
                }
            }
        }
        

        // Validation domains
        if (isset($data['domains']) && is_array($data['domains'])) {
            foreach ($data['domains'] as $index => $domain) {
                if (!isset($domain['code'])) {
                    $this->errors[] = "domains[$index]: code manquant";
                } elseif (!$this->domainExists($domain['code'])) {
                    $this->errors[] = "Le domaine {$domain['code']} n'existe pas. Utilisez GET /api/domains pour voir la liste";
                }
            }
        }
        
        // Validation subdomains
        if (isset($data['subdomains']) && is_array($data['subdomains'])) {
            foreach ($data['subdomains'] as $index => $subdomain) {
                if (!isset($subdomain['code'])) {
                    $this->errors[] = "subdomains[$index]: code manquant";
                } elseif (!$this->subdomainExists($subdomain['code'])) {
                    $this->errors[] = "Le sous-domaine {$subdomain['code']} n'existe pas. Utilisez GET /api/subdomains pour voir la liste";
                }
            }
        } */
    }
    
    /**
     * Validation des formats
     */
    private function validateFormats($data)
    {
        // Validation NNI
        if (isset($data['user_nni']) && !preg_match('/^[A-Z0-9]{6}$/', $data['user_nni'])) {
            $this->errors[] = "Le NNI doit contenir exactement 6 caractères alphanumériques majuscules";
        }
        
        // Validation status
        if (isset($data['status']) && !in_array($data['status'], ['0', '1', '-1', 0, 1, -1])) {
            $this->errors[] = "Le status doit être 0 (brouillon), 1 (publié) ou -1 (archivé)";
        }
        
        // Validation VAT number
        if (isset($data['supplier_vat_number']) && !empty($data['supplier_vat_number'])) {
            if (!preg_match('/^[A-Z]{2}[0-9A-Z]{8,12}$/', $data['supplier_vat_number'])) {
                $this->errors[] = "Le numéro de TVA doit être au format européen (ex: FR12345678901)";
            }
        }
        
        // Validation contracts
        if (!isset($data['contracts']) || !is_array($data['contracts']) || empty($data['contracts'])) {
            $this->errors[] = "Au moins un contrat est obligatoire dans le tableau 'contracts'";
        } else {
            foreach ($data['contracts'] as $index => $contract) {
                if (!isset($contract['contract_code']) || empty($contract['contract_code'])) {
                    $this->errors[] = "contracts[$index]: contract_code obligatoire";
                } elseif (!preg_match('/^C/', $contract['contract_code'])) {
                    $this->errors[] = "contracts[$index]: contract_code doit commencer par 'C' (ex: C123456789)";
                }
                if (!isset($contract['provider']) || empty($contract['provider'])) {
                    $this->errors[] = "contracts[$index]: provider obligatoire";
                }
            }
        }
        
        // Validation lots
        if (isset($data['lots']) && is_array($data['lots'])) {
            foreach ($data['lots'] as $index => $lot) {
                if (!isset($lot['name']) || empty($lot['name'])) {
                    $this->errors[] = "lots[$index]: name obligatoire";
                }
            }
        }
        
        // Validation options
        if (isset($data['options']) && is_array($data['options'])) {
            foreach ($data['options'] as $index => $option) {
                if (!isset($option['startdate']) || !$this->isValidDate($option['startdate'])) {
                    $this->errors[] = "options[$index]: startdate obligatoire et doit être une date valide (YYYY-MM-DD)";
                }
                if (!isset($option['enddate']) || !$this->isValidDate($option['enddate'])) {
                    $this->errors[] = "options[$index]: enddate obligatoire et doit être une date valide (YYYY-MM-DD)";
                }
                // Vérifier que start_date < end_date
                if (isset($option['startdate']) && isset($option['enddate'])) {
                    if (strtotime($option['startdate']) >= strtotime($option['enddate'])) {
                        $this->errors[] = "options[$index]: startdate doit être antérieure à enddate";
                    }
                }
            }
        }
    }
    
    /**
     * Méthodes de vérification d'existence en base
     */
    private function categoryExists($id)
    {
        try {
            $category = new Category();
            $result = $category->getByCode($id);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function committeeExists($id)
    {
        try {
            $committee = new Committee();
            $result = $committee->getByCode($id);
            return !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function groupExists($id)
    {
        try {
            $group = new Group();
            $groups = $group->getList([]);
            
            foreach ($groups as $grp) {
                if ($grp['id'] == $id) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function providerExists($id)
    {
        try {
            $provider = new Provider();
            $providers = $provider->getList([]);
            
            foreach ($providers as $prov) {
                if ($prov['id'] == $id) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function beneficiaryExists($id)
    {
        try {
            $beneficiary = new Beneficiary();
            $beneficiaries = $beneficiary->getList([]);
            
            foreach ($beneficiaries as $ben) {
                if ($ben['id'] == $id) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function stipulationExists($id)
    {
        try {
            $stipulation = new Stipulation();
            $stipulations = $stipulation->getList([]);
            
            foreach ($stipulations as $stip) {
                if ($stip['id'] == $id) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function areaExists($id)
    {
        try {
            $area = new Area();
            $areas = $area->getList(null);
            
            foreach ($areas as $ar) {
                if (($ar['code'] ?? $ar['id']) == $id) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function domainExists($code)
    {
        try {
            $domain = new Domain();
            $domains = $domain->getList([]);
            
            foreach ($domains as $dom) {
                if ($dom['id'] == $code) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function subdomainExists($code)
    {
        try {
            $subdomain = new SubDomain();
            $subdomains = $subdomain->getList([]);
            
            foreach ($subdomains as $sub) {
                if ($sub['id'] == $code) {
                    return true;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validation de format de date
     */
    private function isValidDate($date)
    {
        if (empty($date)) return false;
        
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Retourne la liste des erreurs
     */
    public function getErrors()
    {
        return $this->errors;
    }
}