<?php

use App\Http\Controllers\{ExcelController, PassportAuthController, RebillController, ContactController, StripePaymentController, LeadController, MethodContactController, ProfessionController, PurchasingProcessController, SpecialityController, ZohoController, ContractController, DatafastController, CronosController, PlaceToPayController};
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\PaymentLinkController;
use App\Http\Controllers\Webhooks\WebhookGatewayController;
use App\Http\Controllers\Webhooks\CrmOrderSalesStep5ChargeDetailsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

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
    Route::post('/updateEntityIdLeadVentas', [PurchasingProcessController::class, 'updateEntityIdLeadVentas']);

    Route::post('/createLeadZohoCRM', [ZohoController::class, 'createLead']);
    Route::post('/convertLeadZohoCRM', [ZohoController::class, 'convertLead']); //en la seccion de cursos se puede ver que convertLeadZohoCRM tira error
    Route::post('/createContactZohoCRM', [ZohoController::class, 'createContact']);
    Route::post('/createAddressZohoCRM', [ZohoController::class, 'createAddressRequest']);

    Route::post('/leadSaveProgress/{idPurchaseProgress}', [LeadController::class, 'storeProgress']);

    Route::post('/updateEntityIdContactSales', [ContactController::class, 'updateEntityIdContactSales']);
    Route::post('/contactSaveProgress/{idPurchaseProgress}', [ContactController::class, 'storeProgress']);
    Route::post('/contractSaveProgress/{idPurchaseProgress}', [ContractController::class, 'storeProgress']);
});

Route::get('/products/{iso}', [ZohoController::class, 'getProducts']);
Route::get('/products', [ZohoController::class, 'getProductsWithoutIso']);
Route::post('/db/stepConversionContract', [PurchasingProcessController::class, 'stepConversionContract']);

Route::post('/createSaleZohoCRM', [ZohoController::class, 'createSale']);


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
Route::middleware('cors')->post('/stripe/subscriptionPayment', [StripePaymentController::class, 'subscriptionPayment']);
Route::get('/stripe/customer/search/{email}', [StripePaymentController::class, 'findCustomerByEmail']);


Route::post('/updateZohoCTCZohoCRM', [ZohoController::class, 'updateZohoCTC']);
Route::post('/updateZohoStripeZohoCRM', [ZohoController::class, 'updateZohoStripe']);
Route::post('/updateZohoMPZohoCRM', [ZohoController::class, 'updateZohoMP']);
Route::post('/updateZohoPTP', [ZohoController::class, 'updateZohoPTP']);
Route::post('/setContractStatus', [ContractController::class, 'setContractStatus']);


Route::get('/db', [PurchasingProcessController::class, 'index']);

Route::get('/contract/{id}', [ContractController::class, 'show']);

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

Route::get('/msk', [CronosController::class, 'index']);
Route::post('/addElement', [CronosController::class, 'addcontract']);
Route::post('/deleteElement', [CronosController::class, 'deletecontract']);
Route::get('/processElements', [CronosController::class, 'cronapi']);
Route::get('/cronostest', [CronosController::class, 'test']);
Route::post('/obtainDataCRM', [ZohoController::class, 'obtainData']);

Route::prefix("/rebill")->group(function () {
    Route::get('/login', [RebillController::class, 'login']);
    Route::get('/getAllCustomers', [RebillController::class, 'getAllCustomers']);
    Route::get('/addStripeGateway', [RebillController::class, 'addStripeGateway']);
    Route::get('/generateCheckourRebill', [RebillController::class, 'generateCheckourRebill']);
    Route::post('/addPendingPayment', [RebillController::class, 'addPendingPayment']);
    Route::get('/checkPendingPayments', [RebillController::class, 'checkPendingPayments']);
    Route::post('/generatePaymentLink', [PaymentLinkController::class, 'create']);
    Route::get('/getPaymentLink/{saleId}', [PaymentLinkController::class, 'show']);
});

Route::prefix("/webhook")->group(function () {
    Route::post('/mp', [MercadoPagoController::class, '']);
    Route::post('/stripe', [StripePaymentController::class, 'handleWebhook']);
});

Route::prefix("/payments_msk")->group(function () {
    Route::post('/create', [\App\Http\Controllers\PaymentsMsk\CreatePaymentMskController::class, 'create']);
    Route::get('/list', [\App\Http\Controllers\PaymentsMsk\ReadPaymentMskController::class, 'list']);
});

Route::prefix("/contifico")->group(function () {
    Route::post('/user/create', [\App\Http\Controllers\Contifico\ContificoController::class, 'createUser']);
});

Route::get("/mp/searchPaymentApprove/{so}", [MercadoPagoPaymentController::class, 'searchPaymentApprove']);

Route::get('/getPaymentsStatusDistintContratoEfectivo', [PaymentLinkController::class, 'getPaymentsStatusDistintContratoEfectivo']);


Route::post('/ctc/exportExcel', [ExcelController::class, 'exportExcel']);
Route::get('/download-excel/{filename}', [ExcelController::class, 'downloadExcel']);
Route::post('/ctc/exportExcelSuscription', [ExcelController::class, 'exportExcelSuscription']);

Route::post('/ctc/exportExcel1BPOCP', [ExcelController::class, 'exportExcel1BPOCP']);
Route::post('/ctc/exportExcel2BPOCP', [ExcelController::class, 'exportExcel2BPOCP']);
Route::post('/ctc/exportExcel3OBPOCP', [ExcelController::class, 'exportExcel3OBPOCP']);
Route::post('/ctc/exportExcel4PBOCP', [ExcelController::class, 'exportExcel4PBOCP']);

Route::prefix("/placetopay")->group(function () {
    Route::get('/getAuth', [PlaceToPayController::class, 'getAuth']);
    Route::post('/createSession', [PlaceToPayController::class, 'createSession']);
    Route::post('/createSessionSubscription', [PlaceToPayController::class, 'createSessionSubscription']);
    Route::get('/getSessionByRequestId/{requestId}', [PlaceToPayController::class, 'getSessionByRequestId']);

    Route::get('/placetopay', [PlaceToPayController::class, 'index']);
    Route::get('/createPayment', [PlaceToPayController::class, 'createPayment']);

    Route::post('/savePayments', [PlaceToPayController::class, 'savePayments']);
    Route::post('/savePaymentSubscription', [PlaceToPayController::class, 'savePaymentSubscription']);
    Route::get('/billSubscription/{requestId}', [PlaceToPayController::class, 'billSubscription']);

    Route::get('/pruebaregladepago', [PlaceToPayController::class, 'pruebaregladepago']);


    Route::post('/generatePaymentLink', [PlaceToPayPaymentLinkController::class, 'create']);
    Route::get('/getPaymentLink/{saleId}', [PlaceToPayPaymentLinkController::class, 'show']);
});
