<?php

namespace App\Http\Controllers;

use App\Http\Resources\StockCpoResource;
use App\Http\Resources\StockKernelResource;
use App\Http\Resources\StockShellResource;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Models\Supplier;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StockPurchaseController extends Controller
{
    use ApiResponse;

    /**
     * Get all stock purchases (from suppliers)
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->get('type', 'all'); // cpo, kernel, shell, all

        $results = [];

        if ($type === 'cpo' || $type === 'all') {
            $cpoPurchases = StockCpo::with('supplier')
                ->where('stock_type', 'purchase')
                ->whereNotNull('supplier_id')
                ->orderBy('stock_date', 'desc')
                ->get();
            $results['cpo'] = StockCpoResource::collection($cpoPurchases);
        }

        if ($type === 'kernel' || $type === 'all') {
            $kernelPurchases = StockKernel::with('supplier')
                ->where('stock_type', 'purchase')
                ->whereNotNull('supplier_id')
                ->orderBy('stock_date', 'desc')
                ->get();
            $results['kernel'] = StockKernelResource::collection($kernelPurchases);
        }

        if ($type === 'shell' || $type === 'all') {
            $shellPurchases = StockShell::with('supplier')
                ->where('stock_type', 'purchase')
                ->whereNotNull('supplier_id')
                ->orderBy('stock_date', 'desc')
                ->get();
            $results['shell'] = StockShellResource::collection($shellPurchases);
        }

        return $this->success($results);
    }

    /**
     * Get purchase summary
     */
    public function summary(): JsonResponse
    {
        $summary = [
            'cpo' => [
                'total_quantity' => StockCpo::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->where('movement_type', 'in')
                    ->sum('quantity'),
                'total_value' => StockCpo::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->selectRaw('SUM(quantity * COALESCE(purchase_price, 0)) as total')
                    ->value('total') ?? 0,
                'count' => StockCpo::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->count(),
            ],
            'kernel' => [
                'total_quantity' => StockKernel::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->sum('quantity'),
                'total_value' => StockKernel::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->selectRaw('SUM(quantity * COALESCE(purchase_price, 0)) as total')
                    ->value('total') ?? 0,
                'count' => StockKernel::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->count(),
            ],
            'shell' => [
                'total_quantity' => StockShell::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->sum('quantity'),
                'total_value' => StockShell::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->selectRaw('SUM(quantity * COALESCE(purchase_price, 0)) as total')
                    ->value('total') ?? 0,
                'count' => StockShell::where('stock_type', 'purchase')
                    ->whereNotNull('supplier_id')
                    ->count(),
            ],
        ];

        return $this->success($summary);
    }

    /**
     * Store CPO purchase from supplier
     */
    public function storeCpo(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.01',
            'purchase_price' => 'required|numeric|min:0',
            'quality_grade' => 'nullable|in:premium,standard,low',
            'tank_number' => 'nullable|string|max:20',
            'tank_capacity' => 'nullable|numeric|min:0',
            'reference_number' => 'nullable|string|max:50',
            'stock_date' => 'required|date',
            'expiry_date' => 'nullable|date|after:stock_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $stock = StockCpo::create([
                'supplier_id' => $request->supplier_id,
                'quantity' => $request->quantity,
                'purchase_price' => $request->purchase_price,
                'quality_grade' => $request->quality_grade,
                'tank_number' => $request->tank_number,
                'tank_capacity' => $request->tank_capacity,
                'stock_type' => 'purchase',
                'movement_type' => 'in',
                'reference_number' => $request->reference_number ?? $this->generateReferenceNumber('CPO'),
                'stock_date' => $request->stock_date,
                'expiry_date' => $request->expiry_date,
                'status' => 'available',
                'notes' => $request->notes,
            ]);

            DB::commit();

            return $this->created(
                new StockCpoResource($stock->load('supplier')),
                'Pembelian CPO berhasil dicatat'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Gagal mencatat pembelian CPO: ' . $e->getMessage());
        }
    }

    /**
     * Store Kernel purchase from supplier
     */
    public function storeKernel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.01',
            'purchase_price' => 'required|numeric|min:0',
            'quality_grade' => 'nullable|in:premium,standard,low',
            'location' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:50',
            'stock_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $stock = StockKernel::create([
                'supplier_id' => $request->supplier_id,
                'quantity' => $request->quantity,
                'purchase_price' => $request->purchase_price,
                'quality_grade' => $request->quality_grade,
                'location' => $request->location,
                'stock_type' => 'purchase',
                'status' => 'available',
                'stock_date' => $request->stock_date,
            ]);

            DB::commit();

            return $this->created(
                new StockKernelResource($stock->load('supplier')),
                'Pembelian Kernel berhasil dicatat'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Gagal mencatat pembelian Kernel: ' . $e->getMessage());
        }
    }

    /**
     * Store Shell purchase from supplier
     */
    public function storeShell(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|numeric|min:0.01',
            'purchase_price' => 'required|numeric|min:0',
            'location' => 'nullable|string|max:100',
            'reference_number' => 'nullable|string|max:50',
            'stock_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            DB::beginTransaction();

            $stock = StockShell::create([
                'supplier_id' => $request->supplier_id,
                'quantity' => $request->quantity,
                'purchase_price' => $request->purchase_price,
                'location' => $request->location,
                'stock_type' => 'purchase',
                'status' => 'available',
                'stock_date' => $request->stock_date,
            ]);

            DB::commit();

            return $this->created(
                new StockShellResource($stock->load('supplier')),
                'Pembelian Shell berhasil dicatat'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Gagal mencatat pembelian Shell: ' . $e->getMessage());
        }
    }

    /**
     * Get suppliers that can supply stock
     */
    public function getSuppliers(): JsonResponse
    {
        $suppliers = Supplier::where('status', 'active')
            ->select('id', 'code', 'name', 'type', 'address', 'phone')
            ->orderBy('name')
            ->get();

        return $this->success($suppliers);
    }

    /**
     * Get purchase history by supplier
     */
    public function bySupplier(int $supplierId): JsonResponse
    {
        $supplier = Supplier::find($supplierId);
        
        if (!$supplier) {
            return $this->notFound('Supplier tidak ditemukan');
        }

        $purchases = [
            'supplier' => $supplier,
            'cpo' => StockCpoResource::collection(
                StockCpo::where('supplier_id', $supplierId)
                    ->where('stock_type', 'purchase')
                    ->orderBy('stock_date', 'desc')
                    ->get()
            ),
            'kernel' => StockKernelResource::collection(
                StockKernel::where('supplier_id', $supplierId)
                    ->where('stock_type', 'purchase')
                    ->orderBy('stock_date', 'desc')
                    ->get()
            ),
            'shell' => StockShellResource::collection(
                StockShell::where('supplier_id', $supplierId)
                    ->where('stock_type', 'purchase')
                    ->orderBy('stock_date', 'desc')
                    ->get()
            ),
        ];

        return $this->success($purchases);
    }

    /**
     * Generate reference number for purchase
     */
    private function generateReferenceNumber(string $type): string
    {
        $date = now()->format('Ymd');
        $prefix = "PUR-{$type}-{$date}-";
        
        // Get the last number for today
        $lastRef = match ($type) {
            'CPO' => StockCpo::where('reference_number', 'like', $prefix . '%')
                ->orderBy('reference_number', 'desc')
                ->value('reference_number'),
            'KERNEL' => StockKernel::whereNotNull('supplier_id')
                ->whereDate('stock_date', today())
                ->count() + 1,
            'SHELL' => StockShell::whereNotNull('supplier_id')
                ->whereDate('stock_date', today())
                ->count() + 1,
            default => 1,
        };

        if ($type === 'CPO' && $lastRef) {
            $lastNumber = (int) substr($lastRef, -4);
            return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        $count = is_int($lastRef) ? $lastRef : 1;
        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get purchase history/log for bookkeeping (pembukuan)
     */
    public function history(Request $request): JsonResponse
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', now()->toDateString());
        $type = $request->get('type'); // cpo, kernel, shell, or null for all

        $history = [];

        // CPO purchases
        if (!$type || $type === 'cpo') {
            $cpoPurchases = StockCpo::with('supplier')
                ->where('stock_type', 'purchase')
                ->whereNotNull('supplier_id')
                ->whereBetween('stock_date', [$dateFrom, $dateTo])
                ->orderBy('stock_date', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'type' => 'CPO',
                    'reference_number' => $item->reference_number,
                    'stock_date' => $item->stock_date?->toDateString(),
                    'supplier_name' => $item->supplier?->name,
                    'supplier_code' => $item->supplier?->code,
                    'quantity' => $item->quantity,
                    'unit' => 'kg',
                    'purchase_price' => $item->purchase_price,
                    'total_value' => $item->quantity * ($item->purchase_price ?? 0),
                    'quality_grade' => $item->quality_grade,
                    'notes' => $item->notes,
                    'created_at' => $item->created_at?->toISOString(),
                ]);

            $history = array_merge($history, $cpoPurchases->toArray());
        }

        // Kernel purchases
        if (!$type || $type === 'kernel') {
            $kernelPurchases = StockKernel::with('supplier')
                ->where('stock_type', 'purchase')
                ->whereNotNull('supplier_id')
                ->whereBetween('stock_date', [$dateFrom, $dateTo])
                ->orderBy('stock_date', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'type' => 'Kernel',
                    'reference_number' => null,
                    'stock_date' => $item->stock_date?->toDateString(),
                    'supplier_name' => $item->supplier?->name,
                    'supplier_code' => $item->supplier?->code,
                    'quantity' => $item->quantity,
                    'unit' => 'kg',
                    'purchase_price' => $item->purchase_price,
                    'total_value' => $item->quantity * ($item->purchase_price ?? 0),
                    'quality_grade' => $item->quality_grade ?? null,
                    'notes' => null,
                    'created_at' => $item->created_at?->toISOString(),
                ]);

            $history = array_merge($history, $kernelPurchases->toArray());
        }

        // Shell purchases
        if (!$type || $type === 'shell') {
            $shellPurchases = StockShell::with('supplier')
                ->where('stock_type', 'purchase')
                ->whereNotNull('supplier_id')
                ->whereBetween('stock_date', [$dateFrom, $dateTo])
                ->orderBy('stock_date', 'desc')
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'type' => 'Shell',
                    'reference_number' => null,
                    'stock_date' => $item->stock_date?->toDateString(),
                    'supplier_name' => $item->supplier?->name,
                    'supplier_code' => $item->supplier?->code,
                    'quantity' => $item->quantity,
                    'unit' => 'kg',
                    'purchase_price' => $item->purchase_price,
                    'total_value' => $item->quantity * ($item->purchase_price ?? 0),
                    'quality_grade' => null,
                    'notes' => null,
                    'created_at' => $item->created_at?->toISOString(),
                ]);

            $history = array_merge($history, $shellPurchases->toArray());
        }

        // Sort by date descending
        usort($history, fn($a, $b) => strtotime($b['stock_date']) - strtotime($a['stock_date']));

        // Calculate totals
        $totals = [
            'total_transactions' => count($history),
            'total_quantity' => array_sum(array_column($history, 'quantity')),
            'total_value' => array_sum(array_column($history, 'total_value')),
            'by_type' => [
                'cpo' => [
                    'count' => count(array_filter($history, fn($h) => $h['type'] === 'CPO')),
                    'quantity' => array_sum(array_map(fn($h) => $h['type'] === 'CPO' ? $h['quantity'] : 0, $history)),
                    'value' => array_sum(array_map(fn($h) => $h['type'] === 'CPO' ? $h['total_value'] : 0, $history)),
                ],
                'kernel' => [
                    'count' => count(array_filter($history, fn($h) => $h['type'] === 'Kernel')),
                    'quantity' => array_sum(array_map(fn($h) => $h['type'] === 'Kernel' ? $h['quantity'] : 0, $history)),
                    'value' => array_sum(array_map(fn($h) => $h['type'] === 'Kernel' ? $h['total_value'] : 0, $history)),
                ],
                'shell' => [
                    'count' => count(array_filter($history, fn($h) => $h['type'] === 'Shell')),
                    'quantity' => array_sum(array_map(fn($h) => $h['type'] === 'Shell' ? $h['quantity'] : 0, $history)),
                    'value' => array_sum(array_map(fn($h) => $h['type'] === 'Shell' ? $h['total_value'] : 0, $history)),
                ],
            ],
        ];

        return $this->success([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'totals' => $totals,
            'transactions' => $history,
        ]);
    }

    /**
     * Update purchase status for CPO
     */
    public function updateCpoStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_status' => 'required|in:pending,in_process,done',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $stock = StockCpo::where('stock_type', 'purchase')->find($id);
        
        if (!$stock) {
            return $this->notFound('Data pembelian CPO tidak ditemukan');
        }

        $stock->purchase_status = $request->purchase_status;
        $stock->save();

        return $this->success(
            new StockCpoResource($stock->load('supplier')),
            'Status pembelian CPO berhasil diupdate'
        );
    }

    /**
     * Update purchase status for Kernel
     */
    public function updateKernelStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_status' => 'required|in:pending,in_process,done',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $stock = StockKernel::where('stock_type', 'purchase')->find($id);
        
        if (!$stock) {
            return $this->notFound('Data pembelian Kernel tidak ditemukan');
        }

        $stock->purchase_status = $request->purchase_status;
        $stock->save();

        return $this->success(
            new StockKernelResource($stock->load('supplier')),
            'Status pembelian Kernel berhasil diupdate'
        );
    }

    /**
     * Update purchase status for Shell
     */
    public function updateShellStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purchase_status' => 'required|in:pending,in_process,done',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $stock = StockShell::where('stock_type', 'purchase')->find($id);
        
        if (!$stock) {
            return $this->notFound('Data pembelian Shell tidak ditemukan');
        }

        $stock->purchase_status = $request->purchase_status;
        $stock->save();

        return $this->success(
            new StockShellResource($stock->load('supplier')),
            'Status pembelian Shell berhasil diupdate'
        );
    }

    /**
     * Delete CPO purchase (Admin only)
     */
    public function destroyCpo(int $id): JsonResponse
    {
        try {
            $stock = StockCpo::where('stock_type', 'purchase')->find($id);
            
            if (!$stock) {
                return $this->notFound('Data pembelian CPO tidak ditemukan');
            }

            $stock->delete();

            return $this->success(null, 'Data pembelian CPO berhasil dihapus');
        } catch (\Exception $e) {
            return $this->serverError('Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Delete Kernel purchase (Admin only)
     */
    public function destroyKernel(int $id): JsonResponse
    {
        try {
            $stock = StockKernel::where('stock_type', 'purchase')->find($id);
            
            if (!$stock) {
                return $this->notFound('Data pembelian Kernel tidak ditemukan');
            }

            $stock->delete();

            return $this->success(null, 'Data pembelian Kernel berhasil dihapus');
        } catch (\Exception $e) {
            return $this->serverError('Gagal menghapus data: ' . $e->getMessage());
        }
    }

    /**
     * Delete Shell purchase (Admin only)
     */
    public function destroyShell(int $id): JsonResponse
    {
        try {
            $stock = StockShell::where('stock_type', 'purchase')->find($id);
            
            if (!$stock) {
                return $this->notFound('Data pembelian Shell tidak ditemukan');
            }

            $stock->delete();

            return $this->success(null, 'Data pembelian Shell berhasil dihapus');
        } catch (\Exception $e) {
            return $this->serverError('Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
