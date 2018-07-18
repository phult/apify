<?php
$prefix = env('APIFY_PREFIX_URL', 'api');
$this->app->group(['prefix' => $prefix], function ($router) {
    $router->post('upload', [
        'uses' => 'Megaads\Apify\Controllers\APIController@upload',
    ]);
    $router->get('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@get',
    ]);
    $router->get('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@show',
    ]);
    $router->post('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@store',
    ]);
    $router->put('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@update',
    ]);
    $router->patch('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@patch',
    ]);
    $router->delete('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@destroy',
    ]);
    $router->delete('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@destroyBulk',
    ]);
});
