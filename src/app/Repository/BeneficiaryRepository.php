<?php

namespace App\Repository;

use App\Models\Beneficiary;

class BeneficiaryRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $beneficiary = new Beneficiary();
        return $beneficiary->getList(['name' => $word]);
    }

    public function search($options)
    {
        $beneficiary = new Beneficiary();

        return
            $beneficiary->getPaginatedList($options);
    }
}