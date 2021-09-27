<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('auth/login');
});

Auth::routes();
Route::middleware('auth')->group(function () {
    Route::get('/p_cbn', function () {
        return view('/p_cbn');
    })->name('pcbn');

    Route::get('/p_local_banks', function () {
        return view('/p_local_banks');
    })->name('plb'); 

    Route::get('/p_foreign_banks', function () {
        return view('/p_foreign_banks');
    })->name('pfb');

    Route::get('/p_subsidiary', function () {
        return view('/p_subsidiary');
    })->name('psub');

    Route::get('/t_cbn', function () {
        return view('/t_cbn');
    })->name('tcbn');

    Route::get('/t_local_banks', function () {
        return view('/t_local_banks');
    })->name('tlb');

    Route::get('/t_subsidiary', function () {
        return view('/t_subsidiary');
    })->name('tsub');
});
 Route::get('/home', 'HomeController@index')->name('home'); 

Route::get('/variables', 'HomeController@variables')->name('variables');

Route::get('/rates', 'HomeController@rate');

Route::get('/interestincome_fgb', 'HomeController@InterestIncomeFgb')->name('interestincome_fgb');

Route::get('/interestincome_sub', 'HomeController@InterestIncomeSub')->name('interestincome_sub');

Route::post('/variables', 'HomeController@change');

Route::middleware('auth')->group(function () {
    Route::get('/export_plb', 'PLB@export');

    Route::post('/import_plb', 'PLB@import');

    Route::get('/export_tlb', 'TLB@export');

    Route::post('/import_tlb', 'TLB@import');

    Route::get('/export_pcbn', 'PCBN@export');

    Route::post('/import_pcbn', 'PCBN@import');

    Route::get('/export_tcbn', 'TCBN@export');

    Route::post('/import_tcbn', 'TCBN@import');

    Route::get('/export_psub', 'PSUB@export');

    Route::post('/import_psub', 'PSUB@import');

    Route::get('/interest_income/edit/{class}/{date}', function ($class, $date)
    {
        return view('interest_income_edit', compact('class','date'));
    });

    Route::post('/interest_income/edit', 'HomeController@EditInterestIncome');
});