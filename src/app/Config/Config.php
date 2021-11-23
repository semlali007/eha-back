<?php

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
            'items' => [
                "search" => "search_items", // Action
                "get" => "get_item", // verbe à utiliser
            ],
            'purchases' => [
                "search" => "get_purchase_situation_id", // Action
            ]
        ]
    ]
    , 
    'roles' => [
        "ADMIN" => "EHA_ADMIN",
        "GESTIONNAIRE" => "EHA_GESTIONNAIRE",
        "USER" => "USER"
    ]
];


