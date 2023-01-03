<?php

use App\Http\Controllers\Api\Cart;
use App\Http\Controllers\Api\PaymentGate;
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

Route::prefix('carts')->group(function(){
    Route::get('/get-new-fake-cart', [Cart::class, 'getNewFakeCart']);
});


Route::prefix('payment-gate')->group(function(){
    Route::post('/request-to-pay', [PaymentGate::class, 'requestToPay']);
    Route::post('/get-payment/{invoiceId}', [PaymentGate::class, 'getPayment']);
});