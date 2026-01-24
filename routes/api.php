<?php


use Illuminate\Support\Facades\Route;

Route::middleware(['auth.keycloak'])->group(function () {
    Route::get('/user/profile', function (Request $request) {
        // Access decoded Keycloak data
        return response()->json($request->get('keycloak_user'));
    });
});
