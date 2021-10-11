<?php


$router->get('/api/monitoring/health_check', 'App\Controllers\HealthcheckController@index');
$router->post('/authentication/login', 'App\Controllers\AuthenticationController@login');
$router->get('/authentication/logout', 'App\Controllers\AuthenticationController@logout');

$router->post('/search', 'App\Controllers\SearchController@search', ['USER']);
$router->post('/search/anchors', 'App\Controllers\SearchController@getAnchors', ['USER']);

## Contract ###
$router->get('/contracts/show/:code', 'App\Controllers\ContractController@show');
$router->get('/contracts/search/:q', 'App\Controllers\ContractController@searchFor');

### Dauphins ###
$router->get('/dauphins/show/:code', 'App\Controllers\DauphinController@show');
$router->get('/dauphins/search/:code', 'App\Controllers\DauphinController@search');

### Items ###
$router->get('/items/show/:code', 'App\Controllers\ItemController@show');
$router->get('/items/search/:code', 'App\Controllers\ItemController@search');

## Products ##
$router->get('/products/show/:code', 'App\Controllers\ProductController@show');
$router->get('/products/search/:code', 'App\Controllers\ProductController@search');

### providers ###
$router->get('/providers/show/:code', 'App\Controllers\ProviderController@show');
$router->get('/providers/search/:code', 'App\Controllers\ProviderController@search');

$router->post('/sheets', 'App\Controllers\SheetController@index', ['USER']);
$router->get('/sheets/show/:id', 'App\Controllers\SheetController@show', ['USER']);
$router->post('/sheets/export', 'App\Controllers\SheetController@export', ['USER']);
$router->post('/sheets/add', 'App\Controllers\SheetController@add', ['ADMIN']);
$router->post('/sheets/save', 'App\Controllers\SheetController@update', ['ADMIN']);
$router->post('/sheets/download', 'App\Controllers\SheetController@download', ['USER']);

$router->get('/committees', 'App\Controllers\CommitteeController@index', ['USER']);
$router->get('/providers/autocomplete/:word', 'App\Controllers\ProviderController@autocomplete', ['USER']);
$router->post('/providers/search', 'App\Controllers\ProviderController@search', ['USER']);

$router->get('/beneficiaries/autocomplete/:word', 'App\Controllers\BeneficiaryController@autocomplete', ['USER']);
$router->post('/beneficiaries/search', 'App\Controllers\BeneficiaryController@search', ['USER']);
$router->post('/beneficiaries/add', 'App\Controllers\BeneficiaryController@add', ['ADMIN']);

$router->get('/areas/autocomplete/:word', 'App\Controllers\AreaController@autocomplete', ['USER']);
$router->post('/areas/search', 'App\Controllers\AreaController@search', ['USER']);
$router->post('/areas/add', 'App\Controllers\AreaController@add', ['ADMIN']);

$router->get('/groups/autocomplete/:word', 'App\Controllers\GroupController@autocomplete', ['USER']);
$router->post('/groups/search', 'App\Controllers\GroupController@search', ['USER']);
$router->post('/groups/add', 'App\Controllers\GroupController@add', ['ADMIN']);
// affichage de se documenter dans l'accueil ----
$router->get('/documentations', 'App\Controllers\DocumentationController@index', ['USER']);
// Hotlines 
$router->post('/hotlines', 'App\Controllers\HotlineController@index', ['USER']);
$router->get('/hotlines/configuration', 'App\Controllers\HotlineController@configuration', ['USER']);
$router->get('/hotlines/get/:id/sha/:sha', 'App\Controllers\HotlineController@show', ['USER']);
$router->post('/hotlines/create', 'App\Controllers\HotlineController@create', ['USER']);

$router->post('/hotlines/forward', 'App\Controllers\HotlineActionController@forward', ['USER']);
$router->post('/hotlines/post', 'App\Controllers\HotlineActionController@post', ['USER']);
$router->post('/hotlines/upload', 'App\Controllers\HotlineActionController@upload', ['USER']);
$router->post('/hotlines/close', 'App\Controllers\HotlineActionController@close', ['USER']);

// Gestion de se documenter coté admin ... 
$router->get('/documentations/edit', 'App\Controllers\Admin\DocumentationController@edit', ['ADMIN']);
$router->post('/documentations/add', 'App\Controllers\Admin\DocumentationController@add', ['ADMIN']);
$router->get('/documentations/delete', 'App\Controllers\Admin\DocumentationController@delete', ['ADMIN']);
$router->get('/documentations/show/:id', 'App\Controllers\Admin\DocumentationController@show', ['ADMIN']);
$router->post('/documentations/save', 'App\Controllers\Admin\DocumentationController@update', ['ADMIN']);
// Gestion des admins
$router->get('/administrators', 'App\Controllers\Admin\AdministratorController@index', ['ADMIN']);
$router->post('/administrators/create', 'App\Controllers\Admin\AdministratorController@create', ['ADMIN']);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ['ADMIN']);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ['ADMIN']);
// Externals
$router->get('/externals', 'App\Controllers\Admin\ExternalController@index', ['ADMIN']);
$router->post('/externals/create', 'App\Controllers\Admin\ExternalController@create', ['ADMIN']);
$router->get('/externals/delete/:email', 'App\Controllers\Admin\ExternalController@delete', ['ADMIN']);

$router->get('/hotline-themes', 'App\Controllers\Admin\Hotline\HotlineThemeController@index', ['ADMIN']);
$router->post('/hotline-themes/save', 'App\Controllers\Admin\Hotline\HotlineThemeController@update', ['ADMIN']);

$router->get('/hotline-groups', 'App\Controllers\Admin\Hotline\HotlineGroupController@index', ['ADMIN']);
$router->post('/hotline-groups/save', 'App\Controllers\Admin\Hotline\HotlineGroupController@update', ['ADMIN']);

$router->get('/hotline-emails', 'App\Controllers\Admin\Hotline\HotlineEmailController@index', ['ADMIN']);
$router->post('/hotline-emails/save', 'App\Controllers\Admin\Hotline\HotlineEmailController@update', ['ADMIN']);
$router->post('/hotline-emails/living-save', 'App\Controllers\Admin\Hotline\HotlineEmailController@livingUpdate', ['ADMIN']);

$router->post('/import/upload', 'App\Controllers\Admin\ImportController@upload', ['ADMIN']);

$router->post('/migration/save', 'App\Controllers\Admin\MigrateController@saveData', ['ADMIN']);
$router->post('/migration/migrate', 'App\Controllers\Admin\MigrateController@migrate', ['ADMIN']);
$router->post('/migration/list', 'App\Controllers\Admin\MigrateController@index', ['ADMIN']);

//try {
$data = $router->run();
if (is_string($data) && is_array(json_decode($data, true)) && (json_last_error() == JSON_ERROR_NONE)):
    echo $data;
endif ;
#le try catch à été supprimé car Les détails exposés sur l'environnement de l'application, les utilisateurs ou les données associées (par exemple,
#la trace de la pile) pourraient permettre à un attaquant de trouver une autre faille et l'aider à lancer une attaque. Cela peut également divulguer des données sensibles, par ex.
#mots de passe ou champs de base de données. 
