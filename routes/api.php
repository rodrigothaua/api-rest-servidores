<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ServidorEfetivoController;
use App\Http\Controllers\ServidorTemporarioController;
use App\Http\Controllers\UnidadeController;
use App\Http\Controllers\LotacaoController;
use App\Http\Controllers\FotoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rotas públicas
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);

// Rotas protegidas por autenticação
Route::middleware('auth:api')->group(function () {
    // Autenticação
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);
    
    // Servidores Efetivos
    Route::apiResource('servidores-efetivos', ServidorEfetivoController::class);
    Route::get('servidores-efetivos/unidade/{unid_id}', [ServidorEfetivoController::class, 'servidoresPorUnidade']);
    Route::get('servidores-efetivos/endereco-funcional/{nome}', [ServidorEfetivoController::class, 'enderecoFuncional']);
    
    // Servidores Temporários
    Route::apiResource('servidores-temporarios', ServidorTemporarioController::class);
    
    // Unidades
    Route::apiResource('unidades', UnidadeController::class);
    
    // Lotações
    Route::apiResource('lotacoes', LotacaoController::class);
    
    // Fotos
    Route::post('pessoas/{pes_id}/fotos', [FotoController::class, 'upload']);
    Route::get('fotos/{hash}', [FotoController::class, 'getFoto']);
    Route::delete('fotos/{id}', [FotoController::class, 'deleteFoto']);

    // Rotas de cidades
    Route::get('cidades', [CidadeController::class, 'index']);
    Route::get('cidades/{id}', [CidadeController::class, 'show']);

});

