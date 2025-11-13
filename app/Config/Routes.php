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
    $routes->post('network/treeCampaign', 'Network::getCampaignUser');

    $routes->get('invite/generate/(:num)', 'InviteController::generate/$1', ['filter' => 'jwt']);
    $routes->post('invite/accept', 'InviteController::accept');
    $routes->post('invite/campaign', 'InviteController::getModelCampaign');

    $routes->resource('campaigns', ['controller' => 'CampaignController', 'filter' => 'jwt']);
    $routes->post('campaigns/update/(:num)', 'CampaignController::update/$1', ['filter' => 'jwt']);

    // routes search users with email, this method will used in forgot password
    $routes->post('users/searchByEmail', 'Auth::searchWithEmail');
    $routes->post('users/forgotPassword/(:num)', 'Auth::forgotPassword/$1');

    // routes events
    $routes->resource('events', ['controller' => 'EventsController', 'filter' => 'jwt']);

    // routes notices
    $routes->resource('notices', ['controller' => 'NoticeController', 'filter' => 'jwt']);
    $routes->post('notices/update/(:num)', 'NoticeController::update/$1', ['filter' => 'jwt']);

    // routes comments
    // $routes->post('comments' , 'CommentController::create', ['filter' => 'jwt']);
    $routes->post('comments', 'CommentController::create', ['filter' => 'jwt']);
    $routes->get('comments/notice/(:num)', 'CommentController::byNotice/$1', ['filter' => 'jwt']);

    // routes likes
    $routes->post('likes/toggle', 'LikeController::toggle', ['filter' => 'jwt']);
    $routes->get('likes/notice/(:num)', 'LikeController::likesByNotice/$1', ['filter' => 'jwt']);
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


