<?php

/**
 * API 路由定義
 *
 * 所有 API 路由都使用 /api/v1/ 前綴
 * 使用 Laravel Sanctum 進行 Token 認證
 */

use App\Http\Controllers\Api\V1\AuditLogController;
// 基礎資料模組 Controllers
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CashierShiftController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerLevelController;
use App\Http\Controllers\Api\V1\GoodsIssueController;
use App\Http\Controllers\Api\V1\GoodsReceiptController;
use App\Http\Controllers\Api\V1\HoldOrderController;
use App\Http\Controllers\Api\V1\ImportExportController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\InventoryMovementController;
// 銷售模組 Controllers
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\NumberSequenceController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\PointsLogController;
// 庫存模組 Controllers
use App\Http\Controllers\Api\V1\ProductBarcodeController;
use App\Http\Controllers\Api\V1\ProductBundleController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\ProductVariantController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\PurchaseReceiptController;
// 採購模組 Controllers
use App\Http\Controllers\Api\V1\PurchaseReturnController;
use App\Http\Controllers\Api\V1\PurchaseSuggestionController;
use App\Http\Controllers\Api\V1\RefundController;
use App\Http\Controllers\Api\V1\Reports\CustomerReportController;
// 報表模組 Controllers
use App\Http\Controllers\Api\V1\Reports\DashboardController;
use App\Http\Controllers\Api\V1\Reports\InventoryReportController;
use App\Http\Controllers\Api\V1\Reports\ProfitReportController;
use App\Http\Controllers\Api\V1\Reports\PurchaseReportController;
use App\Http\Controllers\Api\V1\Reports\SalesReportController;
use App\Http\Controllers\Api\V1\RoleController;
// 系統管理 Controllers
use App\Http\Controllers\Api\V1\StockAdjustmentController;
use App\Http\Controllers\Api\V1\StockCountController;
use App\Http\Controllers\Api\V1\StockTransferController;
use App\Http\Controllers\Api\V1\StoreController;
// 進階功能 Controllers
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\SupplierPriceController;
use App\Http\Controllers\Api\V1\SystemSettingController;
use App\Http\Controllers\Api\V1\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API 路由
|--------------------------------------------------------------------------
|
| 所有路由都使用 /api/v1/ 前綴
| 公開路由（不需認證）：登入、註冊
| 受保護路由（需要認證）：其他所有 API
|
*/

