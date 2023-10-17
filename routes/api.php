<?php

use App\Http\Controllers\{ExcelController, PassportAuthController, RebillController, ContactController, StripePaymentController, LeadController, MethodContactController, ProfessionController, PurchasingProcessController, SpecialityController, ZohoController, ContractController, DatafastController, CronosController, PlaceToPayController, PlaceToPayPaymentLinkController};
use App\Http\Controllers\MercadopagoController;
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

Route::get('/getContactByContract/{so}', [ZohoController::class, 'getContactByContract']);

Route::post('/updateZohoCTCZohoCRM', [ZohoController::class, 'updateZohoCTCMSK']);
Route::post('/saveCardZohoCTC', [ZohoController::class, 'saveCardZohoCTC']);
Route::post('/updateZohoStripeZohoCRM', [ZohoController::class, 'updateZohoStripeMSK']);
Route::post('/updateZohoMPZohoCRM', [ZohoController::class, 'updateZohoMPMSK']);
Route::post('/updateZohoPTP', [ZohoController::class, 'updateZohoPTPMSK']);
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

Route::post('/buildTablePaymentDetail', [ZohoController::class, 'buildTablePaymentDetail']);

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

Route::middleware('api-api')->prefix("/payments_msk")->group(function () {
    Route::post('/create', [\App\Http\Controllers\PaymentsMsk\CreatePaymentMskController::class, 'create']);
    Route::get('/list', [\App\Http\Controllers\PaymentsMsk\ReadPaymentMskController::class, 'list']);
});

Route::middleware('api-api')->prefix("/contifico")->group(function () {
    //user
    Route::post('/user', [\App\Http\Controllers\Contifico\ContificoUserController::class, 'store']);
    Route::get('/user/{uuid}', [\App\Http\Controllers\Contifico\ContificoUserController::class, 'getUser']);
    //invoice
    Route::get('/invoice/{uuid}', [\App\Http\Controllers\Contifico\ContificoInvoiceController::class, 'getInvoice']);
    Route::post('/invoice', [\App\Http\Controllers\Contifico\ContificoInvoiceController::class, 'store']);
    Route::post('/charge', [\App\Http\Controllers\Contifico\ContificoChargeController::class, 'store']);
});

Route::get("/mp/searchPaymentApprove/{so}", [\App\Http\Controllers\MercadoPagoPaymentController::class, 'searchPaymentApprove']);

Route::get('/getPaymentsStatusDistintContratoEfectivo', [PaymentLinkController::class, 'getPaymentsStatusDistintContratoEfectivo']);

Route::post('/ctc/exportExcel', [ExcelController::class, 'exportExcel']);
Route::get('/download-excel/{filename}', [ExcelController::class, 'downloadExcel']);
Route::post('/ctc/exportExcelSuscription', [ExcelController::class, 'exportExcelSuscription']);

Route::post('/ctc/exportExcel1BPOCP', [ExcelController::class, 'exportExcel1BPOCP']);
Route::post('/ctc/exportExcel2BPOCP', [ExcelController::class, 'exportExcel2BPOCP']);
Route::post('/ctc/exportExcel3OBPOCP', [ExcelController::class, 'exportExcel3OBPOCP']);
Route::post('/ctc/exportExcel4PBOCP', [ExcelController::class, 'exportExcel4PBOCP']);


// /placetopay/notificationUpdate
Route::prefix("/placetopay")->group(function () {
    Route::get('/{reference}/renew', [PlaceToPayController::class, 'authRenewSession']);
    Route::put('/{reference}', [PlaceToPayController::class, 'updateStatusSessionSubscription']);
    Route::get('/getAuth', [PlaceToPayController::class, 'getAuth']);
    Route::post('/createSession', [PlaceToPayController::class, 'createSession']);
    Route::post('/createSessionSubscription', [PlaceToPayController::class, 'createSessionSubscription']);
    Route::post('/renewSessionSubscription', [PlaceToPayController::class, 'renewSessionSubscription']);

    Route::get('/getSessionByRequestId/{requestId}', [PlaceToPayController::class, 'getSessionByRequestId']);

    Route::get('/createPayment', [PlaceToPayController::class, 'createPayment']);

    Route::post('/savePayments', [PlaceToPayController::class, 'savePayments']);
    Route::post('/savePaymentSubscription', [PlaceToPayController::class, 'savePaymentSubscription']);
    Route::get('/revokeTokenSession/{requestIdSession}', [PlaceToPayController::class, 'revokeTokenSession']);

    Route::post('/generatePaymentLink', [PlaceToPayPaymentLinkController::class, 'create']);
    Route::get('/getPaymentLink/{saleId}', [PlaceToPayPaymentLinkController::class, 'getPaymentLink']);

    Route::get('/updatePaymentLinkStatus/{saleId}', [PlaceToPayPaymentLinkController::class, 'updatePaymentLinkStatus']);

    Route::post('/notificationUpdate', [PlaceToPayController::class, 'notificationUpdate']);

});


