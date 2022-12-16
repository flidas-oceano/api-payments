<?php

use App\Http\Controllers\{StripePaymentController,LeadController,PurchasingProcessController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post("/stripe/paymentIntent",[StripePaymentController::class, 'paymentIntent']);
Route::post("/stripe/subscriptionPayment",[StripePaymentController::class, 'subscriptionPayment']);
Route::get("/stripe/customer/search/{email}",[StripePaymentController::class, 'findCustomerByEmail']);

Route::get("/db",[PurchasingProcessController::class, 'index']);


// Route::resource('db', '\App\Http\Controllers\PurchasingProcessController');
