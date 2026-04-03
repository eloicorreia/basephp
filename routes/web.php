<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/queue-test', function () {
    TestQueueJob::dispatch('Fila funcionando com sucesso.');

    return response()->json([
        'success' => true,
        'message' => 'Job enviado para a fila.',
    ]);
});
