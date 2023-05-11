<?php

use App\Http\Controllers\{PassportAuthController, RebillController, ContactController, StripePaymentController, LeadController, MethodContactController, ProfessionController, PurchasingProcessController, SpecialityController, ZohoController, ContractController, DatafastController, CronosController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Stripe\Stripe;

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:api')->group(function () {
    Route::get('/tokenIsValid', [PassportAuthController::class, 'tokenIsValid']);
    Route::get('user', [PassportAuthController::class, 'user']);
    Route::post('logout', [PassportAuthController::class, 'logout']);

    Route::get('/progress/{id}', [PurchasingProcessController::class, 'show']);
    Route::post('/db/stepCreateLead', [PurchasingProcessController::class, 'stepCreateLead']);
    Route::post('/db/stepConversionContact', [PurchasingProcessController::class, 'stepConversionContact']);
    Route::post('/db/stepConversionContract', [PurchasingProcessController::class, 'stepConversionContract']);
    Route::post('/updateEntityIdLeadVentas', [PurchasingProcessController::class, 'updateEntityIdLeadVentas']);

    Route::post('/createLeadZohoCRM', [ZohoController::class, 'createLead']);
    Route::post('/convertLeadZohoCRM', [ZohoController::class, 'convertLead']); //en la seccion de cursos se puede ver que convertLeadZohoCRM tira error
    Route::post('/createContactZohoCRM', [ZohoController::class, 'createContact']);
    Route::post('/createAddressZohoCRM', [ZohoController::class, 'createAddressRequest']);
    Route::post('/createSaleZohoCRM', [ZohoController::class, 'createSale']);
    Route::get('/products/{iso}', [ZohoController::class, 'getProducts']);
    Route::get('/products', [ZohoController::class, 'getProductsWithoutIso']);

    Route::post('/leadSaveProgress/{idPurchaseProgress}', [LeadController::class, 'storeProgress']);

    Route::post('/updateEntityIdContactSales', [ContactController::class, 'updateEntityIdContactSales']);
    Route::post('/contactSaveProgress/{idPurchaseProgress}', [ContactController::class, 'storeProgress']);
    Route::post('/contractSaveProgress/{idPurchaseProgress}', [ContractController::class, 'storeProgress']);
});


Route::post('/register', [PassportAuthController::class, 'register']);
Route::post('/login', [PassportAuthController::class, 'login']);
Route::post('/expiredToken', [PassportAuthController::class, 'expiredToken']);


Route::apiResource('/leads', LeadController::class);
Route::apiResource('progress', PurchasingProcessController::class);
Route::apiResource('professions', ProfessionController::class);
Route::apiResource('specialities', SpecialityController::class);
Route::apiResource('methods', MethodContactController::class);

Route::get('/db/getLead', [LeadController::class, 'index']);

Route::post('/stripe/paymentIntent', [StripePaymentController::class, 'paymentIntent']);
Route::post('/stripe/subscriptionPayment', [StripePaymentController::class, 'subscriptionPayment']);
Route::get('/stripe/customer/search/{email}', [StripePaymentController::class, 'findCustomerByEmail']);

Route::post('/updateZohoStripeZohoCRM', [ZohoController::class, 'updateZohoStripe']);

Route::get('/db', [PurchasingProcessController::class, 'index']);

Route::get('/contract/{id}', [ContractController::class, 'show']);

Route::get('/msk', [CronosController::class, 'index']);
Route::post('/addElement', [CronosController::class, 'addcontract']);
Route::get('/province/{country}', [CronosController::class, 'getProvinces']);

Route::apiResource('professions', ProfessionController::class);
Route::apiResource('specialities', SpecialityController::class);
Route::apiResource('methods', MethodContactController::class);
Route::apiResource('progress', PurchasingProcessController::class);

Route::get('/contract/{id}', [ContractController::class, 'show']);
Route::get('/msk', [CronosController::class, 'index']);
Route::get('/province/{country}', [CronosController::class, 'getProvinces']);

Route::post('/datafastGetForm', [DatafastController::class, 'requestForm']);


Route::post('/datafastGetForm', [DatafastController::class, 'requestForm']);
Route::post('/datafastProcessResponse', [DatafastController::class, 'processResponse']);

Route::post('/addElement', [CronosController::class, 'addcontract']);
Route::get('/processElements', [CronosController::class, 'cronapi']);
Route::post('/obtainDataCRM', [ZohoController::class, 'obtainData']);

Route::prefix("/rebill")->group(function () {
    Route::get('/login', [RebillController::class, 'login']);
    Route::get('/getAllCustomers', [RebillController::class, 'getAllCustomers']);
    Route::get('/addStripeGateway', [RebillController::class, 'addStripeGateway']);
    Route::get('/generateCheckourRebill', [RebillController::class, 'generateCheckourRebill']);
});