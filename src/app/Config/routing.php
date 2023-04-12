<?php
$router->post('/purchases/list', 'App\Controllers\PurchaseController@list',["roles"=>['USER']]);
$router->get('/notifications/list/:nni', 'App\Controllers\NotificationsController@index');
$router->post('/notifications/update', 'App\Controllers\NotificationsController@update');
$router->post('/notifications/add', 'App\Controllers\NotificationsController@add');
$router->post('/notifications/update-all', 'App\Controllers\NotificationsController@updateList');

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
$router->post('/providers/add', 'App\Controllers\ProviderController@add', ["roles"=>['USER']]);
$router->post('/providers/remove', 'App\Controllers\ProviderController@remove', ["roles"=>['USER']]);


$router->post('/sheets', 'App\Controllers\SheetController@index', ["roles"=>['USER']]);
$router->get('/sheets/show/:id', 'App\Controllers\SheetController@show', ["roles"=>['USER']]);
$router->post('/sheets/export', 'App\Controllers\SheetController@export', ["roles"=>['USER']]);
$router->post('/sheets/add', 'App\Controllers\SheetController@add', ["roles"=>['USER']]);
$router->post('/sheets/save', 'App\Controllers\SheetController@update', ["roles"=>['USER']]);
$router->post('/sheets/addDraft', 'App\Controllers\SheetController@addDraft', ["roles"=>['USER']]);
$router->post('/sheets/saveDraft', 'App\Controllers\SheetController@updateDraft', ["roles"=>['USER']]);
$router->post('/sheets/download', 'App\Controllers\SheetController@download', ["roles"=>['USER']]);
$router->get('/sheets/remove/:id', 'App\Controllers\SheetController@remove', ["roles"=>['USER']]);
$router->post('/sheets/subscribe', 'App\Controllers\SheetController@subscribe', ["roles"=>['USER']]);
$router->post('/sheets/unsubscribe', 'App\Controllers\SheetController@unsubscribe', ["roles"=>['USER']]);
$router->post('/sheets/filter', 'App\Controllers\SheetController@filter', ["roles"=>['USER']]);


$router->get('/committees', 'App\Controllers\CommitteeController@index', ["roles"=>['USER']]);
$router->get('/providers/autocomplete/:word', 'App\Controllers\ProviderController@autocomplete', ["roles"=>['USER']]);
$router->post('/providers/search', 'App\Controllers\ProviderController@search', ["roles"=>['USER']]);

$router->get('/beneficiaries/autocomplete/:word', 'App\Controllers\BeneficiaryController@autocomplete', ["roles"=>['USER']]);
$router->post('/beneficiaries/search', 'App\Controllers\BeneficiaryController@search', ["roles"=>['USER']]);
$router->post('/beneficiaries/add', 'App\Controllers\BeneficiaryController@add', ["roles"=>['USER']]);
$router->get('/beneficiaries/remove/:id', 'App\Controllers\BeneficiaryController@remove', ["roles"=>['USER']]);


$router->get('/areas/autocomplete/:word', 'App\Controllers\AreaController@autocomplete', ["roles"=>['USER']]);
$router->post('/areas/search', 'App\Controllers\AreaController@search', ["roles"=>['USER']]);
$router->post('/areas/add', 'App\Controllers\AreaController@add', ["roles"=>['USER']]);

$router->get('/groups/autocomplete/:word', 'App\Controllers\GroupController@autocomplete', ["roles"=>['USER']]);
$router->post('/groups/search', 'App\Controllers\GroupController@search', ["roles"=>['USER']]);
$router->post('/groups/add', 'App\Controllers\GroupController@add', ["roles"=>['USER']]);
// affichage de se documenter dans l'accueil ----
$router->get('/documentations', 'App\Controllers\DocumentationController@index', ["roles"=>['USER']]);
// Hotlines pos
$router->post('/hotlines', 'App\Controllers\HotlineController@index', ["roles"=>['USER']]);
$router->get('/hotlines/configuration', 'App\Controllers\HotlineController@configuration', ["roles"=>['USER']]);
$router->get('/hotlines/get/:id/sha/:sha', 'App\Controllers\HotlineController@show', ["roles"=>['USER']]);
$router->post('/hotlines/create', 'App\Controllers\HotlineController@create', ["roles"=>['USER']]);
$router->get('/hotlines/get/:id', 'App\Controllers\HotlineController@show', ["roles"=>['USER']]);
$router->post('/hotlines/list', 'App\Controllers\HotlineController@getList', ["roles"=>['USER']]);

$router->post('/hotlines/forward', 'App\Controllers\HotlineActionController@forward', ["roles"=>['USER']]);
$router->post('/hotlines/post', 'App\Controllers\HotlineActionController@post', ["roles"=>['USER']]);
$router->post('/hotlines/upload', 'App\Controllers\HotlineActionController@upload', ["roles"=>['USER']]);
$router->post('/hotlines/close', 'App\Controllers\HotlineActionController@close', ["roles"=>['USER']]);

