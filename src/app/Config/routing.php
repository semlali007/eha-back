<?php
$router->post('/purchases/list', 'App\Controllers\PurchaseController@list',["roles"=>['USER']]);
$router->get('/notifications/list/:nni', 'App\Controllers\NotificationsController@index');
$router->post('/notifications/update', 'App\Controllers\NotificationsController@update');
$router->post('/notifications/add', 'App\Controllers\NotificationsController@add');
$router->post('/notifications/update-all', 'App\Controllers\NotificationsController@updateList');

$router->get('/api/monitoring/health_check', 'App\Controllers\HealthcheckController@index');
$router->post('/authentication/login', 'App\Controllers\AuthenticationController@login');
$router->get('/authentication/logout', 'App\Controllers\AuthenticationController@logout');

## Role verification endpoint for Angular guards ##
$router->get('/roles/check/:roleName', 'App\Controllers\RoleController@checkRole', ["roles"=>['USER']]);

## Modal home ##
$router->get('/news/:type', 'App\Controllers\Admin\NewsController@getNews',["roles"=>['ADMIN']]);
$router->post('/create-user-news', 'App\Controllers\Admin\NewsController@createUserNews',["roles"=>['ADMIN']]);
$router->get('/news/list/:type', 'App\Controllers\Admin\NewsController@getList',["roles"=>['ADMIN']]);
$router->post('/news/add', 'App\Controllers\Admin\NewsController@add',["roles"=>['ADMIN']]);
$router->get('/news/edit/:id', 'App\Controllers\Admin\NewsController@getElement',["roles"=>['ADMIN']]);
$router->post('/news/update', 'App\Controllers\Admin\NewsController@update',["roles"=>['ADMIN']]);
$router->get('/news/delete/:id', 'App\Controllers\Admin\NewsController@delete',["roles"=>['ADMIN']]);

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
$router->post('/providers/update', 'App\Controllers\ProviderController@update', ["roles"=>['USER']]);
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
$router->get('/supplier-sizes', 'App\Controllers\SupplierSizeController@index', ["roles"=>['USER']]);
$router->get('/providers/autocomplete/:word', 'App\Controllers\ProviderController@autocomplete', ["roles"=>['USER']]);
$router->post('/providers/search', 'App\Controllers\ProviderController@search', ["roles"=>['USER']]);

$router->get('/beneficiaries/autocomplete/:word', 'App\Controllers\BeneficiaryController@autocomplete', ["roles"=>['USER']]);
$router->post('/beneficiaries/search', 'App\Controllers\BeneficiaryController@search', ["roles"=>['USER']]);
$router->post('/beneficiaries/add', 'App\Controllers\BeneficiaryController@add', ["roles"=>['USER']]);
$router->get('/beneficiaries/remove/:id', 'App\Controllers\BeneficiaryController@remove', ["roles"=>['USER']]);

$router->get('/stipulationAutrui/autocomplete/:word', 'App\Controllers\StipulationController@autocomplete', ["roles"=>['USER']]);
$router->post('/stipulationAutrui/search', 'App\Controllers\StipulationController@search', ["roles"=>['USER']]);
$router->post('/stipulationAutrui/add', 'App\Controllers\StipulationController@add', ["roles"=>['USER']]);
$router->get('/stipulationAutrui/remove/:id', 'App\Controllers\StipulationController@remove', ["roles"=>['USER']]);

