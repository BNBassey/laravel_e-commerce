<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AuthAdmin;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', [HomeController::class, 'index'])->name('home.index');
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/{slug}', [ShopController::class, 'productDetails'])->name('shop.product.details');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware([AuthAdmin::class])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/brands', [AdminController::class, 'brands'])->name('admin.brands');
    Route::get('/admin/brand-add', [AdminController::class, 'brandAdd'])->name('admin.brand.add');
    Route::post('/admin/brand-store', [AdminController::class, 'brandStore'])->name('admin.brand.store');
    Route::get('/admin/brand-edit/{id}', [AdminController::class, 'brandEdit'])->name('admin.brand.edit');
    Route::put('/admin/brand-update/{id}', [AdminController::class, 'brandUpdate'])->name('admin.brand.update');
    Route::delete('/admin/brand-delete/{id}', [AdminController::class, 'brandDelete'])->name('admin.brand.delete');

    Route::get('/admin/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::get('/admin/category-add', [AdminController::class, 'categoryAdd'])->name('admin.category.add');
    Route::post('/admin/category-store', [AdminController::class, 'categoryStore'])->name('admin.category.store');
    Route::get('/admin/category-edit/{id}', [AdminController::class, 'categoryEdit'])->name('admin.category.edit');
    Route::put('/admin/category-update/{id}', [AdminController::class, 'categoryUpdate'])->name('admin.category.update');
    Route::delete('/admin/category-delete/{id}', [AdminController::class, 'categoryDelete'])->name('admin.category.delete');

    Route::get('/admin/products', [ProductController::class, 'products'])->name('admin.products');
    Route::get('/admin/product-add', [ProductController::class, 'productAdd'])->name('admin.product.add');
    Route::post('/admin/product-store', [ProductController::class, 'productStore'])->name('admin.product.store');
    Route::get('/admin/product-edit/{id}', [ProductController::class, 'productEdit'])->name('admin.product.edit');
    Route::put('/admin/product-update/{id}', [ProductController::class, 'productUpdate'])->name('admin.product.update');
    Route::delete('/admin/product-delete/{id}', [ProductController::class, 'productDelete'])->name('admin.product.delete');
    Route::delete('/admin/products-bulk-delete', [ProductController::class, 'productsBulkDelete'])->name('admin.products.bulk.delete');
    Route::get('/admin/product-export', [ProductController::class, 'productExport'])->name('admin.product.export');    

    Route::get('/admin/orders', [OrderController::class, 'orders'])->name('admin.orders');    
    Route::get('/admin/order-add', [OrderController::class, 'orderAdd'])->name('admin.order.add');
    Route::post('/admin/order-store', [OrderController::class, 'orderStore'])->name('admin.order.store');
    Route::get('/admin/order-edit/{id}', [OrderController::class, 'orderEdit'])->name('admin.order.edit');
    Route::put('/admin/order-update/{id}', [OrderController::class, 'orderUpdate'])->name('admin.order.update');
    Route::delete('/admin/order-delete/{id}', [OrderController::class, 'orderDelete'])->name('admin.order.delete');
});

require __DIR__.'/auth.php';
