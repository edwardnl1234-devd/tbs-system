<?php

namespace App\Http\Controllers;

use App\Http\Requests\TbsPrice\StoreTbsPriceRequest;
use App\Http\Requests\TbsPrice\UpdateTbsPriceRequest;
use App\Http\Resources\TbsPriceResource;
use App\Models\TbsPrice;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TbsPriceController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = TbsPrice::query();

        if ($request->has('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('effective_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('effective_date', '<=', $request->date_to);
        }

        $prices = $query->orderBy('effective_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($prices);
    }

    public function store(StoreTbsPriceRequest $request): JsonResponse
    {
        try {
            // Check if price already exists for the date and supplier type
            $exists = TbsPrice::where('effective_date', $request->effective_date)
                ->where('supplier_type', $request->supplier_type)
                ->exists();

            if ($exists) {
                return $this->error('Price already exists for this date and supplier type', 400);
            }

            $price = TbsPrice::create($request->validated());

            return $this->created(
                new TbsPriceResource($price),
                'TBS price created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create price: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $price = TbsPrice::find($id);

        if (!$price) {
            return $this->notFound('TBS price not found');
        }

        return $this->success(new TbsPriceResource($price));
    }

    public function update(UpdateTbsPriceRequest $request, int $id): JsonResponse
    {
        try {
            $price = TbsPrice::find($id);

            if (!$price) {
                return $this->notFound('TBS price not found');
            }

            // Check for duplicate if date or supplier_type is being changed
            if ($request->has('effective_date') || $request->has('supplier_type')) {
                $effectiveDate = $request->effective_date ?? $price->effective_date;
                $supplierType = $request->supplier_type ?? $price->supplier_type;

                $exists = TbsPrice::where('effective_date', $effectiveDate)
                    ->where('supplier_type', $supplierType)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    return $this->error('Price already exists for this date and supplier type', 400);
                }
            }

            $price->update($request->validated());

            return $this->success(
                new TbsPriceResource($price),
                'TBS price updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update price: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $price = TbsPrice::find($id);

            if (!$price) {
                return $this->notFound('TBS price not found');
            }

            $price->delete();

            return $this->success(null, 'TBS price deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete price: ' . $e->getMessage());
        }
    }

    public function today(): JsonResponse
    {
        $prices = TbsPrice::whereDate('effective_date', today())
            ->get();

        return $this->success(TbsPriceResource::collection($prices));
    }

    public function latest(): JsonResponse
    {
        // Get latest price for each supplier type
        $latestPrices = TbsPrice::selectRaw('supplier_type, MAX(effective_date) as max_date')
            ->groupBy('supplier_type')
            ->get()
            ->mapWithKeys(function ($item) {
                $price = TbsPrice::where('supplier_type', $item->supplier_type)
                    ->where('effective_date', $item->max_date)
                    ->first();
                return [$item->supplier_type => $price];
            });

        return $this->success([
            'inti' => $latestPrices['inti'] ?? null,
            'plasma' => $latestPrices['plasma'] ?? null,
            'umum' => $latestPrices['umum'] ?? null,
        ]);
    }

    public function byDate(string $date): JsonResponse
    {
        $prices = TbsPrice::whereDate('effective_date', $date)->get();

        if ($prices->isEmpty()) {
            // If no price for specific date, get the latest before this date
            $prices = TbsPrice::select('tbs_prices.*')
                ->join(\DB::raw('(SELECT supplier_type, MAX(effective_date) as max_date FROM tbs_prices WHERE effective_date <= ? GROUP BY supplier_type) as latest'), function ($join) {
                    $join->on('tbs_prices.supplier_type', '=', 'latest.supplier_type')
                        ->on('tbs_prices.effective_date', '=', 'latest.max_date');
                })
                ->setBindings([$date])
                ->get();
        }

        return $this->success(TbsPriceResource::collection($prices));
    }

    public function history(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $prices = TbsPrice::where('effective_date', '>=', today()->subDays($days))
            ->orderBy('effective_date', 'desc')
            ->orderBy('supplier_type')
            ->get()
            ->groupBy('effective_date')
            ->map(function ($dayPrices) {
                return $dayPrices->keyBy('supplier_type');
            });

        return $this->success($prices);
    }
}
