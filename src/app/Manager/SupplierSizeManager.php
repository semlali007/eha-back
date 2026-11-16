<?php

namespace App\Manager;

use App\Manager\AbstractManager;
use App\Models\SupplierSize;

class SupplierSizeManager extends AbstractManager
{
    /**
     * Récupère toutes les tailles de fournisseurs
     * @return array
     */
    public function getAll(): array
    {
        try {
            $supplierSize = new SupplierSize();
            return $supplierSize->getAll();
        } catch (\Exception $e) {
            // En cas d'erreur, retourner un tableau vide
            return [];
        }
    }

    /**
     * Récupère une taille de fournisseur par ID
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        try {
            $supplierSize = new SupplierSize();
            return $supplierSize->getById($id);
        } catch (\Exception $e) {
            return null;
        }
    }
}
