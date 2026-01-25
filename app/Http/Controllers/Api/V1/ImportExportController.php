<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * 資料匯入匯出控制器
 *
 * 處理商品、客戶、供應商的匯入匯出
 */
class ImportExportController extends Controller
{
    use ApiResponse;

    /**
     * 匯入商品
     */
    public function importProducts(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $file = $request->file('file');
            $data = $this->parseCsv($file->getPathname());

            if (empty($data)) {
                return $this->error('檔案內容為空', 422);
            }

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // 第一行是標題

                $validator = Validator::make($row, [
                    'sku' => ['required', 'string', 'max:30'],
                    'name' => ['required', 'string', 'max:200'],
                    'category_id' => ['nullable', 'exists:categories,id'],
                    'cost_price' => ['nullable', 'numeric', 'min:0'],
                    'selling_price' => ['required', 'numeric', 'min:0'],
                ]);

                if ($validator->fails()) {
                    $errors[] = "第 {$rowNumber} 行: ".implode(', ', $validator->errors()->all());

                    continue;
                }

                Product::updateOrCreate(
                    ['sku' => $row['sku']],
                    [
                        'name' => $row['name'],
                        'category_id' => $row['category_id'] ?? null,
                        'cost_price' => $row['cost_price'] ?? 0,
                        'selling_price' => $row['selling_price'],
                        'unit' => $row['unit'] ?? 'PCS',
                        'status' => $row['status'] ?? 'ACTIVE',
                    ]
                );

                $imported++;
            }

            DB::commit();

            return $this->success([
                'imported' => $imported,
                'errors' => $errors,
            ], "成功匯入 {$imported} 筆商品資料");
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('匯入商品失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯入客戶
     */
    public function importCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $file = $request->file('file');
            $data = $this->parseCsv($file->getPathname());

            if (empty($data)) {
                return $this->error('檔案內容為空', 422);
            }

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;

                $validator = Validator::make($row, [
                    'name' => ['required', 'string', 'max:100'],
                    'phone' => ['required', 'string', 'max:20'],
                ]);

                if ($validator->fails()) {
                    $errors[] = "第 {$rowNumber} 行: ".implode(', ', $validator->errors()->all());

                    continue;
                }

                Customer::updateOrCreate(
                    ['phone' => $row['phone']],
                    [
                        'name' => $row['name'],
                        'email' => $row['email'] ?? null,
                        'address' => $row['address'] ?? null,
                        'gender' => $row['gender'] ?? null,
                        'birthday' => isset($row['birthday']) && $row['birthday'] ? $row['birthday'] : null,
                        'status' => $row['status'] ?? 'ACTIVE',
                    ]
                );

                $imported++;
            }

            DB::commit();

