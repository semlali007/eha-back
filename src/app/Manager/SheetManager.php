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
use App\Security\User;

class SheetManager extends AbstractManager {

    public function update($user, $sheetData) {
        $dirPath = getenv("CONFIG_UPLOAD_PATH");
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

            $data['status'] = $sheetData['status'] ?? '0';
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $data['updated_at'] = $date;
            $data['updated_by'] = 'nni';
            $data['updated_by_full_name'] = 'full_name';
            $data['contracts'] = $sheetData['contracts'] ?? [];
            $fileUpload = new FileUpload();

            $data['files'] = $fileUpload->uploadSheetFiles([
                'dirpath' => $dirPath,
                'extensions' => ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'],
                'size' => 2100000
            ]);

            $data['contracts'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['contracts'] : [];
            $data['lots'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['lots'] : [];
            $data['filiales'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['filiales'] : [];
            $data['providers'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['providers'] : [];
            $data['groups'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['groups'] : [];
            $data['zones'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['zones'] : [];
            $data['deleted_files'] =  (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['deletedFiles'] : [];
            $data['options'] = (is_array($sheetData) && array_key_exists('contracts', $sheetData)) ? $sheetData['options'] : [];
            
            $sheet = new Sheet();
            try {
                $id = $data['id'];
                $this->db->getPDO()->beginTransaction();
                $sheet->update($data);
                $sheetFile = new SheetFile();
                foreach ($data['files'] as $file) {
                    if (isset($file['file_name']) && isset($file['file_url'])) {
                        $sheetFile->add($id, $file);
                    }
                }

                foreach ($data['deleted_files'] as $deleted) {
                    $sheetFile->delete($deleted['file_id']);
                }

                $sheetContract = new SheetContract();
                $sheetLot = new SheetLot();
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

                $sheetFiliale = new SheetBeneficiary();
                $filialesId = [];
                foreach ($data['filiales'] as $f) {
                    $filialesId[] = $f['id'];
                    $sheetFiliale->add($id, $f['id']);
                }
                $sheetFiliale->deleteFiliales($id, $filialesId);

                $sheetProvider = new SheetProvider();
                $providers = [];
                foreach ($data['providers'] as $p) {
                    $providers[] = $p['id'];
                    $sheetProvider->add($id, $p['id']);
                }
                $sheetProvider->deleteProviders($id, $providers);
                $sheetGroup = new SheetGroup();
                $groups = [];
                foreach ($data['groups'] as $p) {
                    $groups[] = $p['id'];
                    $sheetGroup->add($id, $p['id']);
                }
                $sheetGroup->deleteGroups($id, $groups);

                $sheetZone = new SheetArea();
                $zones = [];
                foreach ($data['zones'] as $p) {
                    $zones[] = $p['id'];
                    $sheetZone->add($id, $p['id']);
                }
                $sheetZone->deleteZones($id, $zones);

                $lots = [];
                foreach ($data['lots'] as $lot) {
                    $lots[] = $lot['name'];
                    $sheetLot->add($id, $lot);
                }

                $sheetLot->deleteLots($id, $lots);

                $sheetOption = new SheetOption();
                $options = [];
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

    public function add(User $user, $sheetData) {
        $dirPath = getenv("CONFIG_UPLOAD_PATH");

        try {

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

            $data['status'] = $sheetData['status'] ?? '0';
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $data['created_at'] = $date;
            $data['updated_at'] = $date;
            $data['created_by'] = $user->getNni();
            $data['updated_by'] = $user->getNni();
            $data['created_by_full_name'] = $user->getFullname() ?? '';
            $data['updated_by_full_name'] = $user->getFullname() ?? '';
            $data['contracts'] = $sheetData['contracts'] ?? [];
            $data['lots'] = $sheetData['lots'] ?? [];

            $fileUpload = new FileUpload();
            $data['files'] = $fileUpload->uploadSheetFiles([
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

    public function addSheet($data) {
        $sheet = new Sheet();
        try {
            $this->db->getPDO()->beginTransaction();
            $id = $sheet->add($data);
            $sheetFile = new SheetFile();
            foreach ($data['files'] as $file) {
                if (isset($file['file_name']) && isset($file['file_url'])) {
                    $sheetFile->add($id, $file);
                }
            }

            $sheetLot = new SheetLot();
            $sheetContract = new SheetContract();
            foreach ($data['contracts'] as $contract) {
                if (isset($contract['contract_code'])) {
                    $sheetContract->add($id, $contract);
                    foreach ($contract['lots'] as $lot) {
                        $sheetLot->addLot($id, $contract['contract_code'], $lot['name']);
                    }
                }
            }

            $sheetFiliale = new SheetBeneficiary();
            foreach ($data['filiales'] as $filiale) {
                if (isset($filiale['id'])) {
                    $sheetFiliale->add($id, $filiale['id']);
                }
            }

            $sheetProvider = new SheetProvider();
            foreach ($data['providers'] as $provider) {
                if (isset($provider['id'])) {
                    $sheetProvider->add($id, $provider['id']);
                }
            }

            $sheetZone = new SheetArea();
            foreach ($data['zones'] as $zone) {
                if (isset($zone['id'])) {
                    $sheetZone->add($id, $zone['id']);
                }
            }

            $sheetGroup = new SheetGroup();
            foreach ($data['groups'] as $group) {
                if (isset($group['id'])) {
                    $sheetGroup->add($id, $group['id']);
                }
            }

            $lots = [];
            foreach ($data['lots'] as $lot) {
                $lots[] = $lot['name'];
                $sheetLot->add($id, $lot);
            }

            $sheetOption = new SheetOption();
            if (array_key_exists('options', $data)) {
                foreach ($data['options'] as $option) {
                    $option['sheet_id'] = $id;
                    $sheetOption->add($option);
                }
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
