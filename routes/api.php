<?php

use Illuminate\Support\Facades\Route;
use SevereHeadache\Coffre\Http\Controllers\StorageController;

Route::prefix('storage')
    ->controller(StorageController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('/{document}', 'contents')->where('document', '^[^\.]+$');
        Route::post('/{document}', 'save')->where('document', '^[^\.]+$');
        Route::patch('/{document}', 'rename')->where('document', '^[^\.]+$');
        Route::delete('/{document}', 'delete')->where('document', '^[^\.]+$');
    });
