<?php

use App\Livewire\CsvUpload;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// })->name('home');

Route::get('/', CsvUpload::class);
