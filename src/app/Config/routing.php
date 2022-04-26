<?php
$router->post('/purchases/list', 'App\Controllers\PurchaseController@list',["roles"=>['USER']]);
$router->get('/notifications/list/:nni', 'App\Controllers\NotificationsController@index');
$router->post('/notifications/update', 'App\Controllers\NotificationsController@update');
$router->post('/notifications/add', 'App\Controllers\NotificationsController@add');

$router->get('/api/monitoring/health_check', 'App\Controllers\HealthcheckController@index');
$router->post('/authentication/login', 'App\Controllers\AuthenticationController@login');
$router->get('/authentication/logout', 'App\Controllers\AuthenticationController@logout');

## Modal home ##
$router->get('/news/:type', 'App\Controllers\Admin\NewsController@getNews',["roles"=>['USER']]);
$router->post('/create-user-news', 'App\Controllers\Admin\NewsController@createUserNews',["roles"=>['USER']]);
$router->get('/news/list/:type', 'App\Controllers\Admin\NewsController@getList',["roles"=>['USER']]);
$router->post('/news/add', 'App\Controllers\Admin\NewsController@add',["roles"=>['USER']]);
$router->get('/news/edit/:id', 'App\Controllers\Admin\NewsController@getElement',["roles"=>['USER']]);
$router->post('/news/update', 'App\Controllers\Admin\NewsController@update',["roles"=>['USER']]);
$router->get('/news/delete/:id', 'App\Controllers\Admin\NewsController@delete',["roles"=>['USER']]);

