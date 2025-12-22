<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'code' => 'SUP-INTI-001',
                'name' => 'PT Perkebunan Nusantara XIV',
                'type' => 'inti',
                'contact_person' => 'Ahmad Sutrisno',
                'phone' => '08123456789',
                'address' => 'Jl. Perkebunan No. 1, Kec. Sawit, Kab. Kelapa',
                'status' => 'active',
            ],
            [
                'code' => 'SUP-INTI-002',
                'name' => 'PT Sawit Makmur Jaya',
                'type' => 'inti',
                'contact_person' => 'Budi Santoso',
                'phone' => '08234567890',
                'address' => 'Jl. Industri Sawit No. 45, Kec. Minyak, Kab. Kelapa',
                'status' => 'active',
            ],
            [
                'code' => 'SUP-PLSM-001',
                'name' => 'Koperasi Petani Plasma Sejahtera',
                'type' => 'plasma',
                'contact_person' => 'Haji Mahmud',
                'phone' => '08345678901',
                'address' => 'Desa Plasma Jaya, Kec. Sawit, Kab. Kelapa',
                'status' => 'active',
            ],
            [
                'code' => 'SUP-PLSM-002',
                'name' => 'KUD Plasma Mandiri',
                'type' => 'plasma',
                'contact_person' => 'Siti Aminah',
                'phone' => '08456789012',
                'address' => 'Desa Plasma Mandiri, Kec. Kebun, Kab. Kelapa',
                'status' => 'active',
            ],
            [
                'code' => 'SUP-UMUM-001',
                'name' => 'CV Sawit Rakyat',
                'type' => 'umum',
                'contact_person' => 'Dedi Kurniawan',
                'phone' => '08567890123',
                'address' => 'Jl. Raya Sawit No. 88, Kec. Umum, Kab. Kelapa',
                'status' => 'active',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
