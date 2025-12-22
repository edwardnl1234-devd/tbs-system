<?php

namespace Database\Seeders;

use App\Models\Truck;
use Illuminate\Database\Seeder;

class TruckSeeder extends Seeder
{
    public function run(): void
    {
        $trucks = [
            [
                'plate_number' => 'B 1234 ABC',
                'driver_name' => 'Supardi',
                'driver_phone' => '08111222333',
                'capacity' => 8000,
                'type' => 'Truk Fuso',
                'status' => 'active',
            ],
            [
                'plate_number' => 'B 5678 DEF',
                'driver_name' => 'Bambang',
                'driver_phone' => '08222333444',
                'capacity' => 10000,
                'type' => 'Truk Tronton',
                'status' => 'active',
            ],
            [
                'plate_number' => 'B 9012 GHI',
                'driver_name' => 'Joko',
                'driver_phone' => '08333444555',
                'capacity' => 6000,
                'type' => 'Truk Colt Diesel',
                'status' => 'active',
            ],
            [
                'plate_number' => 'D 3456 JKL',
                'driver_name' => 'Udin',
                'driver_phone' => '08444555666',
                'capacity' => 8000,
                'type' => 'Truk Fuso',
                'status' => 'active',
            ],
            [
                'plate_number' => 'D 7890 MNO',
                'driver_name' => 'Rahman',
                'driver_phone' => '08555666777',
                'capacity' => 12000,
                'type' => 'Truk Trailer',
                'status' => 'active',
            ],
            [
                'plate_number' => 'F 1234 PQR',
                'driver_name' => 'Saiful',
                'driver_phone' => '08666777888',
                'capacity' => 7000,
                'type' => 'Truk Engkel',
                'status' => 'active',
            ],
            [
                'plate_number' => 'F 5678 STU',
                'driver_name' => 'Agus',
                'driver_phone' => '08777888999',
                'capacity' => 9000,
                'type' => 'Truk Fuso',
                'status' => 'active',
            ],
            [
                'plate_number' => 'E 9012 VWX',
                'driver_name' => 'Darmawan',
                'driver_phone' => '08888999000',
                'capacity' => 10000,
                'type' => 'Truk Tronton',
                'status' => 'active',
            ],
            [
                'plate_number' => 'E 3456 YZA',
                'driver_name' => 'Hasan',
                'driver_phone' => '08999000111',
                'capacity' => 8500,
                'type' => 'Truk Fuso',
                'status' => 'active',
            ],
            [
                'plate_number' => 'A 7890 BCD',
                'driver_name' => 'Karno',
                'driver_phone' => '08000111222',
                'capacity' => 15000,
                'type' => 'Truk Trailer',
                'status' => 'active',
            ],
        ];

        foreach ($trucks as $truck) {
            Truck::create($truck);
        }
    }
}
