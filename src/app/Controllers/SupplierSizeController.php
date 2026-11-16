<?php

namespace App\Controllers;

use App\Models\SupplierSize;

class SupplierSizeController extends Controller {

    public function index() {
        try {
            $supplierSize = new SupplierSize();
            $sizes = $supplierSize->getAll();
            
            return $this->response(['supplier_sizes' => $sizes]);
        } catch (\Exception $e) {
            return $this->response(['error' => $e->getMessage()], 500);
        }
    }
}
