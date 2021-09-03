<?php

namespace App\Manager;

use App\Exceptions\SheetException;
use App\Models\Sheet;
use App\Models\SheetArea;
use App\Models\SheetBeneficiary;
use App\Models\SheetContract;
use App\Models\SheetFile;
use App\Models\SheetGroup;
use App\Models\SheetLot;
use App\Models\SheetOption;
use App\Models\SheetProvider;
use App\Services\FileUpload;
use App\Services\UserService;
use Exception;

class SheetManager extends AbstractManager
{
    public function update($sheetData)
    {
        $dirPath =  getenv("CONFIG_UPLOAD_PATH");
        try {
            $data['id'] = $sheetData['id'];
            $data['label'] = $sheetData['label'] ?? '';
            $data['description'] = $sheetData['description'] ?? '';
            $data['start_date'] = $sheetData['start_date'] ?? '';
            $data['end_date'] = $sheetData['end_date'] ?? '';
            $data['dauphin'] = $sheetData['dauphin'];
            $data['edf_open'] = $sheetData['edf_open'];
            $data['showcase'] = $sheetData['showcase'];
            $data['committee'] = $sheetData['committee'] ?? 0;
            $data['stipulation_for_others'] = $sheetData['stipulation_for_others'];
            $data['partnership'] = $sheetData['partnership'];
            $data['protected_sector'] = $sheetData['protected_sector'];

       
            if (empty($data['label'])) {
                throw SheetException::forSheetSave('Le titre est obligatoire');
            }

            $data['status'] = $sheetData['status'] ??  '0';
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $data['updated_at'] = $date;
            $data['updated_by'] = 'nni';
            $data['updated_by_full_name'] = 'full_name';
            $data['contracts'] = $sheetData['contracts'] ?? [];
            $fileUpload = new FileUpload();

            $data['files'] = $fileUpload->uploadSheetFiles($_FILES, [
                'dirpath' => $dirPath,
                'extensions' => ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'],
                'size' => 2100000
            ]);

            $data['contracts'] = $sheetData['contracts'];
            $data['lots'] = $sheetData['lots'];
            $data['filiales'] = $sheetData['filiales'];
            $data['providers'] = $sheetData['providers'];
            $data['groups'] = $sheetData['groups'];
            $data['zones'] = $sheetData['zones'];
            $data['deleted_files'] = $sheetData['deletedFiles'];
            $data['options'] = $sheetData['options'];
            $sheet = new Sheet($this->db);
            try {
                $id = $data['id'];
                $this->db->getPDO()->beginTransaction();
                $sheet->update($data);
                $sheetFile = new SheetFile($this->db);
                foreach ($data['files'] as $file) {
                    if (isset($file['file_name']) && isset($file['file_url'])) {
                        $sheetFile->add($id, $file);
                    }
                }

                foreach ($data['deleted_files'] as $deleted) {
                    $sheetFile->delete($deleted['file_id']);
                }

                $sheetContract = new SheetContract($this->db);
                $sheetLot = new SheetLot($this->db);
                $sheetContract->deleteContracts($id, $data['contracts']);

                foreach ($data['contracts'] as $contract) {
                    $lots = [];
                    $sheetContract->add($id, $contract);

                    foreach ($contract['lots'] as $lot) {
                        $lots[] = $lot['name'];
                        $sheetLot->addLot($id, $contract['contract_code'], $lot['name']);
                    }

                    $sheetLot->deleteContractLots($id, $contract['contract_code'], $lots);
                }

                $sheetFiliale = new SheetBeneficiary($this->db);
                $filialesId  = [];
                foreach ($data['filiales'] as $f) {
                    $filialesId[] = $f['id'];
                    $sheetFiliale->add($id, $f['id']);
                }
                $sheetFiliale->deleteFiliales($id, $filialesId);

                $sheetProvider = new SheetProvider($this->db);
                $providers  = [];
                foreach ($data['providers'] as $p) {
                    $providers[] = $p['id'];
                    $sheetProvider->add($id, $p['id']);
                }
                $sheetProvider->deleteProviders($id, $providers);
                $sheetGroup = new SheetGroup($this->db);
                $groups  = [];
                foreach ($data['groups'] as $p) {
                    $groups[] = $p['id'];
                    $sheetGroup->add($id, $p['id']);
                }
                $sheetGroup->deleteGroups($id, $groups);

                $sheetZone = new SheetArea($this->db);
                $zones  = [];
                foreach ($data['zones'] as $p) {
                    $zones[] = $p['id'];
                    $sheetZone->add($id, $p['id']);
                }
                $sheetZone->deleteZones($id, $zones);

                $lots  = [];
                foreach ($data['lots'] as $lot) {
                    $lots[] = $lot['name'];
                    $sheetLot->add($id, $lot);
                }

                $sheetLot->deleteLots($id, $lots);

                $sheetOption = new SheetOption($this->db);
                $options  = [];
                foreach ($data['options'] as $option) {
                    if (isset($option['id'])) {
                        $sheetOption->update($option);
                        $options[] = $option['id'];
                    } else {
                        $option['sheet_id'] = $id;
                        $options[] = $sheetOption->add($option);
                    }
                }

                $allOptions = $sheetOption->getBySheet($id);

                foreach ($allOptions as $op) {
                    if (!in_array($op['id'], $options)) {
                        $sheetOption->delete($op['id']);
                    }
                }
                
                $this->db->getPDO()->commit();
            } catch (\Exception $e) {
                if ($this->db->getPDO()->inTransaction()) {
                    $this->db->getPDO()->rollback();
                }
                throw SheetException::forSheetSave($e->getMessage());
            }

            return json_encode([
                'data' => $data,
                'warning' => $fileUpload->deleteFilesFromFolder($dirPath, $data['deleted_files'])
            ]);
        } catch (Exception $ex) {
            http_response_code(500);
            return json_encode([
                'status' => 500,
                'error' => $ex->getMessage(),
                'warning' => (new FileUpload())->deleteFilesFromFolder($dirPath, $data['files'])
            ]);
        }
    }

