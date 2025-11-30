<?php

use Illuminate\Support\Facades\Route;

// Redirect root to frontend web app URL (Vue.js dev server or built app)
Route::get('/', function () {
    return redirect('http://localhost:5173'); // Adjust if needed based on frontend dev server or deployed URL
});

// You may add other web routes here if you want backend to serve any views or pages