// API v1 路由群組
Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | 認證相關路由（公開）
    |--------------------------------------------------------------------------
    */
    Route::controller(AuthController::class)->group(function () {
        Route::post('/auth/register', 'register');      // 註冊
        Route::post('/auth/login', 'login');            // 登入
    });

    /*
    |--------------------------------------------------------------------------
    | 需要認證的路由
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        // 認證相關
        Route::controller(AuthController::class)->group(function () {
            Route::post('/auth/logout', 'logout');          // 登出
            Route::get('/auth/me', 'me');                   // 取得目前使用者
            Route::put('/auth/password', 'updatePassword'); // 更新密碼
        });

        /*
        |--------------------------------------------------------------------------
        | 基礎資料模組
        |--------------------------------------------------------------------------
        */

        // 門市管理
        Route::apiResource('stores', StoreController::class);

        // 倉庫管理
        Route::apiResource('warehouses', WarehouseController::class);

        // 商品分類管理（支援樹狀結構）
        Route::get('categories/tree', [CategoryController::class, 'tree']);                      // 取得分類樹（放在 apiResource 前避免被攔截）
        Route::apiResource('categories', CategoryController::class);
        Route::get('categories/{category}/children', [CategoryController::class, 'children']);  // 取得子分類

        // 會員等級管理
        Route::apiResource('customer-levels', CustomerLevelController::class);

        // 供應商管理
        Route::apiResource('suppliers', SupplierController::class);

        // 商品管理
        Route::apiResource('products', ProductController::class);
        Route::post('products/{product}/variants', [ProductVariantController::class, 'store']);       // 新增規格
        Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update']); // 更新規格
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']); // 刪除規格
        Route::post('products/{product}/barcodes', [ProductBarcodeController::class, 'store']);       // 新增條碼
        Route::delete('products/{product}/barcodes/{barcode}', [ProductBarcodeController::class, 'destroy']); // 刪除條碼

        // 會員管理
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/points', [PointsLogController::class, 'index']);  // 查詢會員點數紀錄
        Route::post('customers/{customer}/points', [PointsLogController::class, 'store']); // 手動調整點數

        /*
        |--------------------------------------------------------------------------
        | 銷售模組
        |--------------------------------------------------------------------------
        */

        // 促銷活動管理
        Route::apiResource('promotions', PromotionController::class);
        Route::put('promotions/{promotion}/toggle', [PromotionController::class, 'toggle']); // 啟用/停用促銷

        // 訂單管理
        Route::apiResource('orders', OrderController::class);
        Route::get('orders/{order}/items', [OrderController::class, 'items']);           // 取得訂單明細
        Route::post('orders/{order}/payments', [PaymentController::class, 'store']);     // 新增付款
        Route::get('orders/{order}/payments', [PaymentController::class, 'index']);      // 查詢付款紀錄
        Route::put('orders/{order}/cancel', [OrderController::class, 'cancel']);         // 取消訂單
        Route::put('orders/{order}/complete', [OrderController::class, 'complete']);     // 完成訂單

        // 退貨管理
        Route::apiResource('refunds', RefundController::class);
        Route::get('refunds/{refund}/items', [RefundController::class, 'items']);        // 取得退貨明細
        Route::put('refunds/{refund}/approve', [RefundController::class, 'approve']);    // 審核通過
        Route::put('refunds/{refund}/reject', [RefundController::class, 'reject']);      // 審核駁回
        Route::put('refunds/{refund}/complete', [RefundController::class, 'complete']);  // 完成退貨

        // 收銀班別管理
        Route::apiResource('cashier-shifts', CashierShiftController::class)->only(['index', 'show', 'store']);
        Route::put('cashier-shifts/{cashierShift}/close', [CashierShiftController::class, 'close']); // 關班

        // 發票管理
        Route::apiResource('invoices', InvoiceController::class)->only(['index', 'show']);
        Route::put('invoices/{invoice}/void', [InvoiceController::class, 'void']);       // 作廢發票

        /*
        |--------------------------------------------------------------------------
        | 庫存模組
        |--------------------------------------------------------------------------
        */

        // 庫存查詢
        Route::get('inventory', [InventoryController::class, 'index']);                   // 查詢庫存清單
        Route::get('inventory/{product}', [InventoryController::class, 'show']);          // 查詢商品庫存
        Route::get('inventory/{product}/movements', [InventoryMovementController::class, 'index']); // 查詢異動紀錄

        // 進貨單管理
        Route::apiResource('goods-receipts', GoodsReceiptController::class);
        Route::get('goods-receipts/{goodsReceipt}/items', [GoodsReceiptController::class, 'items']); // 取得明細
        Route::put('goods-receipts/{goodsReceipt}/confirm', [GoodsReceiptController::class, 'confirm']); // 確認入庫

        // 出貨單管理
        Route::apiResource('goods-issues', GoodsIssueController::class);
        Route::get('goods-issues/{goodsIssue}/items', [GoodsIssueController::class, 'items']); // 取得明細
        Route::put('goods-issues/{goodsIssue}/confirm', [GoodsIssueController::class, 'confirm']); // 確認出庫

        // 盤點管理
        Route::apiResource('stock-counts', StockCountController::class);
        Route::get('stock-counts/{stockCount}/items', [StockCountController::class, 'items']); // 取得盤點明細
        Route::post('stock-counts/{stockCount}/items', [StockCountController::class, 'addItem']); // 新增盤點項目
        Route::put('stock-counts/{stockCount}/items/{item}', [StockCountController::class, 'updateItem']); // 更新盤點數量
        Route::put('stock-counts/{stockCount}/complete', [StockCountController::class, 'complete']); // 完成盤點

        // 調撥管理
        Route::apiResource('stock-transfers', StockTransferController::class);
        Route::get('stock-transfers/{stockTransfer}/items', [StockTransferController::class, 'items']); // 取得明細
        Route::put('stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve']); // 審核通過
        Route::put('stock-transfers/{stockTransfer}/ship', [StockTransferController::class, 'ship']); // 出貨
        Route::put('stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive']); // 收貨

        // 庫存調整
        Route::apiResource('stock-adjustments', StockAdjustmentController::class);
        Route::put('stock-adjustments/{stockAdjustment}/approve', [StockAdjustmentController::class, 'approve']); // 審核

        /*
        |--------------------------------------------------------------------------
        | 採購模組
        |--------------------------------------------------------------------------
        */

        // 採購單管理
        Route::apiResource('purchase-orders', PurchaseOrderController::class);
        Route::get('purchase-orders/{purchaseOrder}/items', [PurchaseOrderController::class, 'items']); // 取得明細
        Route::put('purchase-orders/{purchaseOrder}/submit', [PurchaseOrderController::class, 'submit']); // 送審
        Route::put('purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve']); // 審核通過
        Route::put('purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject']); // 審核駁回
        Route::put('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']); // 取消

        // 採購收貨管理
        Route::apiResource('purchase-receipts', PurchaseReceiptController::class);
        Route::get('purchase-receipts/{purchaseReceipt}/items', [PurchaseReceiptController::class, 'items']); // 取得明細
        Route::put('purchase-receipts/{purchaseReceipt}/confirm', [PurchaseReceiptController::class, 'confirm']); // 確認收貨

        // 採購退貨管理
        Route::apiResource('purchase-returns', PurchaseReturnController::class);
        Route::get('purchase-returns/{purchaseReturn}/items', [PurchaseReturnController::class, 'items']); // 取得明細
        Route::put('purchase-returns/{purchaseReturn}/approve', [PurchaseReturnController::class, 'approve']); // 審核
        Route::put('purchase-returns/{purchaseReturn}/ship', [PurchaseReturnController::class, 'ship']); // 出貨

        // 供應商報價管理
        Route::apiResource('supplier-prices', SupplierPriceController::class);
        Route::get('supplier-prices/{supplierPrice}/history', [SupplierPriceController::class, 'history']); // 報價歷史

        /*
        |--------------------------------------------------------------------------
        | 報表模組
        |--------------------------------------------------------------------------
        */

        // 儀表板報表
        Route::get('reports/dashboard', [DashboardController::class, 'index']);         // 儀表板總覽
        Route::get('reports/dashboard/today', [DashboardController::class, 'today']);   // 今日數據
        Route::get('reports/dashboard/trends', [DashboardController::class, 'trends']); // 趨勢數據

        // 銷售報表
        Route::get('reports/sales', [SalesReportController::class, 'index']);               // 銷售報表
        Route::get('reports/sales/by-product', [SalesReportController::class, 'byProduct']); // 商品銷售排行
        Route::get('reports/sales/by-category', [SalesReportController::class, 'byCategory']); // 分類銷售統計
        Route::get('reports/sales/by-time', [SalesReportController::class, 'byTime']);       // 時段銷售分析
        Route::get('reports/sales/export', [SalesReportController::class, 'export']);       // 匯出銷售報表

        // 庫存報表
        Route::get('reports/inventory', [InventoryReportController::class, 'index']);            // 庫存報表
        Route::get('reports/inventory/valuation', [InventoryReportController::class, 'valuation']); // 庫存價值分析
        Route::get('reports/inventory/turnover', [InventoryReportController::class, 'turnover']); // 庫存週轉率
        Route::get('reports/inventory/low-stock', [InventoryReportController::class, 'lowStock']); // 低庫存預警
        Route::get('reports/inventory/export', [InventoryReportController::class, 'export']);    // 匯出庫存報表

        // 採購報表
        Route::get('reports/purchase', [PurchaseReportController::class, 'index']);               // 採購報表
        Route::get('reports/purchase/by-supplier', [PurchaseReportController::class, 'bySupplier']); // 供應商採購分析
        Route::get('reports/purchase/by-product', [PurchaseReportController::class, 'byProduct']); // 商品採購統計
        Route::get('reports/purchase/export', [PurchaseReportController::class, 'export']);       // 匯出採購報表

        // 利潤報表
        Route::get('reports/profit', [ProfitReportController::class, 'index']);               // 利潤報表
        Route::get('reports/profit/by-product', [ProfitReportController::class, 'byProduct']); // 商品利潤分析
        Route::get('reports/profit/by-category', [ProfitReportController::class, 'byCategory']); // 分類利潤統計
        Route::get('reports/profit/margin', [ProfitReportController::class, 'margin']);        // 毛利率分析
        Route::get('reports/profit/export', [ProfitReportController::class, 'export']);        // 匯出利潤報表

        // 客戶報表
        Route::get('reports/customer', [CustomerReportController::class, 'index']);            // 客戶報表
        Route::get('reports/customer/rfm', [CustomerReportController::class, 'rfm']);          // RFM 分析
        Route::get('reports/customer/ranking', [CustomerReportController::class, 'ranking']);  // 客戶消費排行
        Route::get('reports/customer/retention', [CustomerReportController::class, 'retention']); // 客戶留存分析
        Route::get('reports/customer/export', [CustomerReportController::class, 'export']);    // 匯出客戶報表

        /*
        |--------------------------------------------------------------------------
        | 系統管理模組
        |--------------------------------------------------------------------------
        */

        // 角色管理
        Route::apiResource('roles', RoleController::class);
        Route::post('roles/{role}/permissions', [RoleController::class, 'permissions']); // 設定角色權限

        // 權限管理
        Route::get('permissions', [PermissionController::class, 'index']); // 權限列表

        // 系統設定
        Route::get('system-settings', [SystemSettingController::class, 'index']);         // 取得所有設定
        Route::get('system-settings/{key}', [SystemSettingController::class, 'show']);    // 取得單一設定
        Route::put('system-settings/{key}', [SystemSettingController::class, 'update']);  // 更新設定
        Route::post('system-settings/batch', [SystemSettingController::class, 'batch']);  // 批次更新設定

        // 操作日誌
        Route::get('audit-logs', [AuditLogController::class, 'index']);                   // 操作日誌列表
        Route::get('audit-logs/user/{userId}', [AuditLogController::class, 'byUser']);    // 使用者操作記錄
        Route::get('audit-logs/model/{model}', [AuditLogController::class, 'byModel']);   // 模型操作記錄
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);         // 日誌詳情

        /*
        |--------------------------------------------------------------------------
        | 進階功能模組
        |--------------------------------------------------------------------------
        */

        // 編號規則管理
        Route::apiResource('number-sequences', NumberSequenceController::class);
        Route::post('number-sequences/{numberSequence}/next', [NumberSequenceController::class, 'next']); // 取得下一個編號

        // 組合商品管理
        Route::apiResource('product-bundles', ProductBundleController::class);

        // 資料匯入匯出
        Route::post('import/products', [ImportExportController::class, 'importProducts']);   // 匯入商品
        Route::post('import/customers', [ImportExportController::class, 'importCustomers']); // 匯入客戶
        Route::post('import/suppliers', [ImportExportController::class, 'importSuppliers']); // 匯入供應商
        Route::get('export/products', [ImportExportController::class, 'exportProducts']);    // 匯出商品
        Route::get('export/customers', [ImportExportController::class, 'exportCustomers']);  // 匯出客戶
        Route::get('export/suppliers', [ImportExportController::class, 'exportSuppliers']);  // 匯出供應商
        Route::get('import/template/{type}', [ImportExportController::class, 'template']);   // 下載匯入範本

        // 暫存訂單管理
        Route::apiResource('hold-orders', HoldOrderController::class);
        Route::post('hold-orders/{holdOrder}/restore', [HoldOrderController::class, 'restore']); // 還原為正式訂單

        // 採購建議
        Route::get('purchase-suggestions', [PurchaseSuggestionController::class, 'index']);         // 採購建議列表
        Route::get('purchase-suggestions/generate', [PurchaseSuggestionController::class, 'generate']); // 產生採購建議
        Route::post('purchase-suggestions/to-order', [PurchaseSuggestionController::class, 'toOrder']); // 建議轉採購單
    });
});
