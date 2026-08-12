<?php
// Include this file in any group where the model in the route implements the contract IsAnEntity
// All routes here will then automatically be functional.

Route::resource('assets', 'Entity\AssetController', [
    'only' => [
        'index', 'store', 'update', 'destroy',
    ],
]);
Route::resource('collections', 'Entity\CollectionController', [
    'only' => [
        'index', 'store',
    ],
]);
Route::resource('payments', 'Entity\PaymentController', [
    'only' => [
        'index',
    ],
]);
Route::resource('payment-methods', 'Entity\PaymentMethodController', [
    'only' => [
        'store', 'destroy', 'update',
    ],
]);
Route::resource('profile-images', 'Entity\ProfileImageController', [
    'only' => [
        'store',
    ],
]);
Route::resource('subscriptions', 'Entity\SubscriptionController', [
    'only' => [
        'index', 'store', 'update',
    ],
]);

// Application level routes below