$router->post('/search', 'App\Controllers\SearchController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->post('/search/anchors', 'App\Controllers\SearchController@getAnchors', ["roles"=>['USER'],"provider"=>["HLK"]]);

## Contract ###
$router->get('/contracts/show/:code', 'App\Controllers\ContractController@show', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->get('/contracts/search/:q', 'App\Controllers\ContractController@searchFor', ["roles"=>['USER'],"provider"=>["HLK"]]);

### Dauphins ###
$router->get('/dauphins/show/:code', 'App\Controllers\DauphinController@show', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->get('/dauphins/search/:code', 'App\Controllers\DauphinController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);

### Items ###
$router->get('/items/show/:code', 'App\Controllers\ItemController@show', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->get('/items/search/:code', 'App\Controllers\ItemController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);

## Products ##
$router->get('/products/show/:code', 'App\Controllers\ProductController@show', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->get('/products/search/:code', 'App\Controllers\ProductController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);

### providers ###
$router->get('/providers/show/:code', 'App\Controllers\ProviderController@show', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->get('/providers/search/:code', 'App\Controllers\ProviderController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);
$router->post('/providers/add', 'App\Controllers\ProviderController@add', ["roles"=>['ADMIN']]);
$router->post('/providers/remove', 'App\Controllers\ProviderController@remove', ["roles"=>['ADMIN']]);


$router->post('/sheets', 'App\Controllers\SheetController@index', ["roles"=>['USER']]);
$router->get('/sheets/show/:id', 'App\Controllers\SheetController@show', ["roles"=>['USER']]);
$router->post('/sheets/export', 'App\Controllers\SheetController@export', ["roles"=>['USER']]);
$router->post('/sheets/add', 'App\Controllers\SheetController@add', ["roles"=>['ADMIN']]);
$router->post('/sheets/save', 'App\Controllers\SheetController@update', ["roles"=>['ADMIN']]);
$router->post('/sheets/download', 'App\Controllers\SheetController@download', ["roles"=>['USER']]);
$router->get('/sheets/remove/:id', 'App\Controllers\SheetController@remove', ["roles"=>['ADMIN']]);
$router->post('/sheets/subscribe', 'App\Controllers\SheetController@subscribe', ["roles"=>['USER']]);
$router->post('/sheets/unsubscribe', 'App\Controllers\SheetController@unsubscribe', ["roles"=>['USER']]);
$router->post('/sheets/filter', 'App\Controllers\SheetController@filter', ["roles"=>['ADMIN']]);


$router->get('/committees', 'App\Controllers\CommitteeController@index', ["roles"=>['USER']]);
$router->get('/providers/autocomplete/:word', 'App\Controllers\ProviderController@autocomplete', ["roles"=>['USER']]);
$router->post('/providers/search', 'App\Controllers\ProviderController@search', ["roles"=>['USER']]);

$router->get('/beneficiaries/autocomplete/:word', 'App\Controllers\BeneficiaryController@autocomplete', ["roles"=>['USER']]);
$router->post('/beneficiaries/search', 'App\Controllers\BeneficiaryController@search', ["roles"=>['USER']]);
$router->post('/beneficiaries/add', 'App\Controllers\BeneficiaryController@add', ["roles"=>['ADMIN']]);
$router->get('/beneficiaries/remove/:id', 'App\Controllers\BeneficiaryController@remove', ["roles"=>['ADMIN']]);


$router->get('/areas/autocomplete/:word', 'App\Controllers\AreaController@autocomplete', ["roles"=>['USER']]);
$router->post('/areas/search', 'App\Controllers\AreaController@search', ["roles"=>['USER']]);
$router->post('/areas/add', 'App\Controllers\AreaController@add', ["roles"=>['ADMIN']]);

$router->get('/groups/autocomplete/:word', 'App\Controllers\GroupController@autocomplete', ["roles"=>['USER']]);
$router->post('/groups/search', 'App\Controllers\GroupController@search', ["roles"=>['USER']]);
$router->post('/groups/add', 'App\Controllers\GroupController@add', ["roles"=>['ADMIN']]);
// affichage de se documenter dans l'accueil ----
$router->get('/documentations', 'App\Controllers\DocumentationController@index', ["roles"=>['USER']]);
// Hotlines pos
$router->post('/hotlines', 'App\Controllers\HotlineController@index', ["roles"=>['USER']]);
$router->get('/hotlines/configuration', 'App\Controllers\HotlineController@configuration', ["roles"=>['USER']]);
$router->get('/hotlines/get/:id/sha/:sha', 'App\Controllers\HotlineController@show', ["roles"=>['USER']]);
$router->post('/hotlines/create', 'App\Controllers\HotlineController@create', ["roles"=>['USER']]);

$router->post('/hotlines/forward', 'App\Controllers\HotlineActionController@forward', ["roles"=>['USER']]);
$router->post('/hotlines/post', 'App\Controllers\HotlineActionController@post', ["roles"=>['USER']]);
$router->post('/hotlines/upload', 'App\Controllers\HotlineActionController@upload', ["roles"=>['USER']]);
$router->post('/hotlines/close', 'App\Controllers\HotlineActionController@close', ["roles"=>['USER']]);

// Gestion de se documenter coté admin ... 
$router->get('/documentations/edit', 'App\Controllers\Admin\DocumentationController@edit', ["roles"=>['ADMIN']]);
$router->post('/documentations/add', 'App\Controllers\Admin\DocumentationController@add', ["roles"=>['ADMIN']]);
$router->get('/documentations/delete/:id', 'App\Controllers\Admin\DocumentationController@delete', ["roles"=>['ADMIN']]);
$router->get('/documentations/show/:id', 'App\Controllers\Admin\DocumentationController@show', ["roles"=>['ADMIN']]);
$router->post('/documentations/update', 'App\Controllers\Admin\DocumentationController@update', ["roles"=>['ADMIN']]);
$router->post('/documentations/sort', 'App\Controllers\Admin\DocumentationController@sort', ["roles"=>['ADMIN']]);
$router->get('/documentations/export', 'App\Controllers\Admin\DocumentationController@export', ["roles"=>['ADMIN']]);
$router->post('/documentations/import', 'App\Controllers\Admin\DocumentationController@import', ["roles"=>['ADMIN']]);
// Gestion des admins
$router->get('/administrators', 'App\Controllers\Admin\AdministratorController@index', ["roles"=>['ADMIN']]);
$router->post('/administrators/create', 'App\Controllers\Admin\AdministratorController@create', ["roles"=>['ADMIN']]);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ["roles"=>['ADMIN']]);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ["roles"=>['ADMIN']]);
// Externals
$router->get('/externals', 'App\Controllers\Admin\ExternalController@index', ["roles"=>['ADMIN']]);
$router->post('/externals/create', 'App\Controllers\Admin\ExternalController@create', ["roles"=>['ADMIN']]);
$router->get('/externals/delete/:email', 'App\Controllers\Admin\ExternalController@delete', ["roles"=>['ADMIN']]);

$router->get('/hotline-themes', 'App\Controllers\Admin\Hotline\HotlineThemeController@index', ["roles"=>['ADMIN']]);
$router->post('/hotline-themes/save', 'App\Controllers\Admin\Hotline\HotlineThemeController@update', ["roles"=>['ADMIN']]);

$router->get('/hotline-groups', 'App\Controllers\Admin\Hotline\HotlineGroupController@index', ["roles"=>['ADMIN']]);
$router->post('/hotline-groups/save', 'App\Controllers\Admin\Hotline\HotlineGroupController@update', ["roles"=>['ADMIN']]);

$router->get('/hotline-emails', 'App\Controllers\Admin\Hotline\HotlineEmailController@index', ["roles"=>['ADMIN']]);
$router->post('/hotline-emails/save', 'App\Controllers\Admin\Hotline\HotlineEmailController@update', ["roles"=>['ADMIN']]);
$router->post('/hotline-emails/living-save', 'App\Controllers\Admin\Hotline\HotlineEmailController@livingUpdate', ["roles"=>['ADMIN']]);

$router->post('/import/upload', 'App\Controllers\Admin\ImportController@upload', ["roles"=>['ADMIN']]);

$router->post('/migration/save', 'App\Controllers\Admin\MigrateController@saveData', ["roles"=>['ADMIN']]);
$router->post('/migration/migrate', 'App\Controllers\Admin\MigrateController@migrate', ["roles"=>['ADMIN']]);
$router->post('/migration/list', 'App\Controllers\Admin\MigrateController@index', ["roles"=>['ADMIN']]);

$router->post('/purchases/search', 'App\Controllers\PurchaseController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);


$router->get('/pages/get/:code', 'App\Controllers\PageController@get', ["roles"=>['USER']]);
$router->post('/pages/edit/:code', 'App\Controllers\PageController@update', ["roles"=>['ADMIN']]);

$router->get('/informations/get/:type', 'App\Controllers\InformationController@get', ["roles"=>['USER']]);
$router->get('/informations/edit/:id', 'App\Controllers\InformationController@getElement', ["roles"=>['USER']]);
$router->get('/informations/list/:type', 'App\Controllers\InformationController@getList', ["roles"=>['USER']]);
$router->post('/informations/save/:id', 'App\Controllers\InformationController@update', ["roles"=>['USER']]);
$router->post('/informations/add/:type', 'App\Controllers\InformationController@add', ["roles"=>['USER']]);
$router->get('/informations/delete/:id', 'App\Controllers\InformationController@delete',["roles"=>['USER']]);



//try {
$data = $router->run();
if (is_string($data) && is_array(json_decode($data, true)) && (json_last_error() == JSON_ERROR_NONE)):
    echo $data;
endif ;
#le try catch à été supprimé car Les détails exposés sur l'environnement de l'application, les utilisateurs ou les données associées (par exemple,
#la trace de la pile) pourraient permettre à un attaquant de trouver une autre faille et l'aider à lancer une attaque. Cela peut également divulguer des données sensibles, par ex.
#mots de passe ou champs de base de données. 