// Gestion de se documenter coté USER ... 
$router->get('/documentations/edit', 'App\Controllers\Admin\DocumentationController@edit', ["roles"=>['USER']]);
$router->post('/documentations/add', 'App\Controllers\Admin\DocumentationController@add', ["roles"=>['USER']]);
$router->get('/documentations/delete/:id', 'App\Controllers\Admin\DocumentationController@delete', ["roles"=>['USER']]);
$router->get('/documentations/show/:id', 'App\Controllers\Admin\DocumentationController@show', ["roles"=>['USER']]);
$router->post('/documentations/update', 'App\Controllers\Admin\DocumentationController@update', ["roles"=>['USER']]);
$router->post('/documentations/sort', 'App\Controllers\Admin\DocumentationController@sort', ["roles"=>['USER']]);
$router->get('/documentations/export', 'App\Controllers\Admin\DocumentationController@export', ["roles"=>['USER']]);
$router->post('/documentations/import', 'App\Controllers\Admin\DocumentationController@import', ["roles"=>['USER']]);
// Gestion des USERs
$router->get('/administrators', 'App\Controllers\Admin\AdministratorController@index', ["roles"=>['USER']]);
$router->post('/administrators/create', 'App\Controllers\Admin\AdministratorController@create', ["roles"=>['USER']]);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ["roles"=>['']]);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ["roles"=>['USER']]);
// Externals
$router->get('/externals', 'App\Controllers\Admin\ExternalController@index', ["roles"=>['USER']]);
$router->post('/externals/create', 'App\Controllers\Admin\ExternalController@create', ["roles"=>['USER']]);
$router->get('/externals/delete/:email', 'App\Controllers\Admin\ExternalController@delete', ["roles"=>['USER']]);

$router->get('/hotline-themes', 'App\Controllers\Admin\Hotline\HotlineThemeController@index', ["roles"=>['USER']]);
$router->post('/hotline-themes/save', 'App\Controllers\Admin\Hotline\HotlineThemeController@update', ["roles"=>['USER']]);
$router->post('/hotline-themes/add', 'App\Controllers\Admin\Hotline\HotlineThemeController@add', ["roles"=>['USER']]);
$router->get('/hotline-themes/edit/:id', 'App\Controllers\Admin\Hotline\HotlineThemeController@edit', ["roles"=>['USER']]);
$router->get('/hotline-themes/delete/:id', 'App\Controllers\Admin\Hotline\HotlineThemeController@delete', ["roles"=>['USER']]);


$router->get('/hotline-groups', 'App\Controllers\Admin\Hotline\HotlineGroupController@index', ["roles"=>['USER']]);
$router->post('/hotline-groups/save', 'App\Controllers\Admin\Hotline\HotlineGroupController@update', ["roles"=>['USER']]);

$router->get('/hotline-emails', 'App\Controllers\Admin\Hotline\HotlineEmailController@index', ["roles"=>['USER']]);
$router->post('/hotline-emails/save', 'App\Controllers\Admin\Hotline\HotlineEmailController@update', ["roles"=>['USER']]);
$router->post('/hotline-emails/add', 'App\Controllers\Admin\Hotline\HotlineEmailController@add', ["roles"=>['USER']]);
$router->post('/hotline-emails/living-save', 'App\Controllers\Admin\Hotline\HotlineEmailController@livingUpdate', ["roles"=>['USER']]);

$router->post('/import/upload', 'App\Controllers\Admin\ImportController@upload', ["roles"=>['USER']]);

$router->post('/migration/save', 'App\Controllers\Admin\MigrateController@saveData', ["roles"=>['USER']]);
$router->post('/migration/migrate', 'App\Controllers\Admin\MigrateController@migrate', ["roles"=>['USER']]);
$router->post('/migration/list', 'App\Controllers\Admin\MigrateController@index', ["roles"=>['USER']]);

