<?php

use App\Http\Controllers\{ContactController, StripePaymentController,LeadController, MethodContactController, ProfessionController, PurchasingProcessController, SpecialityController, ZohoController,ContractController,DatafastController, CronosController};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PassportAuthController;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::middleware('auth:api')->get('/user', function (Request $request){
    return $request->user();
});

// Route::group(['prefix' => 'auth'], function (){
    Route::post('register',[PassportAuthController::class, 'register']);
    Route::post('login',[PassportAuthController::class, 'login']);
    Route::post('expiredToken',[PassportAuthController::class, 'expiredToken']);
    Route::get('tokenIsValid',[PassportAuthController::class, 'tokenIsValid'])->middleware('auth:api');

    Route::group(['middleware' => 'auth:api'], function (){
        Route::get('logout',[PassportAuthController::class, 'logout']);
        Route::get('user',[PassportAuthController::class, 'user']);
    });
// });

Route::apiResource('/leads', LeadController::class);
Route::post('/leadSaveProgress/{idPurchaseProgress}', [LeadController::class, 'storeProgress'])->middleware('auth:api');
Route::post('/contactSaveProgress/{idPurchaseProgress}', [ContactController::class, 'storeProgress'])->middleware('auth:api');
Route::post('/contractSaveProgress/{idPurchaseProgress}', [ContractController::class, 'storeProgress'])->middleware('auth:api');
Route::post('/stripe/paymentIntent', [StripePaymentController::class, 'paymentIntent']);
Route::post('/stripe/subscriptionPayment', [StripePaymentController::class, 'subscriptionPayment']);
Route::get('/stripe/customer/search/{email}', [StripePaymentController::class, 'findCustomerByEmail']);

// http://127.0.0.1:8000/api/db/
Route::get('/db', [PurchasingProcessController::class, 'index']);
Route::get('/db/getLead', [LeadController::class, 'index']);
Route::post('/db/stepCreateLead', [PurchasingProcessController::class, 'stepCreateLead'])->middleware('auth:api');
Route::post('/db/stepConversionContact', [PurchasingProcessController::class, 'stepConversionContact'])->middleware('auth:api');
Route::post('/db/stepConversionContract', [PurchasingProcessController::class, 'stepConversionContract'])->middleware('auth:api');

Route::post('/updateEntityIdLeadVentas', [PurchasingProcessController::class, 'updateEntityIdLeadVentas'])->middleware('auth:api');
Route::post('/updateEntityIdContactSales', [ContactController::class, 'updateEntityIdContactSales'])->middleware('auth:api');

Route::post('/createLeadZohoCRM', [ZohoController::class, 'createLead'])->middleware('auth:api');
Route::post('/convertLeadZohoCRM', [ZohoController::class, 'convertLead'])->middleware('auth:api');//en la seccion de cursos se puede ver que convertLeadZohoCRM tira error
Route::post('/createContactZohoCRM', [ZohoController::class, 'createContact']);
Route::post('/createAddressZohoCRM', [ZohoController::class, 'createAddressRequest']);
Route::post('/createSaleZohoCRM', [ZohoController::class, 'createSale'])->middleware('auth:api');
Route::post('/updateZohoStripeZohoCRM', [ZohoController::class, 'updateZohoStripe']);
Route::post('/obtainDataCRM', [ZohoController::class, 'obtainData']);
Route::get('/products/{iso}', [ZohoController::class, 'getProducts'])->middleware('auth:api');
Route::get('/products', [ZohoController::class, 'getProductsWithoutIso'])->middleware('auth:api');
Route::get('/progress/{id}', [PurchasingProcessController::class, 'show'])->middleware('auth:api');

Route::apiResource('professions', ProfessionController::class);
Route::apiResource('specialities', SpecialityController::class);
Route::apiResource('methods', MethodContactController::class);
Route::apiResource('progress', PurchasingProcessController::class);
        Route::get('/progress/{id}', [PurchasingProcessController::class, 'show']);

Route::get('/contract/{id}',[ContractController::class, 'show']);
Route::get('/msk',[CronosController::class, 'index']);
Route::get('/province/{country}',[CronosController::class, 'getProvinces']);

Route::post('/datafastGetForm',[DatafastController::class, 'requestForm']);


