<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');   // se você tiver o view padrão habilitado
});
