<?php

use Core\Util\Status;

$configuration = [
    'hlk' => [
        "pwd" => getenv("HLK_API_PASS"), // Token Password 
        "url" => getenv("HLK_API_URL"),
        "limit" => 20,
        "actions" =>
        [
            'products' => [// Dauphins
                "search" => "search_products", // Action
                "get" => "get_product", // verbe à utiliser
            ],
            'contracts' => [
                "search" => "search_contracts", // Action
                "get" => "get_contract", // verbe à utiliser
            ],
            'providers' => [
                "search" => "search_providers", // Action
                "get" => "get_provider", // verbe à utiliser
            ],
            'anchors' => [
                "search" => "search_around_zipcode", // search_anchors
                "get" => "get_anchor", // verbe à utiliser
            ],
            'items' => [
                "search" => "search_items", // Action
                "get" => "get_item", // verbe à utiliser
            ],
            'purchases' => [
                "search" => "get_purchase_situation_id", // Action
                "list" => "list_purchase_situations",
            ]
        ],
        "status" =>
        [
            Status::REFUSEE => [
                "Pré-attribution => Refusé",
                "Attribution => Refusé",
                "Manager achat - Affecter / Refuser => Refusé",
                "Acheteur - Intégrer à l'affaire => Refusé",
            ],
            Status::INITIALISEE => [
                "Création",
                "Groupe acheteur",
                "Validation"
            ],
            Status::TRANSMISE_PHA => [
                "Evaluation Technique (Acheteur) => Initié",
            ],
            Status::SOUMISE_AU_MANAGER => [
                "Manager achat - Affecter / Refuser => Validé"
            ],
            Status::AFFECTER_ACHETEUR => [
                "DA réaffectée ? => Validé"
            ],
            Status::INTEGRER_EN_AFFAIRE => [
                "Acheteur - Intégrer à l'affaire => Validé",
                "Evaluation Infos générales => Validé",
                "Attribution => Validé",
                "Pré-attribution => Validé",
                "Validation Manager => Validé",
                "Offres à ouvrir => Initié",
                "Clôture => Validé",
                "Validation Manager => Initié",
                "Validation Acheteur => Initié",
                "Evaluation Infos générales => Initié",
                "Initialisation => Initié",
                "Soumettre à validation Manager => Initié",
                "Evaluation Technique (Prescripteur) => Initié",
                "En cours => Initié",
                "Clôture => Initié",
                "Soumettre à Validation Manager => Initié",
                "Soumettre à Validation Manager => Validé",
                "Evaluation Commerciale => Validé",
                "Soumettre à validation Manager => Validé",
                "Evaluation Technique (Prescripteur) => Validé",
                "Offres à ouvrir => Validé",
                "Création => Refusé",
                "Initialisation => Validé",
                "Validation Acheteur => Validé",
                "Pré-attribution => Initié",
                "Création => Validé",
                "A Publier => Validé",
                "Evaluation Technique (Acheteur) => Validé",
                "En cours => Validé",
                "A Publier => Initié",
                "Attribution => Initié",
                "Evaluation Commerciale => Initié"
            ]
        ],
        'roles' => [
            "ADMIN" => "EHA_ADMIN",
            "GESTIONNAIRE" => "EHA_GESTIONNAIRE",
            "USER" => "USER"
        ]
    ]
];

