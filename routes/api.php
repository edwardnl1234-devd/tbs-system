<?php

use App\Http\Controllers\ActivityLogController;
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
use App\Http\Controllers\StockPurchaseController;
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
| Role Permissions:
| - admin: Full access - can view, add, edit, delete everything
| - manager: View only - cannot add, edit or delete
| - mandor: Operational - can add weighing, sortation, queue, production
| - accounting: Financial - can view sales/purchases/stock/customer/supplier/tbs-prices
|               can add sales and stock purchases, cannot delete anything
| - operator_timbangan: Can add weighing data, cannot edit/delete
|
*/

// ============================================================================
// Public Routes (No Authentication Required)
// ============================================================================

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Polling endpoints for display screens (public)
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
    // Auth Routes (All authenticated users)
    // ----------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // ----------------------------------------------------------------------------
    // Dashboard & Reports (All authenticated users can view)
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
    // User Management (Admin only)
    // ----------------------------------------------------------------------------
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // ----------------------------------------------------------------------------
    // Activity Logs (Admin only)
    // ----------------------------------------------------------------------------
    Route::middleware('role:admin')->group(function () {
        Route::prefix('activity-logs')->group(function () {
            Route::get('/', [ActivityLogController::class, 'index']);
            Route::get('/recent', [ActivityLogController::class, 'recent']);
            Route::get('/statistics', [ActivityLogController::class, 'statistics']);
            Route::get('/model/{modelType}/{modelId}', [ActivityLogController::class, 'forModel']);
            Route::get('/{id}', [ActivityLogController::class, 'show']);
        });
    });

    // ----------------------------------------------------------------------------
    // Master Data: Trucks
    // ----------------------------------------------------------------------------
    Route::prefix('trucks')->group(function () {
        Route::get('/search', [TruckController::class, 'search']);
    });
    // View: All roles
    Route::get('trucks', [TruckController::class, 'index']);
    Route::get('trucks/{truck}', [TruckController::class, 'show']);
    // Add: Admin, Accounting, Mandor, Operator Timbangan (not Manager)
    Route::middleware('role:admin,accounting,mandor,operator_timbangan')->group(function () {
        Route::post('trucks', [TruckController::class, 'store']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('trucks/{truck}', [TruckController::class, 'update']);
        Route::delete('trucks/{truck}', [TruckController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Master Data: Suppliers
    // ----------------------------------------------------------------------------
    Route::prefix('suppliers')->group(function () {
        Route::get('/by-type/{type}', [SupplierController::class, 'byType']);
    });
    // View: All roles
    Route::get('suppliers', [SupplierController::class, 'index']);
    Route::get('suppliers/{supplier}', [SupplierController::class, 'show']);
    // Add: Admin, Accounting (not Manager, not Operator Timbangan, not Mandor)
    Route::middleware('role:admin,accounting')->group(function () {
        Route::post('suppliers', [SupplierController::class, 'store']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Master Data: Customers
    // ----------------------------------------------------------------------------
    Route::prefix('customers')->group(function () {
        Route::get('/active', [CustomerController::class, 'active']);
        Route::get('/search', [CustomerController::class, 'search']);
    });
    // View: All roles
    Route::get('customers', [CustomerController::class, 'index']);
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
    // Add: Admin, Accounting (not Manager, not Operator Timbangan, not Mandor)
    Route::middleware('role:admin,accounting')->group(function () {
        Route::post('customers', [CustomerController::class, 'store']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('customers/{customer}', [CustomerController::class, 'update']);
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // TBS Prices
    // ----------------------------------------------------------------------------
    Route::prefix('tbs-prices')->group(function () {
        Route::get('/today', [TbsPriceController::class, 'today']);
        Route::get('/latest', [TbsPriceController::class, 'latest']);
        Route::get('/by-date/{date}', [TbsPriceController::class, 'byDate']);
        Route::get('/history', [TbsPriceController::class, 'history']);
        Route::get('/sources', [TbsPriceController::class, 'sources']);
    });
    // View: All roles
    Route::get('tbs-prices', [TbsPriceController::class, 'index']);
    Route::get('tbs-prices/{tbs_price}', [TbsPriceController::class, 'show']);
    // Add/Edit/Delete: Admin only (prices are critical)
    Route::middleware('role:admin')->group(function () {
        Route::post('tbs-prices', [TbsPriceController::class, 'store']);
        Route::post('tbs-prices/fetch-online', [TbsPriceController::class, 'fetchOnline']);
        Route::put('tbs-prices/{tbs_price}', [TbsPriceController::class, 'update']);
        Route::delete('tbs-prices/{tbs_price}', [TbsPriceController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Queue Management
    // ----------------------------------------------------------------------------
    Route::prefix('queues')->group(function () {
        Route::get('/active', [QueueController::class, 'active']);
        Route::get('/processing', [QueueController::class, 'processing']);
        Route::get('/by-bank/{bank}', [QueueController::class, 'byBank']);
        Route::get('/today', [QueueController::class, 'today']);
        Route::get('/statistics', [QueueController::class, 'statistics']);
    });
    // View: All roles
    Route::get('queues', [QueueController::class, 'index']);
    Route::get('queues/{queue}', [QueueController::class, 'show']);
    // Add: Admin, Mandor, Operator Timbangan
    Route::middleware('role:admin,mandor,operator_timbangan')->group(function () {
        Route::post('queues', [QueueController::class, 'store']);
        Route::put('queues/{id}/status', [QueueController::class, 'updateStatus']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('queues/{queue}', [QueueController::class, 'update']);
        Route::delete('queues/{queue}', [QueueController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Weighing (Weighbridge) - Primary for Operator Timbangan
    // ----------------------------------------------------------------------------
    Route::prefix('weighings')->group(function () {
        Route::get('/today', [WeighingController::class, 'today']);
        Route::get('/pending', [WeighingController::class, 'pending']);
        Route::get('/by-queue/{queueId}', [WeighingController::class, 'byQueue']);
    });
    // View: All roles
    Route::get('weighings', [WeighingController::class, 'index']);
    Route::get('weighings/{weighing}', [WeighingController::class, 'show']);
    // Add/Process Weighing: Admin, Mandor, Operator Timbangan
    Route::middleware('role:admin,mandor,operator_timbangan')->group(function () {
        Route::post('weighings', [WeighingController::class, 'store']);
        Route::post('weighings/{id}/weigh-in', [WeighingController::class, 'weighIn']);
        Route::post('weighings/{id}/weigh-out', [WeighingController::class, 'weighOut']);
        Route::post('weighings/{id}/complete', [WeighingController::class, 'complete']);
        Route::post('weighings/{id}/derivatives', [WeighingController::class, 'updateDerivatives']);
        Route::post('weighings/{id}/refresh-price', [WeighingController::class, 'refreshPrice']);
        Route::post('weighings/bulk-refresh-prices', [WeighingController::class, 'bulkRefreshPrices']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('weighings/{weighing}', [WeighingController::class, 'update']);
        Route::delete('weighings/{weighing}', [WeighingController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Sortation
    // ----------------------------------------------------------------------------
    Route::prefix('sortations')->group(function () {
        Route::get('/by-weighing/{weighingId}', [SortationController::class, 'byWeighing']);
        Route::get('/today', [SortationController::class, 'today']);
        Route::get('/performance', [SortationController::class, 'performance']);
    });
    // View: All roles
    Route::get('sortations', [SortationController::class, 'index']);
    Route::get('sortations/{sortation}', [SortationController::class, 'show']);
    // Add: Admin, Mandor, Operator Timbangan
    Route::middleware('role:admin,mandor,operator_timbangan')->group(function () {
        Route::post('sortations', [SortationController::class, 'store']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('sortations/{sortation}', [SortationController::class, 'update']);
        Route::delete('sortations/{sortation}', [SortationController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Production
    // ----------------------------------------------------------------------------
    Route::prefix('productions')->group(function () {
        Route::get('/today', [ProductionController::class, 'today']);
        Route::get('/by-date/{date}', [ProductionController::class, 'byDate']);
        Route::get('/statistics', [ProductionController::class, 'statistics']);
        Route::get('/efficiency', [ProductionController::class, 'efficiency']);
    });
    // View: All roles
    Route::get('productions', [ProductionController::class, 'index']);
    Route::get('productions/{production}', [ProductionController::class, 'show']);
    // Add: Admin, Mandor, Operator Timbangan
    Route::middleware('role:admin,mandor,operator_timbangan')->group(function () {
        Route::post('productions', [ProductionController::class, 'store']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('productions/{production}', [ProductionController::class, 'update']);
        Route::delete('productions/{production}', [ProductionController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Stock Management - CPO
    // ----------------------------------------------------------------------------
    Route::prefix('stock/cpo')->group(function () {
        Route::get('/', [StockController::class, 'indexCpo']);
        Route::get('/summary', [StockController::class, 'summaryCpo']);
        Route::get('/by-tank', [StockController::class, 'byTankCpo']);
        Route::get('/available', [StockController::class, 'availableCpo']);
        Route::get('/movement', [StockController::class, 'movementCpo']);
        Route::get('/{id}', [StockController::class, 'showCpo']);
        // Add: Admin, Accounting
        Route::middleware('role:admin,accounting')->group(function () {
            Route::post('/', [StockController::class, 'storeCpo']);
        });
        // Edit: Admin only
        Route::middleware('role:admin')->group(function () {
            Route::put('/{id}', [StockController::class, 'updateCpo']);
        });
    });

    // ----------------------------------------------------------------------------
    // Stock Management - Kernel
    // ----------------------------------------------------------------------------
    Route::prefix('stock/kernel')->group(function () {
        Route::get('/', [StockController::class, 'indexKernel']);
        Route::get('/summary', [StockController::class, 'summaryKernel']);
        Route::get('/available', [StockController::class, 'availableKernel']);
        Route::get('/{id}', [StockController::class, 'showKernel']);
        // Add: Admin, Accounting
        Route::middleware('role:admin,accounting')->group(function () {
            Route::post('/', [StockController::class, 'storeKernel']);
        });
    });

    // ----------------------------------------------------------------------------
    // Stock Management - Shell
    // ----------------------------------------------------------------------------
    Route::prefix('stock/shell')->group(function () {
        Route::get('/', [StockController::class, 'indexShell']);
        Route::get('/summary', [StockController::class, 'summaryShell']);
        Route::get('/available', [StockController::class, 'availableShell']);
        Route::get('/{id}', [StockController::class, 'showShell']);
        // Add: Admin, Accounting
        Route::middleware('role:admin,accounting')->group(function () {
            Route::post('/', [StockController::class, 'storeShell']);
        });
    });

    // ----------------------------------------------------------------------------
    // Stock Purchase from Supplier (Pembelian Stock)
    // Accessible by: Admin, Accounting, Finance
    // ----------------------------------------------------------------------------
    Route::prefix('stock-purchases')->group(function () {
        // View: Admin, Manager, Accounting
        Route::middleware('role:admin,manager,accounting')->group(function () {
            Route::get('/', [StockPurchaseController::class, 'index']);
            Route::get('/summary', [StockPurchaseController::class, 'summary']);
            Route::get('/history', [StockPurchaseController::class, 'history']);
            Route::get('/suppliers', [StockPurchaseController::class, 'getSuppliers']);
            Route::get('/by-supplier/{supplierId}', [StockPurchaseController::class, 'bySupplier']);
        });
        // Add: Admin, Accounting
        Route::middleware('role:admin,accounting')->group(function () {
            Route::post('/cpo', [StockPurchaseController::class, 'storeCpo']);
            Route::post('/kernel', [StockPurchaseController::class, 'storeKernel']);
            Route::post('/shell', [StockPurchaseController::class, 'storeShell']);
        });
        // Update Status: Admin only
        Route::middleware('role:admin')->group(function () {
            Route::patch('/cpo/{id}/status', [StockPurchaseController::class, 'updateCpoStatus']);
            Route::patch('/kernel/{id}/status', [StockPurchaseController::class, 'updateKernelStatus']);
            Route::patch('/shell/{id}/status', [StockPurchaseController::class, 'updateShellStatus']);
            // Delete: Admin only
            Route::delete('/cpo/{id}', [StockPurchaseController::class, 'destroyCpo']);
            Route::delete('/kernel/{id}', [StockPurchaseController::class, 'destroyKernel']);
            Route::delete('/shell/{id}', [StockPurchaseController::class, 'destroyShell']);
        });
    });

    // ----------------------------------------------------------------------------
    // Stock Opname
    // ----------------------------------------------------------------------------
    Route::prefix('stock-opnames')->group(function () {
        Route::get('/latest', [StockOpnameController::class, 'latest']);
        Route::get('/by-date/{date}', [StockOpnameController::class, 'byDate']);
    });
    // View: All roles
    Route::get('stock-opnames', [StockOpnameController::class, 'index']);
    Route::get('stock-opnames/{stock_opname}', [StockOpnameController::class, 'show']);
    // Add: Admin, Accounting
    Route::middleware('role:admin,accounting')->group(function () {
        Route::post('stock-opnames', [StockOpnameController::class, 'store']);
    });
    // Verify/Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::post('stock-opnames/{id}/verify', [StockOpnameController::class, 'verify']);
        Route::put('stock-opnames/{stock_opname}', [StockOpnameController::class, 'update']);
        Route::delete('stock-opnames/{stock_opname}', [StockOpnameController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Stock Adjustments
    // ----------------------------------------------------------------------------
    Route::prefix('stock-adjustments')->group(function () {
        Route::get('/pending', [StockAdjustmentController::class, 'pending']);
    });
    // View: All roles
    Route::get('stock-adjustments', [StockAdjustmentController::class, 'index']);
    Route::get('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'show']);
    // Add: Admin, Accounting
    Route::middleware('role:admin,accounting')->group(function () {
        Route::post('stock-adjustments', [StockAdjustmentController::class, 'store']);
    });
    // Approve/Reject/Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::post('stock-adjustments/{id}/approve', [StockAdjustmentController::class, 'approve']);
        Route::post('stock-adjustments/{id}/reject', [StockAdjustmentController::class, 'reject']);
        Route::put('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'update']);
        Route::delete('stock-adjustments/{stock_adjustment}', [StockAdjustmentController::class, 'destroy']);
    });

    // ----------------------------------------------------------------------------
    // Sales (Penjualan)
    // Accessible by: Admin, Manager (view), Accounting, Finance
    // ----------------------------------------------------------------------------
    Route::prefix('sales')->group(function () {
        Route::get('/today', [SalesController::class, 'today']);
        Route::get('/pending', [SalesController::class, 'pending']);
        Route::get('/by-customer/{customerId}', [SalesController::class, 'byCustomer']);
        Route::get('/statistics', [SalesController::class, 'statistics']);
    });
    // View: Admin, Manager, Accounting
    Route::middleware('role:admin,manager,accounting')->group(function () {
        Route::get('sales', [SalesController::class, 'index']);
        Route::get('sales/{sale}', [SalesController::class, 'show']);
    });
    // Add: Admin, Accounting
    Route::middleware('role:admin,accounting')->group(function () {
        Route::post('sales', [SalesController::class, 'store']);
        Route::post('sales/{id}/deliver', [SalesController::class, 'deliver']);
        Route::post('sales/{id}/complete', [SalesController::class, 'complete']);
    });
    // Edit/Delete: Admin only
    Route::middleware('role:admin')->group(function () {
        Route::put('sales/{sale}', [SalesController::class, 'update']);
        Route::delete('sales/{sale}', [SalesController::class, 'destroy']);
    });
});
