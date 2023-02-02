<?php

use App\Http\Controllers\{ContactController, StripePaymentController,LeadController, MethodContactController, ProfessionController, PurchasingProcessController, SpecialityController, ZohoController};
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

Route::apiResource("/leads",LeadController::class);
Route::post('/leadSaveProgress/{idPurchaseProgress}',[LeadController::class, 'storeProgress']);
Route::post('/contactSaveProgress/{idPurchaseProgress}',[ContactController::class, 'storeProgress']);
Route::post("/stripe/paymentIntent",[StripePaymentController::class, 'paymentIntent']);
Route::post("/stripe/subscriptionPayment",[StripePaymentController::class, 'subscriptionPayment']);
Route::get("/stripe/customer/search/{email}",[StripePaymentController::class, 'findCustomerByEmail']);


// http://127.0.0.1:8000/api/db/
Route::get("/db",[PurchasingProcessController::class, 'index']);
Route::get("/db/getLead",[LeadController::class, 'index']);
Route::post("/db/stepCreateLead",[PurchasingProcessController::class, 'stepCreateLead']);
Route::post("/db/stepConversionContact",[PurchasingProcessController::class, 'stepConversionContact']);

Route::post('/createLead',[ZohoController::class, 'createLead']);
Route::post('/convertLead',[ZohoController::class, 'convertLead']);
Route::post('/createContact',[ZohoController::class, 'createContact']);
Route::post('/createAddress',[ZohoController::class, 'createAddress']);
Route::post('/createSale',[ZohoController::class, 'createSale']);
Route::post('/updateZohoStripe',[ZohoController::class, 'updateZohoStripe']);
Route::get('/zoho/products',[ZohoController::class, 'getProducts']);

// http://localhost:8000/api/zcrm/createLead


Route::apiResource("professions", ProfessionController::class);
Route::apiResource("specialities", SpecialityController::class);
Route::apiResource("methods", MethodContactController::class);
Route::apiResource("progress", PurchasingProcessController::class);
