<?php

use Slowlyo\OwlSku\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'owl-sku'], function () {
    Route::post('generate', [Controllers\OwlSkuController::class, 'generate']);
});
