<?php

namespace App\Console\Commands;

use App\Services\TbsPriceService;
use Illuminate\Console\Command;

class FetchTbsPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tbs:fetch-price 
                            {--source=disbun : Sumber harga (disbun, ptpn, gapki, custom, simulate)}
                            {--province= : Kode provinsi untuk disbun (riau, sumut, kaltim, dll)}
                            {--save : Simpan ke database}
                            {--force : Paksa update meskipun sudah ada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch harga TBS dari sumber online';

    /**
     * Execute the console command.
     */
    public function handle(TbsPriceService $priceService)
    {
        $source = $this->option('source') ?? config('tbs.price_source.default', 'disbun');
        $province = $this->option('province') ?? config('tbs.price_source.province', 'riau');
        $save = $this->option('save');

        $this->info("Mengambil harga TBS dari: {$source}");
        
        if ($source === 'simulate') {
            $this->info("Mode simulasi - menggunakan harga kalkulasi");
            $priceData = $priceService->simulatePrice();
        } else {
            $priceData = $priceService->fetchOnlinePrice($source, $province);
        }

        if (!$priceData) {
            $this->error('Gagal mengambil harga dari sumber online');
            $this->line('');
            $this->warn('Tips:');
            $this->line('1. Pastikan URL API sudah dikonfigurasi di .env');
            $this->line('2. Gunakan --source=simulate untuk testing');
            $this->line('3. Periksa koneksi internet');
            return 1;
        }

        $this->info("Sumber: {$priceData['source']}");
        $this->info("Tanggal Efektif: {$priceData['effective_date']}");
        $this->line('');

        $this->table(
            ['Tipe Supplier', 'Harga per Kg'],
            collect($priceData['prices'])->map(function ($price, $type) {
                return [
                    strtoupper($type),
                    $price ? 'Rp ' . number_format($price, 0, ',', '.') : '-',
                ];
            })->toArray()
        );

        if ($save) {
            $this->line('');
            $this->info('Menyimpan ke database...');
            
            $result = $priceService->updatePricesFromOnline(
                $source === 'simulate' ? 'manual' : $source, 
                $province
            );

            if ($source === 'simulate') {
                // For simulation, manually save
                foreach ($priceData['prices'] as $type => $price) {
                    if ($price) {
                        \App\Models\TbsPrice::updateOrCreate(
                            [
                                'effective_date' => $priceData['effective_date'],
                                'supplier_type' => $type,
                            ],
                            [
                                'price_per_kg' => $price,
                                'notes' => 'Simulated price on ' . now()->format('Y-m-d H:i:s'),
                            ]
                        );
                    }
                }
                $this->info('✓ Harga simulasi berhasil disimpan');
            } elseif ($result['success']) {
                $this->info('✓ ' . $result['message']);
                
                if (!empty($result['created'])) {
                    $this->line('  Dibuat: ' . implode(', ', $result['created']));
                }
                if (!empty($result['updated'])) {
                    $this->line('  Diupdate: ' . implode(', ', $result['updated']));
                }
                if (!empty($result['skipped'])) {
                    $this->line('  Dilewati: ' . implode(', ', $result['skipped']));
                }
            } else {
                $this->error('✗ ' . $result['message']);
            }
        } else {
            $this->line('');
            $this->comment('Gunakan --save untuk menyimpan ke database');
        }

        return 0;
    }
}
