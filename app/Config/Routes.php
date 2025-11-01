<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->options('api/(:any)', function($path){
    return service('response')
        ->setStatusCode(200)
        ->setHeader('Access-Control-Allow-Origin', '*') // ou seu domínio permitido
        ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->send();
});

$routes->group('api', ['namespace' => 'App\Controllers\api'], function($routes) {
    $routes->resource('auth', ['controller' => 'Auth', 'filter' => 'jwt']);
    $routes->post('auth/login', 'Auth::login');
    $routes->post('auth/logout', 'Auth::logout');

    $routes->get('network/tree', 'Network::tree', ['filter' => 'jwt']);          // toda a árvore
    $routes->get('network/tree/(:num)', 'Network::tree/$1', ['filter' => 'jwt']); // subárvore do id

    $routes->get('invite/generate/(:num)', 'InviteController::generate/$1', ['filter' => 'jwt']);
    $routes->post('invite/accept', 'InviteController::accept');
    $routes->post('invite/campaign', 'InviteController::getModelCampaign');

    $routes->resource('campaigns', ['controller' => 'CampaignController', 'filter' => 'jwt']);
    $routes->post('campaigns/update/(:num)', 'CampaignController::update/$1', ['filter' => 'jwt']);
});

// // Example protected routes using the 'jwt' filter
// $routes->group('api', ['namespace' => 'App\Controllers\api'], function($routes) {
//     // Protect a single route
//     $routes->get('secure', 'Home::index', ['filter' => 'jwt']);

//     // Protect a group of routes
//     $routes->group('private', ['filter' => 'jwt'], function($routes) {
//         $routes->get('profile', 'Auth::show');
//         // add more protected routes here
//     });
// });


