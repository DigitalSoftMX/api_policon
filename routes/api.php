<?php

use Illuminate\Support\Facades\Route;

// Rutas del usuario, login, registro y cierre de sesion
Route::post('login', 'Api\AuthController@login');
Route::post('register', 'Api\AuthController@register');
Route::get('logout', 'Api\AuthController@logout');
Route::post('password/email', 'Auth\ForgotPasswordController@sendResetLinkEmail');
// Rutas para ver y editar perfiles de cliente y despachador
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('profile', 'Api\UserController@index');
    Route::post('profile/update', 'Api\UserController@update');
});
//Rutas para los usuarios tipo cliente
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::get('main', 'Api\ClientController@index');
    Route::get('dates', 'Api\ClientController@dates');
    Route::get('points/{station}', 'Api\ClientController@pointsStation');
    Route::get('termsandconditions', 'Api\ClientController@termsAndConditions');
});
// Rutas para los abonos
Route::group(['middleware' => 'jwtAuth'], function () {
    Route::post('addpoints', 'Api\ClientController@addPoints');
    Route::post('updatesale/{qr}', 'Api\ClientController@updateSale');
});
