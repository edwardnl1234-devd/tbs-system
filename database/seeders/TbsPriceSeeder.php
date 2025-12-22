<?php

namespace Database\Seeders;

use App\Models\TbsPrice;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TbsPriceSeeder extends Seeder
{
    public function run(): void
    {
        $startDate = Carbon::now()->subDays(30);
        $basePrice = [
            'inti' => 2500,   // Rp per kg
            'plasma' => 2400,
            'umum' => 2300,
        ];

        for ($i = 0; $i <= 30; $i++) {
            $date = $startDate->copy()->addDays($i)->toDateString();
            
            // Add some price variation (±50)
            $variation = rand(-50, 50);
            
            foreach (['inti', 'plasma', 'umum'] as $type) {
                $price = $basePrice[$type] + $variation;
                
                // Ensure price doesn't go below minimum
                $price = max($price, 2000);
                
                TbsPrice::create([
                    'effective_date' => $date,
                    'supplier_type' => $type,
                    'price_per_kg' => $price,
                    'notes' => $i === 30 ? 'Harga hari ini' : null,
                ]);
            }
        }
    }
}
