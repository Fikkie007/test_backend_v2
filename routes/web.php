<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['success' => true, 'code' => 200, 'message' => 'Product API']));
