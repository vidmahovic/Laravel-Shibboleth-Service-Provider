<?php

// Login Route (Shibboleth and Local)
Route::get('/login', 'App\Http\Controllers\WelcomeController@login');
// Logout Route (Shibboleth and Local)
Route::get('/logout', ['middleware' => 'auth', 'uses' =>'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@destroy']);
// Shibboleth IdP Callback
Route::get('/auth', ['middleware' => 'guest', 'uses' => 'StudentAffairsUwm\Shibboleth\Controllers\ShibbolethController@idpAuthorize']);

?>