$router->get('/areas', 'App\Controllers\AreaController@getList', ["roles"=>['USER']]);
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
$router->get('/documentations/edit', 'App\Controllers\Admin\DocumentationController@edit', ["roles"=>['ADMIN']]);
$router->post('/documentations/add', 'App\Controllers\Admin\DocumentationController@add', ["roles"=>['ADMIN']]);
$router->get('/documentations/delete/:id', 'App\Controllers\Admin\DocumentationController@delete', ["roles"=>['ADMIN']]);
$router->get('/documentations/show/:id', 'App\Controllers\Admin\DocumentationController@show', ["roles"=>['ADMIN']]);
$router->post('/documentations/update', 'App\Controllers\Admin\DocumentationController@update', ["roles"=>['ADMIN']]);
$router->post('/documentations/sort', 'App\Controllers\Admin\DocumentationController@sort', ["roles"=>['ADMIN']]);
$router->get('/documentations/export', 'App\Controllers\Admin\DocumentationController@export', ["roles"=>['ADMIN']]);
$router->post('/documentations/import', 'App\Controllers\Admin\DocumentationController@import', ["roles"=>['ADMIN']]);
// Gestion des USERs
$router->get('/administrators', 'App\Controllers\Admin\AdministratorController@index', ["roles"=>['ADMIN']]);
$router->post('/administrators/create', 'App\Controllers\Admin\AdministratorController@create', ["roles"=>['ADMIN']]);
$router->get('/administrators/delete/:email', 'App\Controllers\Admin\AdministratorController@delete', ["roles"=>['ADMIN']]);
// Externals
$router->get('/externals', 'App\Controllers\Admin\ExternalController@index', ["roles"=>['ADMIN']]);
$router->post('/externals/create', 'App\Controllers\Admin\ExternalController@create', ["roles"=>['ADMIN']]);
$router->get('/externals/delete/:email', 'App\Controllers\Admin\ExternalController@delete', ["roles"=>['ADMIN']]);
$router->get('/hotline-themes', 'App\Controllers\Admin\Hotline\HotlineThemeController@index', ["roles"=>['ADMIN']]);
$router->post('/hotline-themes/save', 'App\Controllers\Admin\Hotline\HotlineThemeController@update', ["roles"=>['ADMIN']]);
$router->post('/hotline-themes/add', 'App\Controllers\Admin\Hotline\HotlineThemeController@add', ["roles"=>['ADMIN']]);
$router->get('/hotline-themes/edit/:id', 'App\Controllers\Admin\Hotline\HotlineThemeController@edit', ["roles"=>['ADMIN']]);
$router->get('/hotline-themes/delete/:id', 'App\Controllers\Admin\Hotline\HotlineThemeController@delete', ["roles"=>['ADMIN']]);


$router->get('/hotline-groups', 'App\Controllers\Admin\Hotline\HotlineGroupController@index', ["roles"=>['ADMIN']]);
$router->post('/hotline-groups/save', 'App\Controllers\Admin\Hotline\HotlineGroupController@update', ["roles"=>['ADMIN']]);
//***
$router->post('/hotline-groups/add', 'App\Controllers\Admin\Hotline\HotlineGroupController@add', ["roles"=>['ADMIN']]);
$router->get('/hotline-groups/edit/:id', 'App\Controllers\Admin\Hotline\HotlineGroupController@edit', ["roles"=>['ADMIN']]);
$router->get('/hotline-groups/delete/:id', 'App\Controllers\Admin\Hotline\HotlineGroupController@delete', ["roles"=>['ADMIN']]);


$router->get('/hotline-emails', 'App\Controllers\Admin\Hotline\HotlineEmailController@index', ["roles"=>['ADMIN']]);
$router->post('/hotline-emails/save', 'App\Controllers\Admin\Hotline\HotlineEmailController@update', ["roles"=>['ADMIN']]);
$router->post('/hotline-emails/add', 'App\Controllers\Admin\Hotline\HotlineEmailController@add', ["roles"=>['ADMIN']]);
$router->post('/hotline-emails/living-save', 'App\Controllers\Admin\Hotline\HotlineEmailController@livingUpdate', ["roles"=>['ADMIN']]);

$router->post('/import/upload', 'App\Controllers\Admin\ImportController@upload', ["roles"=>['ADMIN']]);

$router->post('/migration/save', 'App\Controllers\Admin\MigrateController@saveData', ["roles"=>['ADMIN']]);
$router->post('/migration/migrate', 'App\Controllers\Admin\MigrateController@migrate', ["roles"=>['ADMIN']]);
$router->post('/migration/list', 'App\Controllers\Admin\MigrateController@index', ["roles"=>['ADMIN']]);

// HLK — sync bénéficiaires (bouton admin, endpoint synchrone, ~1-2 min)
$router->post('/admin/hlk/sync-beneficiaries', 'App\Controllers\Admin\Hlk\HlkSyncController@syncBeneficiaries', ["roles"=>['ADMIN']]);

// HLK — auto-import fiches marché : trigger async (fire-and-forget)
// Body optionnel : { signed_since: "YYYY-MM-DD", dry_run: true|false }
$router->post('/admin/hlk/import-sheets/trigger', 'App\Controllers\Admin\Hlk\HlkTriggerController@triggerImportSheets', ["roles"=>['ADMIN']]);