$router->post('/purchases/search', 'App\Controllers\PurchaseController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);


$router->get('/pages/get/:code', 'App\Controllers\PageController@get', ["roles"=>['USER']]);
$router->post('/pages/edit/:code', 'App\Controllers\PageController@update', ["roles"=>['USER']]);

$router->get('/informations/get/:type', 'App\Controllers\InformationController@get', ["roles"=>['USER']]);
$router->get('/informations/edit/:id', 'App\Controllers\InformationController@getElement', ["roles"=>['USER']]);
$router->get('/informations/list/:type', 'App\Controllers\InformationController@getList', ["roles"=>['USER']]);
$router->post('/informations/save/:id', 'App\Controllers\InformationController@update', ["roles"=>['USER']]);
$router->post('/informations/add/:type', 'App\Controllers\InformationController@add', ["roles"=>['USER']]);
$router->get('/informations/delete/:id', 'App\Controllers\InformationController@delete',["roles"=>['USER']]);

$router->get('/factsheets/list', 'App\Controllers\FactsheetController@getList', ["roles"=>['USER']]);
$router->post('/factsheets/download', 'App\Controllers\FactsheetController@download', ["roles"=>['USER']]);
$router->post('/factsheets/upload', 'App\Controllers\FactsheetController@upload', ["roles"=>['USER']]);
$router->get('/factsheets/edit/:id', 'App\Controllers\FactsheetController@edit', ["roles"=>['USER']]);
$router->post('/factsheets/save/:id', 'App\Controllers\FactsheetController@save', ["roles"=>['USER']]);
$router->get('/factsheets/delete/:id', 'App\Controllers\FactsheetController@delete', ["roles"=>['USER']]);

$router->get('/domains/autocomplete/:word', 'App\Controllers\DomainController@autocomplete', ["roles"=>['USER']]);
$router->post('/domains/search', 'App\Controllers\DomainController@search', ["roles"=>['USER']]);
$router->post('/domains/add', 'App\Controllers\DomainController@add', ["roles"=>['USER']]);

$router->get('/subdomains/autocomplete/:word', 'App\Controllers\SubDomainController@autocomplete', ["roles"=>['USER']]);
$router->post('/subdomains/search', 'App\Controllers\SubDomainController@search', ["roles"=>['USER']]);
$router->post('/subdomains/add', 'App\Controllers\SubDomainController@add', ["roles"=>['USER']]);
$router->get('/admin/recherche/logs', 'App\Controllers\SearchConfigController@getLogsList', ["roles"=>['USER']]);
$router->get('/admin/recherche/ignored/list', 'App\Controllers\SearchConfigController@getIgnoredList', ["roles"=>['USER']]);
$router->get('/admin/recherche/associated/list', 'App\Controllers\SearchConfigController@getAssociatedList', ["roles"=>['USER']]);
$router->post('/admin/recherche/ignored/add', 'App\Controllers\SearchConfigController@addIgnored', ["roles"=>['USER']]);
$router->post('/admin/recherche/associated/add', 'App\Controllers\SearchConfigController@addAssociated', ["roles"=>['USER']]);
$router->get('/admin/recherche/ignored/delete/:id', 'App\Controllers\SearchConfigController@deleteIgnored', ["roles"=>['USER']]);
$router->get('/admin/recherche/words/list', 'App\Controllers\SearchConfigController@wordslist', ["roles"=>['USER']]);
$router->post('/admin/recherche/synonymous/add', 'App\Controllers\SearchConfigController@addSynonymous', ["roles"=>['USER']]);
$router->get('/admin/recherche/synonymous/delete/:id', 'App\Controllers\SearchConfigController@deleteSynonymous', ["roles"=>['USER']]);
$router->post('/admin/recherche/associated/update/:id', 'App\Controllers\SearchConfigController@updateAssociated', ["roles"=>['USER']]);
$router->get('/admin/recherche/associated/delete/:id', 'App\Controllers\SearchConfigController@deleteAssociated', ["roles"=>['USER']]);

$router->post('/admin/news-cast/list', 'App\Controllers\NewsCastController@list', ["roles" => ['USER']]);

$router->get('/admin/news-cast/categories', 'App\Controllers\NewsCastController@getCategories', ["roles" => ['ADMIN']]);
$router->post('/admin/news-cast/create', 'App\Controllers\NewsCastController@create', ["roles" => ['ADMIN']]);
$router->post('/admin/news-cast/update', 'App\Controllers\NewsCastController@update', ["roles" => ['ADMIN']]);
$router->get('/admin/news-cast/get/:id', 'App\Controllers\NewsCastController@getNews', ["roles" => ['USER']]);
$router->post('/admin/news-cast/sort', 'App\Controllers\NewsCastController@sort', ["roles"=>['USER']]);
$router->post('/admin/news-cast/delete', 'App\Controllers\NewsCastController@delete', ["roles"=>['USER']]);

$router->post('/news-cast/list', 'App\Controllers\NewsCastController@frontList', ["roles" => ['USER']]);

$router->post('/purchases-situation/list', 'App\Controllers\PurchaseController@listV2',["roles"=>['USER']]);

$router->post('/sheets/history', 'App\Controllers\SheetController@history', ["roles" => ['USER']]);
// User management
$router->get('/saml/login', 'App\Controllers\AuthenticationController@login');
$router->get('/saml/login/:url', 'App\Controllers\AuthenticationController@login');

$router->get('/saml/logout', 'App\Controllers\AuthenticationController@logout');
$router->get('/saml/attributes', 'App\Controllers\AuthenticationController@attributes');

// Log Front informations
$router->post('/front/logs', 'App\Controllers\FrontController@logs');

$router->get('/categories', 'App\Controllers\CategoryController@index', ["roles"=>['USER']]);

//try {
$data = $router->run();
if (is_string($data) && is_array(json_decode($data, true)) && (json_last_error() == JSON_ERROR_NONE)):
    echo $data;
endif ;
#le try catch à été supprimé car Les détails exposés sur l'environnement de l'application, les utilisateurs ou les données associées (par exemple,
#la trace de la pile) pourraient permettre à un attaquant de trouver une autre faille et l'aider à lancer une attaque. Cela peut également divulguer des données sensibles, par ex.
#mots de passe ou champs de base de données. 
