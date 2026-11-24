<?php

use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json(['success' => true, 'message' => 'Mahapreneur API berjalan']));

Route::get('/', fn () => response()->json(['success' => true, 'message' => 'Mahapreneur API']));