            return $this->success([
                'imported' => $imported,
                'errors' => $errors,
            ], "成功匯入 {$imported} 筆客戶資料");
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('匯入客戶失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯入供應商
     */
    public function importSuppliers(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $file = $request->file('file');
            $data = $this->parseCsv($file->getPathname());

            if (empty($data)) {
                return $this->error('檔案內容為空', 422);
            }

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($data as $index => $row) {
                $rowNumber = $index + 2;

                $validator = Validator::make($row, [
                    'code' => ['required', 'string', 'max:20'],
                    'name' => ['required', 'string', 'max:100'],
                ]);

                if ($validator->fails()) {
                    $errors[] = "第 {$rowNumber} 行: ".implode(', ', $validator->errors()->all());

                    continue;
                }

                Supplier::updateOrCreate(
                    ['code' => $row['code']],
                    [
                        'name' => $row['name'],
                        'contact_name' => $row['contact_name'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'email' => $row['email'] ?? null,
                        'address' => $row['address'] ?? null,
                        'tax_id' => $row['tax_id'] ?? null,
                        'status' => $row['status'] ?? 'ACTIVE',
                    ]
                );

                $imported++;
            }

            DB::commit();

            return $this->success([
                'imported' => $imported,
                'errors' => $errors,
            ], "成功匯入 {$imported} 筆供應商資料");
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->serverError('匯入供應商失敗：'.$e->getMessage());
        }
    }

    /**
     * 匯出商品
     */
    public function exportProducts(Request $request): Response
    {
        $query = Product::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('sku')->get();

        $filename = 'products_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['sku', 'name', 'category_id', 'category_name', 'cost_price', 'selling_price', 'unit', 'status']);

            foreach ($products as $product) {
                fputcsv($file, [
                    $product->sku,
                    $product->name,
                    $product->category_id,
                    $product->category?->name ?? '',
                    $product->cost_price,
                    $product->selling_price,
                    $product->unit,
                    $product->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 匯出客戶
     */
    public function exportCustomers(Request $request): Response
    {
        $query = Customer::with('level');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->orderBy('member_no')->get();

        $filename = 'customers_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['member_no', 'name', 'phone', 'email', 'address', 'gender', 'birthday', 'level_name', 'total_spending', 'available_points', 'status']);

            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->member_no,
                    $customer->name,
                    $customer->phone,
                    $customer->email,
                    $customer->address,
                    $customer->gender,
                    $customer->birthday?->format('Y-m-d') ?? '',
                    $customer->level?->name ?? '',
                    $customer->total_spending,
                    $customer->available_points,
                    $customer->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 匯出供應商
     */
    public function exportSuppliers(Request $request): Response
    {
        $query = Supplier::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $suppliers = $query->orderBy('code')->get();

        $filename = 'suppliers_'.date('Y-m-d_His').'.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($suppliers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, ['code', 'name', 'contact_name', 'phone', 'email', 'address', 'tax_id', 'status']);

            foreach ($suppliers as $supplier) {
                fputcsv($file, [
                    $supplier->code,
                    $supplier->name,
                    $supplier->contact_name,
                    $supplier->phone,
                    $supplier->email,
                    $supplier->address,
                    $supplier->tax_id,
                    $supplier->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 下載匯入範本
     */
    public function template(string $type): Response|JsonResponse
    {
        $templates = [
            'products' => [
                'headers' => ['sku', 'name', 'category_id', 'cost_price', 'selling_price', 'unit', 'status'],
                'example' => ['SKU001', '範例商品', '1', '100', '150', 'PCS', 'ACTIVE'],
            ],
            'customers' => [
                'headers' => ['name', 'phone', 'email', 'address', 'gender', 'birthday', 'status'],
                'example' => ['範例客戶', '0912345678', 'test@example.com', '台北市中正區', 'M', '1990-01-01', 'ACTIVE'],
            ],
            'suppliers' => [
                'headers' => ['code', 'name', 'contact_name', 'phone', 'email', 'address', 'tax_id', 'status'],
                'example' => ['SUP001', '範例供應商', '聯絡人', '02-12345678', 'supplier@example.com', '台北市信義區', '12345678', 'ACTIVE'],
            ],
        ];

        if (! isset($templates[$type])) {
            return $this->notFound('範本類型不存在');
        }

        $template = $templates[$type];
        $filename = "{$type}_template.csv";
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($template) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

            fputcsv($file, $template['headers']);
            fputcsv($file, $template['example']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * 解析 CSV 檔案
     */
    protected function parseCsv(string $filepath): array
    {
        $data = [];
        $headers = [];

        if (($handle = fopen($filepath, 'r')) !== false) {
            $row = 0;
            while (($line = fgetcsv($handle)) !== false) {
                // 處理 BOM
                if ($row === 0) {
                    $line[0] = preg_replace('/^\xEF\xBB\xBF/', '', $line[0]);
                    $headers = array_map('trim', $line);
                } else {
                    if (count($line) === count($headers)) {
                        $data[] = array_combine($headers, array_map('trim', $line));
                    }
                }
                $row++;
            }
            fclose($handle);
        }

        return $data;
    }
}