    public function add($sheetData)
    {
        $dirPath =  getenv("CONFIG_UPLOAD_PATH");
        try {
            $user = UserService::getUser();
            $data['label'] = $sheetData['label'] ?? '';
            $data['description'] = $sheetData['description'] ?? '';
            $data['start_date'] = $sheetData['start_date'] ?? '';
            $data['end_date'] = $sheetData['end_date'] ?? '';
            $data['dauphin'] = $sheetData['dauphin'];
            $data['committee'] = $sheetData['committee'] ?? 0;
            $data['edf_open'] = $sheetData['edf_open'];
            $data['showcase'] = $sheetData['showcase'];
            $data['partnership'] = $sheetData['partnership'];
            $data['protected_sector'] = $sheetData['protected_sector'];
            $data['filiales'] = $sheetData['filiales'];
            $data['providers'] = $sheetData['providers'];
            $data['zones'] = $sheetData['zones'];
            $data['groups'] = $sheetData['groups'];
            $data['stipulation_for_others'] = $sheetData['stipulation_for_others'];
            $data['options'] = $sheetData['options'];
            if (empty($data['label'])) {
                throw SheetException::forSheetSave('Le titre est obligatoire');
            }

            $data['status'] = $sheetData['status'] ??  '0';
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $data['created_at'] = $date;
            $data['updated_at'] = $date;
            $data['created_by'] = $user['nni'];
            $data['updated_by'] = $user['nni'];
            $data['created_by_full_name'] = $user['full_name'] ?? '';
            $data['updated_by_full_name'] = $user['full_name'] ??  '';
            $data['contracts'] = $sheetData['contracts'] ?? [];
            $data['lots'] = $sheetData['lots'] ?? [];

            $fileUpload = new FileUpload();
            $data['files'] = $fileUpload->uploadSheetFiles($_FILES, [
                'dirpath' => $dirPath,
                'extensions' => ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'],
                'size' => 2100000
            ]);
            

            $id = $this->addSheet($data);
            return json_encode([
                'data' => $id,
                'data' => $data
            ]);
        } catch (Exception $ex) {
            http_response_code(500);
            return json_encode([
                'status' => 500,
                'error' => $ex->getMessage(),
                'warning' => (new FileUpload())->deleteFilesFromFolder($dirPath, $data['files'])
            ]);
        }
    }

    public function addSheet($data)
    {
        $sheet = new Sheet($this->db);
        try {
            $this->db->getPDO()->beginTransaction();
            $id = $sheet->add($data);
            $sheetFile = new SheetFile($this->db);
            foreach ($data['files'] as $file) {
                if (isset($file['file_name']) && isset($file['file_url'])) {
                    $sheetFile->add($id, $file);
                }
            }

            $sheetLot = new SheetLot($this->db);
            $sheetContract = new SheetContract($this->db);
            foreach ($data['contracts'] as $contract) {
                if (isset($contract['contract_code'])) {
                    $sheetContract->add($id, $contract);
                    foreach ($contract['lots'] as $lot) {
                        $sheetLot->addLot($id, $contract['contract_code'], $lot['name']);
                    }
                }
            }

            $sheetFiliale = new SheetBeneficiary($this->db);
            foreach ($data['filiales'] as $filiale) {
                if (isset($filiale['id'])) {
                    $sheetFiliale->add($id, $filiale['id']);
                }
            }

            $sheetProvider = new SheetProvider($this->db);
            foreach ($data['providers'] as $provider) {
                if (isset($provider['id'])) {
                    $sheetProvider->add($id, $provider['id']);
                }
            }

            $sheetZone = new SheetArea($this->db);
            foreach ($data['zones'] as $zone) {
                if (isset($zone['id'])) {
                    $sheetZone->add($id, $zone['id']);
                }
            }

            $sheetGroup = new SheetGroup($this->db);
            foreach ($data['groups'] as $group) {
                if (isset($group['id'])) {
                    $sheetGroup->add($id, $group['id']);
                }
            }

            $lots  = [];
            foreach ($data['lots'] as $lot) {
                $lots[] = $lot['name'];
                $sheetLot->add($id, $lot);
            }
            
            $sheetOption = new SheetOption($this->db);
            foreach ($data['options'] as $option) {
                $option['sheet_id'] = $id;
                $sheetOption->add($option);
            }

            $this->db->getPDO()->commit();
            return $id;
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }
            
            throw SheetException::forSheetSave($e->getMessage());
        }
    }
}
