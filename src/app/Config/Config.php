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
            "PGI Groupe acheteur" => Status::INITIALISEE,
            "PGI Validation" => Status::INITIALISEE,
            "PHA, consultation Pré-attribution => Refusé" => Status::REFUSEE,
            "PHA, panier Acheteur - Intégrer à l'affaire => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Evaluation Infos générales => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PGI Création" => Status::INITIALISEE,
            "PHA, consultation Attribution => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PGI Groupe acheteur" => Status::INITIALISEE,
            "PHA, consultation Attribution => Refusé" => Status::REFUSEE,
            "PHA, consultation Pré-attribution => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Validation Manager => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Offres à ouvrir => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Clôture => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Validation Manager => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, panier DA réaffectée ? => Validé" => Status::AFFECTER_ACHETEUR,
            "PHA, consultation Validation Acheteur => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, panier Manager achat - Affecter / Refuser => Validé" => Status::SOUMISE_AU_MANAGER,
            "PHA, consultation Evaluation Technique (Acheteur) => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, panier Manager achat - Affecter / Refuser => Refusé" => Status::REFUSEE,
            "PHA, consultation Evaluation Infos générales => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Initialisation => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, panier Acheteur - Intégrer à l'affaire => Refusé" => Status::REFUSEE,
            "PHA, consultation Soumettre à validation Manager => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Evaluation Technique (Prescripteur) => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation En cours => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Clôture => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Soumettre à Validation Manager => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Soumettre à Validation Manager => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Evaluation Commerciale => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Validation Manager => Refusé" => Status::REFUSEE,
            "PHA, consultation Soumettre à validation Manager => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Evaluation Technique (Prescripteur) => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Offres à ouvrir => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, panier Création => Refusé" => Status::REFUSEE,
            "PHA, consultation Initialisation => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Validation Acheteur => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Pré-attribution => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, panier Création => Validé" => Status::TRANSMISE_PHA,
            "PHA, consultation A Publier => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Evaluation Technique (Acheteur) => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation En cours => Validé" => Status::INTEGRER_EN_AFFAIRE,
            "PGI Validation" => Status::INITIALISEE,
            "PHA, consultation A Publier => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation Attribution => Initié" => Status::INTEGRER_EN_AFFAIRE,
            "PHA, consultation" => Status::INTEGRER_EN_AFFAIRE,
        ]
    ],
        'roles' => [
            "ADMIN" => "EHA_ADMIN",
            "GESTIONNAIRE" => "EHA_GESTIONNAIRE",
            "USER" => "USER"
        ],
    'logger'=>[
        "channels"=>[
            "hlk"=>"hlk",
            "app"=>"eha",
            "fhm"=>"fhm",
            "front"=>"front"
        ]
    ]
];

