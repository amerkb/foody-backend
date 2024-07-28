<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\TableController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*  Route::middleware(['auth:sanctum','abilities:'.UserStatus::ADMIN])->prefix('Admin')->group(function (){
      Route::apiResource('category',CategoryController::class);
      Route::apiResource('table',TableController::class);
      Route::apiResource('emp',Employeecontroller::class);
  });*/

Route::middleware('auth:sanctum')->prefix('Admin')->group(function () {

    Route::apiResource('category', CategoryController::class);
    Route::put('/activecategory/{id}', [CategoryController::class, 'active']);
    //////////product //////////////////////
    Route::apiResource('table', TableController::class);
    Route::post('/activetable/{id}', [TableController::class, 'active']);
    //////employeee////
    Route::get('/getemps', [Employeecontroller::class, 'index']);
    Route::post('/storeemp', [EmployeeController::class, 'store']);
    Route::post('/updateemp/{id}', [EmployeeController::class, 'update']);
    Route::delete('/deleteemp/{id}', [EmployeeController::class, 'destroy']);
    Route::post('/activeemp/{id}', [EmployeeController::class, 'active']);

    Route::apiResource('product', ProductController::class)->except('update');
    Route::post('/product/{product}', [ProductController::class, 'update']);

});