// HLK — historique des runs sync (écran admin, lecture seule)
$router->get('/admin/hlk/sync-runs',            'App\Controllers\Admin\Hlk\HlkHistoryController@listRuns',        ["roles"=>['ADMIN']]);
$router->get('/admin/hlk/sync-runs/:id',        'App\Controllers\Admin\Hlk\HlkHistoryController@runDetail',       ["roles"=>['ADMIN']]);
$router->get('/admin/hlk/created-sheets',       'App\Controllers\Admin\Hlk\HlkHistoryController@listCreatedSheets',["roles"=>['ADMIN']]);

$router->post('/purchases/search', 'App\Controllers\PurchaseController@search', ["roles"=>['USER'],"provider"=>["HLK"]]);


$router->get('/pages/get/:code', 'App\Controllers\PageController@get', ["roles"=>['USER']]);
$router->post('/pages/edit/:code', 'App\Controllers\PageController@update', ["roles"=>['ADMIN']]);

$router->get('/informations/get/:type', 'App\Controllers\InformationController@get', ["roles"=>['ADMIN']]);
$router->get('/informations/edit/:id', 'App\Controllers\InformationController@getElement', ["roles"=>['ADMIN']]);
$router->get('/informations/list/:type', 'App\Controllers\InformationController@getList', ["roles"=>['ADMIN']]);
$router->post('/informations/save/:id', 'App\Controllers\InformationController@update', ["roles"=>['ADMIN']]);
$router->post('/informations/add/:type', 'App\Controllers\InformationController@add', ["roles"=>['ADMIN']]);
$router->get('/informations/delete/:id', 'App\Controllers\InformationController@delete',["roles"=>['ADMIN']]);

$router->get('/factsheets/list', 'App\Controllers\FactsheetController@getList', ["roles"=>['ADMIN']]);
$router->post('/factsheets/download', 'App\Controllers\FactsheetController@download', ["roles"=>['ADMIN']]);
$router->post('/factsheets/upload', 'App\Controllers\FactsheetController@upload', ["roles"=>['ADMIN']]);
$router->get('/factsheets/edit/:id', 'App\Controllers\FactsheetController@edit', ["roles"=>['ADMIN']]);
$router->post('/factsheets/save/:id', 'App\Controllers\FactsheetController@save', ["roles"=>['ADMIN']]);
$router->get('/factsheets/delete/:id', 'App\Controllers\FactsheetController@delete', ["roles"=>['ADMIN']]);

$router->get('/domains/autocomplete/:word', 'App\Controllers\DomainController@autocomplete', ["roles"=>['USER']]);
$router->post('/domains/search', 'App\Controllers\DomainController@search', ["roles"=>['USER']]);
$router->post('/domains/add', 'App\Controllers\DomainController@add', ["roles"=>['USER']]);

$router->get('/subdomains/autocomplete/:word', 'App\Controllers\SubDomainController@autocomplete', ["roles"=>['USER']]);
$router->post('/subdomains/search', 'App\Controllers\SubDomainController@search', ["roles"=>['USER']]);
$router->post('/subdomains/add', 'App\Controllers\SubDomainController@add', ["roles"=>['USER']]);
$router->get('/admin/recherche/logs', 'App\Controllers\SearchConfigController@getLogsList', ["roles"=>['ADMIN']]);
$router->get('/admin/recherche/ignored/list', 'App\Controllers\SearchConfigController@getIgnoredList', ["roles"=>['ADMIN']]);
$router->get('/admin/recherche/associated/list', 'App\Controllers\SearchConfigController@getAssociatedList', ["roles"=>['ADMIN']]);
$router->post('/admin/recherche/ignored/add', 'App\Controllers\SearchConfigController@addIgnored', ["roles"=>['ADMIN']]);
$router->post('/admin/recherche/associated/add', 'App\Controllers\SearchConfigController@addAssociated', ["roles"=>['ADMIN']]);
$router->get('/admin/recherche/ignored/delete/:id', 'App\Controllers\SearchConfigController@deleteIgnored', ["roles"=>['ADMIN']]);
$router->get('/admin/recherche/words/list', 'App\Controllers\SearchConfigController@wordslist', ["roles"=>['ADMIN']]);
$router->post('/admin/recherche/synonymous/add', 'App\Controllers\SearchConfigController@addSynonymous', ["roles"=>['ADMIN']]);
$router->get('/admin/recherche/synonymous/delete/:id', 'App\Controllers\SearchConfigController@deleteSynonymous', ["roles"=>['ADMIN']]);
$router->post('/admin/recherche/associated/update/:id', 'App\Controllers\SearchConfigController@updateAssociated', ["roles"=>['ADMIN']]);
$router->get('/admin/recherche/associated/delete/:id', 'App\Controllers\SearchConfigController@deleteAssociated', ["roles"=>['ADMIN']]);

