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
         "config" => [
            "PGI Création" => 1, // Création
            "PGI Validation" => 2, // Instruction,
            "PHA, consultation Validation Acheteur => Validé" => 2,
            "PHA, panier Manager achat - Affecter / Refuser => Refusé" => 3, // Refusée
        ]
        ,
        "status" =>
        [
            "Pré-attribution => Refusé" => Status::REFUSEE,
            "Attribution => Refusé" => Status::REFUSEE,
            "Manager achat - Affecter / Refuser => Refusé" => Status::REFUSEE,
            "Acheteur - Intégrer à l'affaire => Refusé" => Status::REFUSEE,
            "Création" => Status::INITIALISEE,
            "Groupe acheteur" => Status::INITIALISEE,
            "Validation" => Status::INITIALISEE,
            "Evaluation Technique (Acheteur) => Initié" => Status::TRANSMISE_PHA,
            "Manager achat - Affecter / Refuser => Validé" => Status::SOUMISE_AU_MANAGER,
            "DA réaffectée ? => Validé" => Status::AFFECTER_ACHETEUR,
            "Acheteur - Intégrer à l'affaire => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Infos générales => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Attribution => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Pré-attribution => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Validation Manager => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Offres à ouvrir => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Clôture => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Validation Manager => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Validation Acheteur => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Infos générales => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Initialisation => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Soumettre à validation Manager => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Technique (Prescripteur) => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "En cours => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Clôture => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Soumettre à Validation Manager => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Soumettre à Validation Manager => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Commerciale => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Soumettre à validation Manager => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Technique (Prescripteur) => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Offres à ouvrir => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Création => Refusé" => Status::INTEGRER_EN_AFFAIRE,
            "Initialisation => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Validation Acheteur => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Pré-attribution => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Création => Validé" => Status::TRANSMISE_PHA,
            "A Publier => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Technique (Acheteur) => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "En cours => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "A Publier => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Attribution => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "Evaluation Commerciale => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PGI Groupe acheteur"=>Status::INITIALISEE,
            "PGI Validation"=>Status::INITIALISEE,
        ]
    ],
        'roles' => [
            "ADMIN" => "EHA_ADMIN",
            "GESTIONNAIRE" => "EHA_GESTIONNAIRE",
            "USER" => "USER"
        ]
];

