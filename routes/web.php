<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'command'], static function () use ($router) {
    $router->post('etalase/store', 'EtalaseController@store');
    $router->delete('etalase/delete/{id}', 'EtalaseController@delete');
});

$router->group(['prefix' => 'query'], static function () use ($router) {
    $router->get('etalase', 'EtalaseController@index');
    $router->get('etalase/show/{id}', 'EtalaseController@show');
});