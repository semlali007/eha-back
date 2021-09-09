<?php

$configuration = [
    'hlk' => [
        "pwd" => getenv("HLK_API_PROD_PASS"), // Token Password 
        "url" => getenv("HLK_API_PROD_URL"),
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
            ]
        ]
    ]
    , 
    'roles' => [
        "EHA_ADMIN" => "ADMIN",
        "EHA_GESTIONNAIRE" => "GESTIONNAIRE"
    ]
];


