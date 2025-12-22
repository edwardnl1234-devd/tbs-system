<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PollingController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SortationController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TbsPriceController;
use App\Http\Controllers\TruckController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WeighingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// ============================================================================
// Public Routes (No Authentication Required)
// ============================================================================

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Polling endpoints for display screens (can be public or protected based on needs)
Route::prefix('polling')->group(function () {
    Route::get('/queue', [PollingController::class, 'queue']);
    Route::get('/weighing', [PollingController::class, 'weighing']);
    Route::get('/stock', [PollingController::class, 'stock']);
    Route::get('/dashboard', [PollingController::class, 'dashboard']);
    Route::get('/production', [PollingController::class, 'production']);
});

// ============================================================================
// Protected Routes (Authentication Required)
// ============================================================================

Route::middleware('auth:sanctum')->group(function () {

    // ----------------------------------------------------------------------------
    // Auth Routes
    // ----------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // ----------------------------------------------------------------------------
    // Dashboard & Reports
    // ----------------------------------------------------------------------------
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/queue-stats', [DashboardController::class, 'queueStats']);
        Route::get('/production-stats', [DashboardController::class, 'productionStats']);
        Route::get('/stock-summary', [DashboardController::class, 'stockSummary']);
        Route::get('/sales-stats', [DashboardController::class, 'salesStats']);
        Route::get('/margin', [DashboardController::class, 'margin']);
        Route::get('/efficiency', [DashboardController::class, 'efficiency']);
    });

    Route::prefix('reports')->group(function () {
        Route::get('/daily', [ReportController::class, 'daily']);
        Route::get('/weekly', [ReportController::class, 'weekly']);
        Route::get('/monthly', [ReportController::class, 'monthly']);
        Route::get('/margin', [ReportController::class, 'marginReport']);
        Route::get('/stock-movement', [ReportController::class, 'stockMovement']);
        Route::get('/production', [ReportController::class, 'productionReport']);
    });

    // ----------------------------------------------------------------------------
    // User Management (Owner/Manager only)
    // ----------------------------------------------------------------------------
    Route::middleware('role:owner,manager')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // ----------------------------------------------------------------------------
    // Master Data
    // ----------------------------------------------------------------------------
    
    // Trucks
    Route::prefix('trucks')->group(function () {
        Route::get('/search', [TruckController::class, 'search']);
    });
    Route::apiResource('trucks', TruckController::class);

    // Suppliers
    Route::prefix('suppliers')->group(function () {
        Route::get('/by-type/{type}', [SupplierController::class, 'byType']);
    });
    Route::apiResource('suppliers', SupplierController::class);

    // Customers
    Route::prefix('customers')->group(function () {
        Route::get('/active', [CustomerController::class, 'active']);
        Route::get('/search', [CustomerController::class, 'search']);
    });
    Route::apiResource('customers', CustomerController::class);

    // TBS Prices
    Route::prefix('tbs-prices')->group(function () {
        Route::get('/today', [TbsPriceController::class, 'today']);
        Route::get('/latest', [TbsPriceController::class, 'latest']);
        Route::get('/by-date/{date}', [TbsPriceController::class, 'byDate']);
        Route::get('/history', [TbsPriceController::class, 'history']);
    });
    Route::apiResource('tbs-prices', TbsPriceController::class);

    // ----------------------------------------------------------------------------
    // Queue Management
    // ----------------------------------------------------------------------------
    Route::prefix('queues')->group(function () {
        Route::get('/active', [QueueController::class, 'active']);
        Route::get('/processing', [QueueController::class, 'processing']);
        Route::get('/by-bank/{bank}', [QueueController::class, 'byBank']);
        Route::get('/today', [QueueController::class, 'today']);
        Route::get('/statistics', [QueueController::class, 'statistics']);
        Route::put('/{id}/status', [QueueController::class, 'updateStatus']);
    });
    Route::apiResource('queues', QueueController::class);

    // ----------------------------------------------------------------------------
    // Weighing (Weighbridge)
    // ----------------------------------------------------------------------------
    Route::prefix('weighings')->group(function () {
        Route::post('/{id}/weigh-in', [WeighingController::class, 'weighIn']);
        Route::post('/{id}/weigh-out', [WeighingController::class, 'weighOut']);
        Route::post('/{id}/complete', [WeighingController::class, 'complete']);
        Route::get('/today', [WeighingController::class, 'today']);
        Route::get('/pending', [WeighingController::class, 'pending']);
        Route::get('/by-queue/{queueId}', [WeighingController::class, 'byQueue']);
    });
    Route::apiResource('weighings', WeighingController::class);

    // ----------------------------------------------------------------------------
    // Sortation
    // ----------------------------------------------------------------------------
    Route::prefix('sortations')->group(function () {
        Route::get('/by-weighing/{weighingId}', [SortationController::class, 'byWeighing']);
        Route::get('/today', [SortationController::class, 'today']);
        Route::get('/performance', [SortationController::class, 'performance']);
    });
    Route::apiResource('sortations', SortationController::class);

    // ----------------------------------------------------------------------------
    // Production
    // ----------------------------------------------------------------------------
    Route::prefix('productions')->group(function () {
        Route::get('/today', [ProductionController::class, 'today']);
        Route::get('/by-date/{date}', [ProductionController::class, 'byDate']);
        Route::get('/statistics', [ProductionController::class, 'statistics']);
        Route::get('/efficiency', [ProductionController::class, 'efficiency']);
    });
    Route::apiResource('productions', ProductionController::class);

    // ----------------------------------------------------------------------------
    // Stock Management
    // ----------------------------------------------------------------------------
    
    // CPO Stock
    Route::prefix('stock/cpo')->group(function () {
        Route::get('/', [StockController::class, 'indexCpo']);
        Route::post('/', [StockController::class, 'storeCpo']);
        Route::get('/summary', [StockController::class, 'summaryCpo']);
        Route::get('/by-tank', [StockController::class, 'byTankCpo']);
        Route::get('/available', [StockController::class, 'availableCpo']);
        Route::get('/movement', [StockController::class, 'movementCpo']);
        Route::get('/{id}', [StockController::class, 'showCpo']);
        Route::put('/{id}', [StockController::class, 'updateCpo']);
    });

    // Kernel Stock
    Route::prefix('stock/kernel')->group(function () {
        Route::get('/', [StockController::class, 'indexKernel']);
        Route::post('/', [StockController::class, 'storeKernel']);
        Route::get('/summary', [StockController::class, 'summaryKernel']);
        Route::get('/available', [StockController::class, 'availableKernel']);
        Route::get('/{id}', [StockController::class, 'showKernel']);
    });

    // Shell Stock
    Route::prefix('stock/shell')->group(function () {
        Route::get('/', [StockController::class, 'indexShell']);
        Route::post('/', [StockController::class, 'storeShell']);
        Route::get('/summary', [StockController::class, 'summaryShell']);
        Route::get('/available', [StockController::class, 'availableShell']);
        Route::get('/{id}', [StockController::class, 'showShell']);
    });

    // Stock Opname
    Route::prefix('stock-opnames')->group(function () {
        Route::post('/{id}/verify', [StockOpnameController::class, 'verify']);
        Route::get('/latest', [StockOpnameController::class, 'latest']);
        Route::get('/by-date/{date}', [StockOpnameController::class, 'byDate']);
    });
    Route::apiResource('stock-opnames', StockOpnameController::class);

    // Stock Adjustments
    Route::prefix('stock-adjustments')->group(function () {
        Route::post('/{id}/approve', [StockAdjustmentController::class, 'approve']);
        Route::post('/{id}/reject', [StockAdjustmentController::class, 'reject']);
        Route::get('/pending', [StockAdjustmentController::class, 'pending']);
    });
    Route::apiResource('stock-adjustments', StockAdjustmentController::class);

    // ----------------------------------------------------------------------------
    // Sales
    // ----------------------------------------------------------------------------
    Route::prefix('sales')->group(function () {
        Route::post('/{id}/deliver', [SalesController::class, 'deliver']);
        Route::post('/{id}/complete', [SalesController::class, 'complete']);
        Route::get('/today', [SalesController::class, 'today']);
        Route::get('/pending', [SalesController::class, 'pending']);
        Route::get('/by-customer/{customerId}', [SalesController::class, 'byCustomer']);
        Route::get('/statistics', [SalesController::class, 'statistics']);
    });
    Route::apiResource('sales', SalesController::class);
});
