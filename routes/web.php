<?php

use App\Http\Controllers\ZohoController;
use App\Http\Controllers\PlaceToPayController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/contract/{so}', [ZohoController::class, 'getContractBySO']);

Route::middleware('auth')->prefix('ptp')->group(function () {
    Route::get('/', [PlaceToPayController::class, 'show'])->name('ptp.home');
    Route::get('/buscar-transacciones', [PlaceToPayController::class, 'buscarTransacciones'])->name('ptp.search.transactions');
    Route::get('/{reference}', [PlaceToPayController::class, 'showPaymentsOfTransaction']);
    Route::get('/{reference}/renew', [PlaceToPayController::class, 'renewSession']);
    Route::post('/baja-transaccion/{id}', [PlaceToPayController::class, 'darDeBajaTransaccion'])->name('ptp.delete.sub');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/cronos', [App\Http\Controllers\CronosController::class, 'viewCronos']);


