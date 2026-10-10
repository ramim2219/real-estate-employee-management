<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ResourceController;
use App\Http\Controllers\Web\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'rems.dashboard' : 'login'));
Route::middleware('guest')->group(function () {
    Route::get('/login',[AuthController::class,'create'])->name('login');
    Route::post('/login',[AuthController::class,'store'])->middleware('throttle:5,1')->name('rems.login.submit');
});
Route::middleware('auth')->prefix('panel')->name('rems.')->group(function () {
    Route::get('/',DashboardController::class)->name('dashboard');
    Route::post('/logout',[AuthController::class,'destroy'])->name('logout');
    Route::get('/documents/{id}/download',[WorkflowController::class,'download'])->whereNumber('id')->name('documents.download');
    Route::post('/workflow/{action}/{id?}',[WorkflowController::class,'handle'])->whereNumber('id')->name('workflow');
    Route::get('/{resource}',[ResourceController::class,'index'])->name('resources.index');
    Route::get('/{resource}/create',[ResourceController::class,'create'])->name('resources.create');
    Route::post('/{resource}',[ResourceController::class,'store'])->name('resources.store');
    Route::get('/{resource}/{id}',[ResourceController::class,'show'])->whereNumber('id')->name('resources.show');
    Route::get('/{resource}/{id}/edit',[ResourceController::class,'edit'])->whereNumber('id')->name('resources.edit');
    Route::put('/{resource}/{id}',[ResourceController::class,'update'])->whereNumber('id')->name('resources.update');
    Route::delete('/{resource}/{id}',[ResourceController::class,'destroy'])->whereNumber('id')->name('resources.destroy');
});
