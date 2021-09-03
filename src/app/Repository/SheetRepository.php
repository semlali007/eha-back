<?php

namespace App\Repository;

use App\Models\Committee;
use App\Models\Sheet;
use App\Models\SheetArea;
use App\Models\SheetBeneficiary;
use App\Models\SheetContract;
use App\Models\SheetFile;
use App\Models\SheetGroup;
use App\Models\SheetLot;
use App\Models\SheetOption;
use App\Models\SheetProvider;
use Exception;

class SheetRepository extends AbstractRepository
{
    public function search($options)
    {
        $sheet = new Sheet($this->db);
        $sheet->initSearchCondition($options);
        return
            [
                'sheets' => $sheet->search($options),
                'total' => $sheet->getCount(),
                'filter' => [
                    'edf_open' => $sheet->groupedByOpenEdf(),
                    'showcase' => $sheet->groupedByShowcase(),
                    'status' => $sheet->groupedByStatus(),
                    'partnership' => $sheet->groupedByPartnership(),
                    'protected_sector' => $sheet->groupedBySector(),
                    'dauphin' => $sheet->groupedByDauphin(),
                    'beneficiaries' => $sheet->groupedByFiliale(),
                    'areas' => $sheet->groupedByZone(),
                    'providers' => $sheet->groupedByProvider(),
                    'directions' => $sheet->groupedByDirections(),
                ],
            ];
    }

    public function getDetails($id)
    {
        try {
            $details = (new Sheet($this->db))->get($id);

            $committee = new Committee($this->db);
            $committetSheet = $committee->getByCode($details['committee']);
            if (!is_array($committetSheet)) {
                $committetSheet = [];
            }

            $details['committee'] = $committetSheet;
            $sheetContract = new SheetContract($this->db);
            $details['contracts'] = $sheetContract->getBySheet($id);
            $sheetFiliale = new SheetBeneficiary($this->db);
            $details['filiales'] = $sheetFiliale->getBySheet($id);
            $sheetProvider = new SheetProvider($this->db);
            $details['providers'] = $sheetProvider->getBySheet($id);
            $sheetZone = new SheetArea($this->db);
            $details['zones'] = $sheetZone->getBySheet($id);
            $sheetGroup = new SheetGroup($this->db);
            $details['groups'] = $sheetGroup->getBySheet($id);
            $sheetLot = new SheetLot($this->db);
            $details['lots'] = $sheetLot->getBySheet($id);
            foreach ($details['contracts'] as $k => $c) {
                $details['contracts'][$k]['lots'] = $sheetLot->getByContract($id, $c['contract_code']);
            }

            $sheetFile = new SheetFile($this->db);
            $documents = $sheetFile->getBySheet($id);
            $details['documents'] = $documents;

            $sheetOption = new SheetOption($this->db);
            $details['options'] = $sheetOption->getBySheet($id);

            return $details;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }


    public function export($options)
    {
        $status = [0 => 'En attente', 1 => 'Publiée', '-1' => 'Archivée'];
        $sheet = new Sheet($this->db);
        $answer = [];
        $sheetProvider = new SheetProvider($this->db);
        $sheetFiliale = new SheetBeneficiary($this->db);
        $sheetZone = new SheetArea($this->db);
        $sheetGroup = new SheetGroup($this->db);
        $committee = new Committee($this->db);
        $sheetContract = new SheetContract($this->db);
        $sheet->initSearchCondition($options);
        $result =  $sheet->searchWithoutPagination($options);
        $header = [
            'Id',
            'Titre',
            'Statut',
            'Commentaires',
            'Date de début',
            'Date de fin',
            'Approvisionnement sous dauphin',
            'Marché ouvert au personnel EDF',
            'Stipulation pour autrui',
            'Partenariat de productivité',
            'Secteur protégé',
            'Comité de Gestion de la Demande',
            'Fournisseurs',
            'Bénéficiaires',
            'Zones',
            'DOA',
            'Contrats'
        ];
        $csv = [
            'header' => $header,
            'data' => []
        ];

        foreach ($result['sheets'] as $sheet) {
            if (isset($sheet['committee'])) {
                $committeeItem =  $committee->getByCode($sheet['committee']);
                if (!is_null($committeeItem) && is_array($committeeItem) && isset($committeeItem['label'])) {
                    $sheet['committee'] = $committeeItem['label'];
                }
            }

            $providers = $sheetProvider->getBySheet($sheet['id']);
            $sheet['providers'] = '';
            foreach ($providers as $key => $provider) {
                if ($key !== 0) {
                    $sheet['providers'] .= '|';
                }

                $sheet['providers'] .=  $provider['name'];
            }

            $filiales = $sheetFiliale->getBySheet($sheet['id']);
            $sheet['filiales'] = '';
            foreach ($filiales as $key => $filiale) {
                if ($key !== 0) {
                    $sheet['filiales'] .= '|';
                }

                $sheet['filiales'] .= $filiale['name'];
            }

            $zones = $sheetZone->getBySheet($sheet['id']);
            $sheet['zones'] = '';
            foreach ($zones as $key => $zone) {
                if ($key !== 0) {
                    $sheet['zones'] .= '|';
                }
                $sheet['zones'] .= $zone['name'];
            }

            $groups = $sheetGroup->getBySheet($sheet['id']);
            $sheet['groups'] = '';
            foreach ($groups as $key => $group) {
                if ($key !== 0) {
                    $sheet['groups'] .= '|';
                }
                $sheet['groups'] .= $group['name'];
            }

            $contracts = $sheetContract->getBySheet($sheet['id']);
            $sheet['contracts'] = '';
            foreach ($contracts as $key => $contract) {
                if ($key !== 0) {
                    $sheet['contracts'] .= '|';
                }
                $sheet['contracts'] .= $contract['contract_code'];
            }

            $sheet['dauphin'] = ($sheet['dauphin'] == 1) ? 'Oui' : 'Non';
            $sheet['edf_open'] = ($sheet['edf_open'] == 1) ? 'Oui' : 'Non';
            $sheet['stipulation_for_others'] = ($sheet['stipulation_for_others'] == 1) ? 'Oui' : 'Non';
            $sheet['protected_sector'] = ($sheet['protected_sector'] == 1) ? 'Oui' : 'Non';
            $sheet['partnership'] = ($sheet['partnership'] == 1) ? 'Oui' : 'Non';
            $sheet['status'] = $status[$sheet['status']];
            $csv['data'][] = $sheet;
        }
        
        return  $csv;
    }
}
