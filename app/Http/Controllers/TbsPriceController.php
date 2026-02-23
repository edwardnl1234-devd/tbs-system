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

        if ($request->filled('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('effective_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
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

    /**
     * Fetch harga dari sumber online
     */
    public function fetchOnline(Request $request): JsonResponse
    {
        try {
            $source = $request->get('source', config('tbs.price_source.default', 'disbun'));
            $province = $request->get('province', config('tbs.price_source.province', 'riau'));
            $save = $request->boolean('save', false);

            $priceService = app(\App\Services\TbsPriceService::class);

            // Jika source = simulate, gunakan harga simulasi
            if ($source === 'simulate') {
                $priceData = $priceService->simulatePrice();
            } else {
                $priceData = $priceService->fetchOnlinePrice($source, $province);
            }

            if (!$priceData) {
                return $this->error('Gagal mengambil harga dari sumber online. Pastikan URL API sudah dikonfigurasi.', 503);
            }

            // Simpan ke database jika diminta
            if ($save) {
                foreach ($priceData['prices'] as $type => $price) {
                    if ($price) {
                        TbsPrice::updateOrCreate(
                            [
                                'effective_date' => $priceData['effective_date'],
                                'supplier_type' => $type,
                            ],
                            [
                                'price_per_kg' => $price,
                                'notes' => 'Fetched from ' . $source . ' on ' . now()->format('Y-m-d H:i:s'),
                            ]
                        );
                    }
                }
                $priceData['saved'] = true;
            }

            return $this->success($priceData, 'Harga berhasil diambil dari ' . $source);
        } catch (\Exception $e) {
            return $this->serverError('Gagal mengambil harga: ' . $e->getMessage());
        }
    }

    /**
     * Get available price sources
     */
    public function sources(): JsonResponse
    {
        $sources = [
            [
                'id' => 'manual',
                'name' => 'Input Manual',
                'description' => 'Input harga secara manual',
                'available' => true,
            ],
            [
                'id' => 'disbun',
                'name' => 'Dinas Perkebunan',
                'description' => 'Harga dari Dinas Perkebunan Provinsi',
                'available' => !empty(config('tbs.price_source.disbun_url')),
                'provinces' => ['riau', 'sumut', 'kaltim', 'kalbar', 'jambi'],
            ],
            [
                'id' => 'ptpn',
                'name' => 'PTPN',
                'description' => 'Harga dari PTPN',
                'available' => !empty(config('tbs.price_source.ptpn_url')),
            ],
            [
                'id' => 'gapki',
                'name' => 'GAPKI',
                'description' => 'Harga dari Gabungan Pengusaha Kelapa Sawit Indonesia',
                'available' => !empty(config('tbs.price_source.gapki_url')),
            ],
            [
                'id' => 'custom',
                'name' => 'Custom API',
                'description' => 'Harga dari API internal perusahaan',
                'available' => !empty(config('tbs.price_source.custom_api_url')),
            ],
            [
                'id' => 'simulate',
                'name' => 'Simulasi',
                'description' => 'Harga simulasi berdasarkan kalkulasi CPO',
                'available' => true,
            ],
        ];

        return $this->success([
            'sources' => $sources,
            'default' => config('tbs.price_source.default', 'manual'),
            'province' => config('tbs.price_source.province', 'riau'),
        ]);
    }
}
