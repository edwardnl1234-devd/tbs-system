<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'code' => 'CUS-001',
                'name' => 'PT Oleochemical Indonesia',
                'contact_person' => 'Michael Tan',
                'phone' => '02198765432',
                'email' => 'procurement@oleochem.co.id',
                'address' => 'Jl. Industri Kimia No. 100, Cikarang, Bekasi',
                'product_types' => ['CPO', 'Kernel'],
                'status' => 'active',
            ],
            [
                'code' => 'CUS-002',
                'name' => 'PT Refinery Nusantara',
                'contact_person' => 'Andi Wijaya',
                'phone' => '02187654321',
                'email' => 'buyer@refinery-nusantara.com',
                'address' => 'Kawasan Industri Pulogadung, Jakarta Timur',
                'product_types' => ['CPO'],
                'status' => 'active',
            ],
            [
                'code' => 'CUS-003',
                'name' => 'CV Pakan Ternak Makmur',
                'contact_person' => 'Hendra Kusuma',
                'phone' => '08112233445',
                'email' => 'order@pakanmakmur.id',
                'address' => 'Jl. Peternakan No. 55, Bogor, Jawa Barat',
                'product_types' => ['Kernel', 'Shell'],
                'status' => 'active',
            ],
            [
                'code' => 'CUS-004',
                'name' => 'PT Biomass Energy',
                'contact_person' => 'Steven Lim',
                'phone' => '02176543210',
                'email' => 'supply@biomass-energy.co.id',
                'address' => 'Jl. Energi Hijau No. 88, Tangerang, Banten',
                'product_types' => ['Shell'],
                'status' => 'active',
            ],
            [
                'code' => 'CUS-005',
                'name' => 'PT Export Trading Company',
                'contact_person' => 'David Wong',
                'phone' => '02165432109',
                'email' => 'trading@export-co.com',
                'address' => 'Jl. Pelabuhan No. 1, Tanjung Priok, Jakarta Utara',
                'product_types' => ['CPO', 'Kernel', 'Shell'],
                'status' => 'active',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
