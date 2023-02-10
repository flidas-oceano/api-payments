<?php

use App\Http\Controllers\{ContactController, StripePaymentController,LeadController, MethodContactController, ProfessionController, PurchasingProcessController, SpecialityController, ZohoController,Contact, ContractController};
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
Route::post('/contractSaveProgress/{idPurchaseProgress}',[ContractController::class, 'storeProgress']);
Route::post("/stripe/paymentIntent",[StripePaymentController::class, 'paymentIntent']);
Route::post("/stripe/subscriptionPayment",[StripePaymentController::class, 'subscriptionPayment']);
Route::get("/stripe/customer/search/{email}",[StripePaymentController::class, 'findCustomerByEmail']);

// http://127.0.0.1:8000/api/db/
Route::get("/db",[PurchasingProcessController::class, 'index']);
Route::get("/db/getLead",[LeadController::class, 'index']);
Route::post("/db/stepCreateLead",[PurchasingProcessController::class, 'stepCreateLead']);
Route::post("/db/stepConversionContact",[PurchasingProcessController::class, 'stepConversionContact']);
Route::post('/db/stepConversionContract',[PurchasingProcessController::class, 'stepConversionContract']);

Route::post('/updateEntityIdLeadVentas',[PurchasingProcessController::class, 'updateEntityIdLeadVentas']);
Route::post('/updateEntityIdContactSales',[ContactController::class, 'updateEntityIdContactSales']);


Route::post('/createLeadZohoCRM',[ZohoController::class, 'createLead']);
Route::post('/convertLeadZohoCRM',[ZohoController::class, 'convertLead']);
Route::post('/createContactZohoCRM',[ZohoController::class, 'createContact']);
Route::post('/createContractZohoCRM',[ZohoController::class, 'createSale']);
Route::post('/createAddressZohoCRM',[ZohoController::class, 'createAddress']);
Route::post('/createSaleZohoCRM',[ZohoController::class, 'createSale']);
Route::post('/updateZohoStripeZohoCRM',[ZohoController::class, 'updateZohoStripe']);
Route::get('/products',[ZohoController::class, 'getProducts']);

// http://localhost:8000/api/zcrm/createLead

Route::apiResource("professions", ProfessionController::class);
Route::apiResource("specialities", SpecialityController::class);
Route::apiResource("methods", MethodContactController::class);
Route::apiResource("progress", PurchasingProcessController::class);
Route::get('/contract/{id}',[ContractController::class, 'show']);
