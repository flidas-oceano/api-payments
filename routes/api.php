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


// http://127.0.0.1:8000/api/db/
Route::get("/db",[PurchasingProcessController::class, 'index']);
Route::get("/db/getLead",[LeadController::class, 'index']);
Route::post("/db/stepCreateLead",[PurchasingProcessController::class, 'stepCreateLead']);
Route::post("/db/stepCreateContact",[PurchasingProcessController::class, 'stepCreateContact']);



// Route::resource('db', '\App\Http\Controllers\PurchasingProcessController');
