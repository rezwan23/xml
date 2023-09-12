<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DataController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/get-data', [DataController::class, 'getData']);

Route::post('/get-products-data', [DataController::class, 'getProductsData']);


Route::get('flak-large-xml', [DataController::class, 'getLargeXml']);

Route::post('/get-bring-product', [DataController::class, 'getBringProducts']);


Route::get('product-data', [DataController::class, 'getProductsXLData']);

Route::post('/create-bring-booking', [DataController::class, 'createBringBooking']);


Route::post('/bring-order-status', [DataController::class, 'processBringOrderChangeRequest']);


Route::post('/bring-create-hook', [DataController::class, 'createHook']);

Route::get('/sent-email/{orderNumber}', [DataController::class, 'sendEmailRequestToMarketingCloud']);

Route::get('/dispatch-mc-emails', [DataController::class, 'dispatchMCEmailJobs']);

Route::get('/flush-queues', [DataController::class, 'queueFlush']);


