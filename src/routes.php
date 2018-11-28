<?php
$prefix = env('APIFY_PREFIX_URL', 'api');
Route::group(['prefix' => $prefix], function() {
    Route::post('upload', [
        'uses' => 'Megaads\Apify\Controllers\APIController@upload',
    ]);
    Route::get('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@get',
    ]);
    Route::get('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@show',
    ]);
    Route::post('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@store',
    ]);
    Route::put('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@update',
    ]);
    Route::patch('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@patch',
    ]);
    Route::delete('{entity}/{id:[0-9]+}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@destroy',
    ]);
    Route::delete('{entity}', [
        'uses' => 'Megaads\Apify\Controllers\APIController@destroyBulk',
    ]);
});