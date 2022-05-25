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
use App\Models\Subscription;
use App\Services\FileUpload;
use App\Services\UserService;
use Exception;
use App\Security\User;
use App\Models\Provider;
use App\Models\Beneficiary;
use App\Models\Area;
use App\Models\Group;
use phpDocumentor\Reflection\Types\Null_;

class SheetManager extends AbstractManager
{

    public function update($user, $sheetData)
    {
        $dirPath = getenv("CONFIG_UPLOAD_PATH");
        try {
            $data['id'] = $sheetData['id'];
            $data['label'] = $sheetData['label'] ?? '';
            $data['description'] = $sheetData['description'] ?? '';
            $data['content'] = $sheetData['content'] ?? '';
            $data['start_date'] = ($sheetData['start_date'] && $sheetData['start_date'] != '')? $sheetData['start_date'] : null;
            $data['end_date'] = ($sheetData['end_date'] && $sheetData['end_date'] != '') ?$sheetData['end_date'] : null;
            $data['dauphin'] = $sheetData['dauphin'];
            $data['edf_open'] = $sheetData['edf_open'];
            $data['showcase'] = $sheetData['showcase'];
            $data['committee'] = $sheetData['committee'] ?? 0;
            $data['stipulation_for_others'] = $sheetData['stipulation_for_others'];
            $data['partnership'] = $sheetData['partnership'];
            $data['protected_sector'] = $sheetData['protected_sector'];
            $data['status'] = $sheetData['status'] ?? '0';
            if (isset($sheetData['draft']) && $sheetData['draft'] == true) {
                $data['status'] = '0';
            } else {
                if (empty($data['label'])) {
                    throw SheetException::forSheetSave('Le titre est obligatoire');
                }
            }
            
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

            $data['lots'] = $sheetData['lots'] ?? [];
            $data['filiales'] = $sheetData['filiales'] ?? [];
            $data['providers'] = $sheetData['providers'] ?? [];
            $data['groups'] = $sheetData['groups'] ?? [];
            $data['zones'] = $sheetData['zones'] ?? [];
            $data['deleted_files'] = $sheetData['deletedFiles'] ?? [];
            $data['options'] = $sheetData['options'] ?? [];

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
            $data['start_date'] = ($sheetData['start_date'] && $sheetData['start_date'] != '') ? $sheetData['start_date']: null;
            $data['end_date'] = ($sheetData['end_date'] && $sheetData['end_date'] != '') ?$sheetData['end_date']: null;
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
            $data['status'] = $sheetData['status'] ?? '0';
            if (isset($sheetData['draft']) && $sheetData['draft'] == true) {
                $data['status'] = '0';
            } else {
                if (empty($data['label'])) {
                    throw SheetException::forSheetSave('Le titre est obligatoire');
                }
            }
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
                'id' => $id,
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

    
    /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    function remove($id){
        return (new Sheet())->deleteSheet($id); 
    }

     /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    function subscribe($sheetId, $userNni){
        return (new Subscription())->add($sheetId, $userNni);
    }

         /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    function unsubscribe($sheetId, $userNni){
        return (new Subscription())->remove($sheetId, $userNni);
    }

    public static $MigrateConfig = [
        'source' => '',
        'dait' =>
            [
                'contrat' => 'Contrats_DAIT/',
                'doc_dep' => 'Doc_Dep_DAIT/',
            ],
        'dapi' =>
            [
                'contrat' => 'Contrats_DAPI/',
                'doc_dep' => 'Doc_Dep_DAPI/',
            ],
        'datp' =>
            [
                'contrat' => [
                    'edf_enedis' => 'Contrats_EDF_Enedis/',
                    'enedis' => 'Contrats_Enedis/',
                ],
                'doc_dep' => 'Doc_Dep_DATP/',
            ]
            ,
        'datp_archivage' =>
            [
                'contrat' => [
                    'edf_enedis' => 'Contrats_EDF_Enedis/',
                    'enedis' => 'Contrats_Enedis/',
                ],
                'doc_dep' => 'Doc_Dep_DATP/',
            ]
    ];
    public function migrate($type)
    {
        $type = trim($type);
        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");
        $sources = [
            "dait" => [
                "files" => ["dait1.json", "dait2.json"],
                "status" => '1'
            ],
            "dapi" => [
                "files" => ["dapi1.json", "dapi2.json"],
                "status" => '1'
            ],
            "datp" => [
                "files" => ["datp1.json", "datp2.json"],
                "status" => '1'
            ],
            "datp_archivage" => [
                "files" => ["datp_archivage1.json", "datp_archivage2.json"],
                "status" => '-1'
            ],
            "dapi_archivage" => [
                "files" => ["dapi_archivage.json"],
                "status" => '-1'
            ],
            "dait_archivage" => [
                "files" => ["dait_archivage"],
                "status" => '-1'
            ],
        ];

        if(!array_key_exists($type, $sources)){
            throw new Exception('type de fiche inconnu');
            exit;
        }

        $netachatData = [];
        foreach ($sources[$type]['files'] as $i => $f) {
            $s = json_decode(file_get_contents($destinationMigrationPath .'netachat/'.$f), true);
            $netachatData = array_merge($netachatData,$s['Row']);
        }
        
       
        foreach ($netachatData as $row) {
            $this->migrateOneSheet(['sheet'=>$row ,'type'=>$type, 'status'=>$sources[$type]['status']]);
        }
    }
    public function migrateOneSheet($options)
    {
        $data = [];
        $sheet = $options['sheet'];
        $data['netachat_type'] = $options['type'];
        $data['status'] = $options['status'];
        $data['contracts'] = [];
        $data['lots'] = [];
        $data['netachat_id'] = $sheet['ID'] ?? '';
        $data['label'] = self::formateHtml($sheet['Title']);
        $data['content'] = self::formateHtml($sheet['Contenu']);
        $data['description'] = self::formateHtml($sheet['Desc']);
        $origDateDebut = trim($sheet['DateDeb']);
        $dateDebut = str_replace('/', '-', $origDateDebut);
        $newDateDebut = date("Y-m-d", strtotime($dateDebut));
        $origDateFin = trim($sheet['DateFin']);
        $dateFin = str_replace('/', '-', $origDateFin);
        $newDateFin = date("Y-m-d", strtotime($dateFin));
        $data['start_date'] = $newDateDebut;
        $data['end_date'] = $newDateFin;
        $data['dauphin'] = boolval($sheet['ApproDauphin.value']);
        $date = date('Y-m-d H:i:s');
        $data['created_at'] = $date;
        $data['updated_at'] = $date;
        $data['created_by'] = 'MIGRATION';
        $data['updated_by'] = 'MIGRATION';
        $data['created_by_full_name'] = 'MIGRATION';
        $data['updated_by_full_name'] = 'MIGRATION';
        $data['edf_open'] = boolval($sheet['MarcheOuvertPersEDF.value']);
        $data['showcase'] = false;
        $data['partnership'] = boolval($sheet['PartenariatProd.value']);
        $data['protected_sector'] = boolval($sheet['SecteurProt.value']);
        $data['stipulation_for_others'] = boolval($sheet['StipulationPourAutrui.value']);
        $data['providers'] = [];
        $data['filiales'] = [];
        $data['zones'] = [];
        $data['files'] = [];
        $data['groups'] = [];
        $group = new Group();
        $groupLabel = $options['type'];
        if (!is_numeric($groupLabel)  && !empty($groupLabel)) {
            if ($groupLabel === 'dapi') {
                $groupLabel = 'DOA PRODUCTION';
            }

            if ($groupLabel === 'dait' || $groupLabel === 'datp') {
                $groupLabel = 'DOA TP&IT';
            }

            $group->add(['name' => $groupLabel]);
            $p = $group->get(['name' => $groupLabel]);
            if (count($p) > 0) {
                $data['groups'][] = ['id' => $p[0]['id']];
            }
        }
        $data = array_merge($data, $this->migrateOptions($sheet));
        $data =  array_merge($data, $this->kitDep($sheet, $options['type']));
        $this->addSheet($data);
    }


    public function kitDep($list, $type)
    {
        $data = [];
        $data['files'] = [];
        $migrateConfig = self::$MigrateConfig;
        switch ($type) {
            case 'dait':
                $config = $migrateConfig['dait'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Actifs/";
                $foldersList = $list['KitDAIT'];
                break;
            case 'dapi':
                $config = $migrateConfig['dapi'];
                $foldersList = $list['KitDAPI'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Actifs/";
                break;
            case 'datp':
                $config = $migrateConfig['datp'];
                $foldersList = $list['KitDATP'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Actifs/";
                break;
            case 'datp_archivage':
                $config = $migrateConfig['datp_archivage'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Archives/";
                $foldersList = $list['KitDATP'];
                break;
            case 'dait_archivage':
                $config = $migrateConfig['dait_archivage'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Archives/";
                $foldersList = $list['KitDAIT'];
                break;
            case 'dapi_archivage':
                $config = $migrateConfig['dapi_archivage'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Archives/";
                $foldersList = $list['KitDAPI'];
                break;
            default:
                throw new Exception('type de contact inconnu');
                break;
        }

        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");

        if(is_null($migrateConfig) || !is_array($foldersList)){
            return $data;
        }
        foreach ($foldersList as $kit) {
            $kitFolderName = str_replace(" ", "_", $kit['lookupValue']);
            if (!empty($kitFolderName)) {
                if (is_dir($sourceDepDoc . $kitFolderName)) {
                    $docDep = scandir($sourceDepDoc . $kitFolderName);
                    if ($docDep !== false) {
                        foreach ($docDep as $fileName) {
                            if ('.' !== $fileName && '..' !== $fileName) {
                                $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                $destFileName = uniqid('D_' . $name . '_') . '.' . $ext;
                                $path = $sourceDepDoc . $kitFolderName . '/' . basename($fileName);
                                if (copy($path, $destinationMigrationPath . $destFileName)) {
                                    $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 1];
                                }
                            }
                        }
                    }
                }

            }
        }

        return $data;
    }

    public function migrateOptions($row)
    {
        $data = [];
        $data['providers'] = [];
        $data['filiales'] = [];
        $data['zones'] = [];
       
        $filiale = new Beneficiary();
        $provider = new Provider();
        $zone = new Area();
        

        foreach ($row['Fournisseur'] as $item) {
            if (!is_numeric($item)  && !empty($item)) {
                $provider->add(['provider_label' => substr($item["Label"], 0, 50), 'provider_code' => $item["Label"]]);
                $p = $provider->get(['provider_label' => $item["Label"], 'provider_code' => $item["Label"]]);
                if (count($p) > 0) {
                    $data['providers'][] = ['id' => $p[0]['provider_code']];
                }
            }
        }

        foreach ($row['Beneficiaires'] as $itemB) {
            $filiale->add(['name' => $itemB['Label']]);
            $p = $filiale->get(['name' => $itemB['Label']]);
            if (count($p) > 0) {
                $data['filiales'][] = ['id' => $p[0]['id']];
            }
        }


        foreach ($row['ZoneGeo'] as $itemZ) {
            $zone->add(['name' => $itemZ['Label']]);
            $p = $zone->get(['name' => $itemZ['Label']]);
            if (count($p) > 0) {
                $data['zones'][] = ['id' => $p[0]['id']];
            }
        }
        


        return $data;
    }

    public static function formateHtml($content){
        
        $content = preg_replace('/\>\s+\</m', '><', $content);
        $content = str_replace('&nbsp;', 'replacedwords', $content);
        $content = html_entity_decode($content);
        $content = preg_replace('/\>[\x00-\x1F\x80-\xFF]+</', '><', $content);
        $content = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $content );
        $content = preg_replace('/\>\s+\</m', '><', $content);
        $content = str_replace("<br>", "", $content);
        $content = str_replace("<br/>", "<br>", $content);
        $content = str_replace('<br><t', '<t', $content);
        $content = str_replace('<br></t', '</t', $content);
        $content = str_replace('&#160;', ' ', $content);
        $content = str_replace('<p>&#160;</p>', '', $content);
        $content = preg_replace('/\>\ +\</m', '><', $content);
        $content = preg_replace('/\>(replacedwords)+/', '>', $content);
        $content = preg_replace('/(replacedwords)+<\//', '</', $content);
        $content = preg_replace('/class="ms-rteTable[-0-9a-zA-Z.+_\s]+"/', 'border="1"', $content);

        $htmlReplace = [
            'replacedwords' => '',
            '<p>&#160;</p>' => '',
            '<strong>N° de contrat PGI</strong></td>' => '<p><strong>N° de contrat PGI</strong></p></td>',
            '<strong>Fournisseur</strong></td>' => '<p><strong>Fournisseur</strong></p></td>',
            '<strong>Fournisseurs</strong></td>' => '<p><strong>Fournisseurs</strong></p></td>',
            '<strong>Contact DAIT</strong></td>' => '<p><strong>Contact DAIT</strong></p></td>',
            '<strong>N° de contrat</strong></td>' => '<p><strong>N° de contrat</strong></p></td>',
            '<strong>contacts</strong></td>' => '<p><strong>contacts</strong></p></td>',
            '<strong>Contact</strong></td>' => '<p><strong>Contact</strong></p></td>',
            '<strong>N° de marché</strong></td>' => '<p><strong>N° de marché</strong></p></td>',
            '<strong>N° Marché</strong></td>' => '<p><strong>N° Marché</strong></p></td>',
            '<strong>Contact fournisseur</strong></td>' => '<p><strong>Contact fournisseur</strong></p></td>',
            '<strong>Interlocuteurs</strong></td>' => '<p><strong>Interlocuteurs</strong></p></td>',
            '<strong>Contacts fournisseur</strong></td>' => '<p><strong>Contacts fournisseur</strong></p></td>',
            '<strong>No de marché</strong></td>' => '<p><strong>No de marché</strong></p></td>',
            '<strong>ENEDIS n° de contrat</strong></td>' => '<p><strong>ENEDIS n° de contrat</strong></p></td>',
            '<strong>Fournisseur</strong></td>' => '<p><strong>Fournisseur</strong></p></td>',
            '<strong>EDF HYDRO N° de contrat</strong></td>' => '<p><strong>EDF HYDRO N° de contrat</strong></p></td>',
            '<strong>Contact </strong></td>' => '<p><strong>Contact</strong></p></td>',
            '&#39;' => "'",
            'class="ms-rteTable-8"' => 'border="1"'
        ];

        foreach ($htmlReplace as $src => $dest) {
            $content = str_replace($src, $dest, $content);
        }
        
        preg_match_all('/<a href="mailto:[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+.[a-zA-Z]+">mailto:[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+.[a-zA-Z]+<\/a>/', $content, $emails, PREG_PATTERN_ORDER);
        foreach ($emails[0] as $code) {
            if (preg_match("/@/", $code)) {
                $explode = explode('"', $code);
                $content = str_replace($code, $explode[1], $content);
            }
        }

        preg_match_all('/(<a href="<a href=").*("\>).*(<\/a\>")/', $content, $tttt, PREG_PATTERN_ORDER);
      
        foreach ($tttt[0] as $code) {
            
            if (preg_match('/<a href="<a href="/', $code)) {
                $c = substr($code, 17);
                $explode = explode('"', $c);
                $content = str_replace($code, '<a href="' . $explode[1] . '"', $content);
            }
        }

       
        preg_match_all("/C[a-zA-Z_0-9]{9}</", $content, $matches1, PREG_PATTERN_ORDER);

        foreach ($matches1[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, -1);
                $content = str_replace($code, '<a href="app/contrat/details/' . $c . '" target="_blank" class="link">' . $c . '</a><', $content);
            }
        }

        preg_match_all("/C[a-zA-Z_0-9]{9}\s/", $content, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, -1);
                $content = str_replace($code, ' <a href="app/contrat/details/' . $code . '" target="_blank" class="link">' . $c . '</a> ', $content);
            }
        }

        preg_match_all("/C[a-zA-Z_0-9]{8}<strong>[a-zA-Z_0-9]{1}<\/strong>/", $content, $matches2, PREG_PATTERN_ORDER);

        foreach ($matches2[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, -18);
                $i = substr($code, 17, -9);
                $content = str_replace($code, '<a href="app/contrat/details/' . $c . $i . '" target="_blank" class="link">' . $code . '</a>', $content);
            }
        }

        preg_match_all('/C[a-zA-Z_0-9]{8}<span class="ms-rteForeColor-2">[a-zA-Z_0-9]{1}<\/span>/', $content, $matches22, PREG_PATTERN_ORDER);

        foreach ($matches22[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, 9);
                $i = substr($code, -8, -7);
                $content = str_replace($code, '<a href="app/contrat/details/' . $c . $i . '" target="_blank" class="link">' . $code . '</a>', $content);
            }
        }
        
        return $content;

    }

    public function getNetAchatSheet(){
        $sheet = new Sheet();
        return $sheet->getNetAchatSheet();
    }
}