$router->post('/admin/news-cast/list', 'App\Controllers\NewsCastController@list', ["roles" => ['ADMIN']]);
$router->get('/admin/news-cast/categories', 'App\Controllers\NewsCastController@getCategories', ["roles" => ['ADMIN']]);
$router->post('/admin/news-cast/create', 'App\Controllers\NewsCastController@create', ["roles" => ['ADMIN']]);
$router->post('/admin/news-cast/update', 'App\Controllers\NewsCastController@update', ["roles" => ['ADMIN']]);
$router->get('/admin/news-cast/get/:id', 'App\Controllers\NewsCastController@getNews', ["roles" => ['ADMIN']]);
$router->post('/admin/news-cast/sort', 'App\Controllers\NewsCastController@sort', ["roles"=>['ADMIN']]);
$router->post('/admin/news-cast/delete', 'App\Controllers\NewsCastController@delete', ["roles"=>['ADMIN']]);

$router->post('/news-cast/list', 'App\Controllers\NewsCastController@frontList', ["roles" => ['USER']]);
$router->get('/news-cast/get/:id', 'App\Controllers\NewsCastController@frontGetNews', ["roles" => ['USER']]);
$router->get('/news-cast/categories', 'App\Controllers\NewsCastController@frontGetCategories', ["roles" => ['USER']]);

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
$router->post('/satisfaction/create', 'App\Controllers\SatisfactionController@create', ["roles"=>['USER']]);
$router->get('/satisfaction/get/:nni/:cockpit_id', 'App\Controllers\SatisfactionController@get', ["roles"=>['USER']]);
$router->get('/satisfaction/list', 'App\Controllers\SatisfactionController@list', ["roles"=>['USER']]);

// ============================================
// Mappings Organisation → Rôles
// ============================================
$router->get('/org-role-mappings', 'App\Controllers\OrgRoleMappingController@index', ["roles" => ['USER']]);
$router->get('/org-role-mappings/:orgCode', 'App\Controllers\OrgRoleMappingController@getByOrganization', ["roles" => ['USER']]);

//
$router->post('/auth', 'App\Controllers\AuthController@authenticate', ["roles"=>['USER']]);

// API Référentiels - Endpoints pour récupérer les listes de référence
$router->get('/cockpit/categories', 'App\Controllers\ApiReferentialController@getCategories');
$router->get('/cockpit/committees', 'App\Controllers\ApiReferentialController@getCommittees');
$router->get('/cockpit/providers', 'App\Controllers\ApiReferentialController@getProviders');
$router->get('/cockpit/beneficiaries', 'App\Controllers\ApiReferentialController@getBeneficiaries');
$router->get('/cockpit/stipulations', 'App\Controllers\ApiReferentialController@getStipulations');
$router->get('/cockpit/areas', 'App\Controllers\ApiReferentialController@getAreas');
$router->get('/cockpit/referentials', 'App\Controllers\ApiReferentialController@getAllReferentials');

// API Cockpit - Création directe de fiches sans token
$router->post('/cockpit/sheets/add', 'App\Controllers\SheetController@addViaCockpit');
$router->post('/cockpit/auth/login', 'App\Controllers\SheetController@login');


//try {
$data = $router->run();
if (is_string($data) && is_array(json_decode($data, true)) && (json_last_error() == JSON_ERROR_NONE)):
    echo $data;
endif ;
#le try catch à été supprimé car Les détails exposés sur l'environnement de l'application, les utilisateurs ou les données associées (par exemple,
#la trace de la pile) pourraient permettre à un attaquant de trouver une autre faille et l'aider à lancer une attaque. Cela peut également divulguer des données sensibles, par ex.
#mots de passe ou champs de base de données. 
