<?php
$this->app->group(['prefix' => 'api'], function ($router) {
    $router->get('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@get',
    ]);
    $router->get('{entity}/{id}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@show',
    ]);
    $router->post('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@store',
    ]);
    $router->put('{entity}/{id}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@update',
    ]);
    $router->patch('{entity}/{id}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@patch',
    ]);
    $router->delete('{entity}/{id}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@destroy',
    ]);
});
