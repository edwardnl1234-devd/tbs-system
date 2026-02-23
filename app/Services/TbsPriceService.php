<?php

namespace App\Services;

use App\Models\TbsPrice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TbsPriceService
{
    /**
     * Sumber harga yang didukung
     */
    const SOURCE_MANUAL = 'manual';
    const SOURCE_DISBUN = 'disbun';      // Dinas Perkebunan
    const SOURCE_PTPN = 'ptpn';          // PTPN
    const SOURCE_GAPKI = 'gapki';        // GAPKI
    const SOURCE_CUSTOM_API = 'custom';  // Custom API

    /**
     * Fetch harga dari sumber online
     *
     * @param string $source
     * @param string|null $province Kode provinsi (untuk disbun)
     * @return array|null
     */
    public function fetchOnlinePrice(string $source = self::SOURCE_DISBUN, ?string $province = null): ?array
    {
        try {
            return match ($source) {
                self::SOURCE_DISBUN => $this->fetchFromDisbun($province),
                self::SOURCE_PTPN => $this->fetchFromPtpn(),
                self::SOURCE_GAPKI => $this->fetchFromGapki(),
                self::SOURCE_CUSTOM_API => $this->fetchFromCustomApi(),
                default => null,
            };
        } catch (\Exception $e) {
            Log::error('Failed to fetch TBS price online', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Fetch harga dari Dinas Perkebunan
     * Contoh: Dinas Perkebunan Riau, Sumut, Kaltim, dll
     */
    private function fetchFromDisbun(?string $province = null): ?array
    {
        $province = $province ?? config('tbs.price_source.province', 'riau');
        
        // URL endpoint Dinas Perkebunan (sesuaikan dengan yang sebenarnya)
        $endpoints = [
            'riau' => 'https://disbun.riau.go.id/api/harga-tbs',
            'sumut' => 'https://disbun.sumutprov.go.id/api/harga-tbs',
            'kaltim' => 'https://disbun.kaltimprov.go.id/api/harga-tbs',
            'kalbar' => 'https://disbun.kalbarprov.go.id/api/harga-tbs',
            'jambi' => 'https://disbun.jambiprov.go.id/api/harga-tbs',
        ];

        $url = $endpoints[$province] ?? null;
        
        if (!$url) {
            // Fallback: gunakan custom API URL dari config
            $url = config('tbs.price_source.disbun_url');
        }

        if (!$url) {
            Log::warning('No Disbun URL configured for province: ' . $province);
            return null;
        }

        $response = Http::timeout(30)->get($url);

        if ($response->successful()) {
            $data = $response->json();
            return $this->parseDisbunResponse($data);
        }

        return null;
    }

    /**
     * Parse response dari Dinas Perkebunan
     */
    private function parseDisbunResponse(array $data): array
    {
        // Format response bervariasi per provinsi, sesuaikan dengan format sebenarnya
        // Contoh format umum:
        return [
            'effective_date' => $data['tanggal'] ?? $data['date'] ?? today()->format('Y-m-d'),
            'prices' => [
                'inti' => $data['harga_inti'] ?? $data['inti'] ?? $data['price_inti'] ?? null,
                'plasma' => $data['harga_plasma'] ?? $data['plasma'] ?? $data['price_plasma'] ?? null,
                'umum' => $data['harga_umum'] ?? $data['umum'] ?? $data['price_umum'] ?? $data['harga'] ?? null,
            ],
            'source' => 'disbun',
            'raw' => $data,
        ];
    }

    /**
     * Fetch harga dari PTPN
     */
    private function fetchFromPtpn(): ?array
    {
        $url = config('tbs.price_source.ptpn_url');
        
        if (!$url) {
            Log::warning('No PTPN URL configured');
            return null;
        }

        $response = Http::timeout(30)->get($url);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'effective_date' => $data['tanggal'] ?? today()->format('Y-m-d'),
                'prices' => [
                    'inti' => $data['harga_inti'] ?? null,
                    'plasma' => $data['harga_plasma'] ?? null,
                    'umum' => $data['harga_pihak_ketiga'] ?? $data['harga_umum'] ?? null,
                ],
                'source' => 'ptpn',
                'raw' => $data,
            ];
        }

        return null;
    }

    /**
     * Fetch harga dari GAPKI
     */
    private function fetchFromGapki(): ?array
    {
        $url = config('tbs.price_source.gapki_url', 'https://gapki.id/api/harga-tbs');

        $response = Http::timeout(30)->get($url);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'effective_date' => $data['date'] ?? today()->format('Y-m-d'),
                'prices' => [
                    'inti' => $data['inti'] ?? null,
                    'plasma' => $data['plasma'] ?? null,
                    'umum' => $data['umum'] ?? $data['price'] ?? null,
                ],
                'source' => 'gapki',
                'raw' => $data,
            ];
        }

        return null;
    }

    /**
     * Fetch harga dari Custom API
     * Untuk integrasi dengan sistem internal perusahaan
     */
    private function fetchFromCustomApi(): ?array
    {
        $url = config('tbs.price_source.custom_api_url');
        $apiKey = config('tbs.price_source.custom_api_key');

        if (!$url) {
            Log::warning('No Custom API URL configured');
            return null;
        }

        $headers = [];
        if ($apiKey) {
            $headers['Authorization'] = 'Bearer ' . $apiKey;
            $headers['X-API-Key'] = $apiKey;
        }

        $response = Http::timeout(30)
            ->withHeaders($headers)
            ->get($url);

        if ($response->successful()) {
            $data = $response->json();
            
            // Map field names from config
            $fieldMap = config('tbs.price_source.custom_field_map', [
                'date' => 'effective_date',
                'inti' => 'price_inti',
                'plasma' => 'price_plasma',
                'umum' => 'price_umum',
            ]);

            return [
                'effective_date' => $data[$fieldMap['date']] ?? today()->format('Y-m-d'),
                'prices' => [
                    'inti' => $data[$fieldMap['inti']] ?? null,
                    'plasma' => $data[$fieldMap['plasma']] ?? null,
                    'umum' => $data[$fieldMap['umum']] ?? null,
                ],
                'source' => 'custom_api',
                'raw' => $data,
            ];
        }

        return null;
    }

    /**
     * Update harga di database dari sumber online
     *
     * @param string $source
     * @param string|null $province
     * @return array Result with status and message
     */
    public function updatePricesFromOnline(string $source = self::SOURCE_DISBUN, ?string $province = null): array
    {
        $priceData = $this->fetchOnlinePrice($source, $province);

        if (!$priceData) {
            return [
                'success' => false,
                'message' => 'Gagal mengambil harga dari sumber online',
                'source' => $source,
            ];
        }

        $effectiveDate = $priceData['effective_date'];
        $created = [];
        $updated = [];
        $skipped = [];

        foreach ($priceData['prices'] as $type => $price) {
            if (!$price) {
                $skipped[] = $type;
                continue;
            }

            $existing = TbsPrice::where('effective_date', $effectiveDate)
                ->where('supplier_type', $type)
                ->first();

            if ($existing) {
                if ($existing->price_per_kg != $price) {
                    $existing->update([
                        'price_per_kg' => $price,
                        'notes' => 'Updated from ' . $source . ' on ' . now()->format('Y-m-d H:i:s'),
                    ]);
                    $updated[] = $type;
                } else {
                    $skipped[] = $type;
                }
            } else {
                TbsPrice::create([
                    'effective_date' => $effectiveDate,
                    'supplier_type' => $type,
                    'price_per_kg' => $price,
                    'notes' => 'Fetched from ' . $source . ' on ' . now()->format('Y-m-d H:i:s'),
                ]);
                $created[] = $type;
            }
        }

        // Clear cache
        Cache::forget('tbs_prices_today');

        return [
            'success' => true,
            'message' => 'Harga berhasil diperbarui dari ' . $source,
            'source' => $source,
            'effective_date' => $effectiveDate,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'prices' => $priceData['prices'],
        ];
    }

    /**
     * Get cached today prices or fetch from database
     */
    public function getTodayPricesCached(): array
    {
        return Cache::remember('tbs_prices_today', 3600, function () {
            return TbsPrice::getTodayPrices();
        });
    }

    /**
     * Simulate fetching price for testing
     * Harga berdasarkan harga CPO internasional dan formula konversi
     */
    public function simulatePrice(): array
    {
        // Simulasi harga berdasarkan harga CPO (bisa diganti dengan fetch harga CPO sebenarnya)
        // Rumus umum: Harga TBS = (Harga CPO × Rendemen × K) - Biaya
        // Rendemen rata-rata: 21-23%
        // K (koefisien): 0.85-0.90

        $baseCpoPrice = 14000; // Rp per kg (contoh)
        $rendemen = 0.22; // 22%
        $koefisien = 0.87;
        $biayaProses = 200; // Rp per kg

        $basePrice = ($baseCpoPrice * $rendemen * $koefisien) - $biayaProses;
        $basePrice = round($basePrice, -1); // Bulatkan ke puluhan

        return [
            'effective_date' => today()->format('Y-m-d'),
            'prices' => [
                'inti' => $basePrice + 100,   // Inti dapat premium
                'plasma' => $basePrice + 50,  // Plasma sedikit premium
                'umum' => $basePrice,          // Umum harga dasar
            ],
            'source' => 'simulation',
            'calculation' => [
                'cpo_price' => $baseCpoPrice,
                'rendemen' => $rendemen,
                'koefisien' => $koefisien,
                'biaya_proses' => $biayaProses,
            ],
        ];
    }
}
