<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sales\StoreSalesRequest;
use App\Http\Requests\Sales\UpdateSalesRequest;
use App\Http\Resources\SalesResource;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Sales::with(['customer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $sales = $query->orderBy('order_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($sales);
    }

    public function store(StoreSalesRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate SO number: SO-YYYYMMDD-NNN
            $today = now()->format('Ymd');
            $lastSale = Sales::whereDate('order_date', today())
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastSale ? (int) substr($lastSale->so_number, -3) + 1 : 1;
            $soNumber = 'SO-' . $today . '-' . str_pad($sequence, 3, '0', STR_PAD_LEFT);

            $totalAmount = $request->quantity * $request->price_per_kg;

            $sale = Sales::create([
                'customer_id' => $request->customer_id,
                'so_number' => $soNumber,
                'product_type' => $request->product_type,
                'quantity' => $request->quantity,
                'price_per_kg' => $request->price_per_kg,
                'total_amount' => $totalAmount,
                'order_date' => $request->order_date,
                'delivery_date' => $request->delivery_date,
                'truck_plate' => $request->truck_plate,
                'driver_name' => $request->driver_name,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // Reserve stock based on product type
            $this->reserveStock($sale);

            DB::commit();

            return $this->created(
                new SalesResource($sale->load(['customer', 'details'])),
                'Sales order created successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create sales: ' . $e->getMessage());
        }
    }

    private function reserveStock(Sales $sale): void
    {
        $remainingQuantity = $sale->quantity;

        switch ($sale->product_type) {
            case 'CPO':
                $availableStocks = StockCpo::where('status', 'available')
                    ->where('movement_type', 'in')
                    ->orderBy('stock_date', 'asc')
                    ->get();

                foreach ($availableStocks as $stock) {
                    if ($remainingQuantity <= 0) break;

                    $allocatedQty = min($stock->quantity, $remainingQuantity);

                    SalesDetail::create([
                        'sales_id' => $sale->id,
                        'stock_cpo_id' => $stock->id,
                        'quantity_sold' => $allocatedQty,
                    ]);

                    $stock->update(['status' => 'reserved']);
                    $remainingQuantity -= $allocatedQty;
                }
                break;

            case 'Kernel':
                $availableStocks = StockKernel::where('status', 'available')
                    ->orderBy('stock_date', 'asc')
                    ->get();

                foreach ($availableStocks as $stock) {
                    if ($remainingQuantity <= 0) break;

                    $allocatedQty = min($stock->quantity, $remainingQuantity);

                    SalesDetail::create([
                        'sales_id' => $sale->id,
                        'stock_kernel_id' => $stock->id,
                        'quantity_sold' => $allocatedQty,
                    ]);

                    $remainingQuantity -= $allocatedQty;
                }
                break;

            case 'Shell':
                $availableStocks = StockShell::where('status', 'available')
                    ->orderBy('stock_date', 'asc')
                    ->get();

                foreach ($availableStocks as $stock) {
                    if ($remainingQuantity <= 0) break;

                    $allocatedQty = min($stock->quantity, $remainingQuantity);

                    SalesDetail::create([
                        'sales_id' => $sale->id,
                        'stock_shell_id' => $stock->id,
                        'quantity_sold' => $allocatedQty,
                    ]);

                    $remainingQuantity -= $allocatedQty;
                }
                break;
        }
    }

    public function show(int $id): JsonResponse
    {
        $sale = Sales::with(['customer', 'details.stockCpo', 'details.stockKernel', 'details.stockShell'])->find($id);

        if (!$sale) {
            return $this->notFound('Sales order not found');
        }

        return $this->success(new SalesResource($sale));
    }

    public function update(UpdateSalesRequest $request, int $id): JsonResponse
    {
        try {
            $sale = Sales::find($id);

            if (!$sale) {
                return $this->notFound('Sales order not found');
            }

            if (in_array($sale->status, ['delivered', 'completed'])) {
                return $this->error('Cannot update delivered or completed sales', 400);
            }

            $data = $request->validated();

            // Recalculate total if quantity or price changed
            if (isset($data['quantity']) || isset($data['price_per_kg'])) {
                $quantity = $data['quantity'] ?? $sale->quantity;
                $pricePerKg = $data['price_per_kg'] ?? $sale->price_per_kg;
                $data['total_amount'] = $quantity * $pricePerKg;
            }

            $sale->update($data);

            return $this->success(
                new SalesResource($sale->load(['customer', 'details'])),
                'Sales order updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update sales: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $sale = Sales::find($id);

            if (!$sale) {
                return $this->notFound('Sales order not found');
            }

            if (in_array($sale->status, ['delivered', 'completed'])) {
                return $this->error('Cannot delete delivered or completed sales', 400);
            }

            // Release reserved stock
            $this->releaseReservedStock($sale);

            $sale->delete();

            DB::commit();

            return $this->success(null, 'Sales order deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to delete sales: ' . $e->getMessage());
        }
    }

    private function releaseReservedStock(Sales $sale): void
    {
        foreach ($sale->details as $detail) {
            if ($detail->stock_cpo_id) {
                StockCpo::where('id', $detail->stock_cpo_id)
                    ->update(['status' => 'available']);
            }
        }
    }

    public function deliver(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $sale = Sales::find($id);

            if (!$sale) {
                return $this->notFound('Sales order not found');
            }

            if ($sale->status !== 'pending') {
                return $this->error('Only pending orders can be marked as delivered', 400);
            }

            // Create stock OUT records for CPO
            if ($sale->product_type === 'CPO') {
                StockCpo::create([
                    'quantity' => $sale->quantity,
                    'movement_type' => 'out',
                    'reference_number' => $sale->so_number,
                    'stock_date' => today(),
                    'status' => 'sold',
                    'notes' => 'Sales delivery: ' . $sale->so_number,
                ]);
            }

            // Update reserved stocks to sold
            foreach ($sale->details as $detail) {
                if ($detail->stock_cpo_id) {
                    StockCpo::where('id', $detail->stock_cpo_id)->update(['status' => 'sold']);
                }
                if ($detail->stock_kernel_id) {
                    StockKernel::where('id', $detail->stock_kernel_id)->update(['status' => 'sold']);
                }
                if ($detail->stock_shell_id) {
                    StockShell::where('id', $detail->stock_shell_id)->update(['status' => 'sold']);
                }
            }

            $sale->update([
                'status' => 'delivered',
                'delivery_date' => today(),
            ]);

            DB::commit();

            return $this->success(
                new SalesResource($sale->load(['customer', 'details'])),
                'Sales order marked as delivered'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to mark as delivered: ' . $e->getMessage());
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            $sale = Sales::find($id);

            if (!$sale) {
                return $this->notFound('Sales order not found');
            }

            if ($sale->status !== 'delivered') {
                return $this->error('Only delivered orders can be marked as completed', 400);
            }

            $sale->update(['status' => 'completed']);

            return $this->success(
                new SalesResource($sale->load(['customer', 'details'])),
                'Sales order completed successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to complete sales: ' . $e->getMessage());
        }
    }

    public function today(): JsonResponse
    {
        $sales = Sales::with(['customer'])
            ->whereDate('order_date', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(SalesResource::collection($sales));
    }

    public function pending(): JsonResponse
    {
        $sales = Sales::with(['customer'])
            ->where('status', 'pending')
            ->orderBy('order_date', 'asc')
            ->get();

        return $this->success(SalesResource::collection($sales));
    }

    public function byCustomer(int $customerId): JsonResponse
    {
        $sales = Sales::with(['customer'])
            ->where('customer_id', $customerId)
            ->orderBy('order_date', 'desc')
            ->paginate(15);

        return $this->successPaginated($sales);
    }

    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $stats = Sales::whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(quantity) as total_quantity,
                SUM(total_amount) as total_revenue,
                AVG(price_per_kg) as avg_price
            ')
            ->first();

        $byProduct = Sales::whereBetween('order_date', [$startDate, $endDate])
            ->select('product_type', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_amount) as total_revenue'))
            ->groupBy('product_type')
            ->get();

        $byStatus = Sales::whereBetween('order_date', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => $stats,
            'by_product' => $byProduct,
            'by_status' => $byStatus,
        ]);
    }
}
