<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Industries
        $industries = [
            ['name' => 'Teknologi Informasi', 'icon' => 'monitor'],
            ['name' => 'Kreatif & Desain', 'icon' => 'palette'],
            ['name' => 'Pemasaran & Komunikasi', 'icon' => 'megaphone'],
            ['name' => 'Keuangan & Perbankan', 'icon' => 'landmark'],
            ['name' => 'Pendidikan', 'icon' => 'graduation-cap'],
            ['name' => 'Kesehatan', 'icon' => 'heart-pulse'],
            ['name' => 'Manufaktur', 'icon' => 'factory'],
            ['name' => 'Logistik & Transportasi', 'icon' => 'truck'],
        ];

        foreach ($industries as $industry) {
            DB::table('industries')->updateOrInsert(
                ['slug' => Str::slug($industry['name'])],
                [
                    'name' => $industry['name'],
                    'icon' => $industry['icon'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 2. Locations (Provinces Indonesia)
        $provinces = [
            'DKI Jakarta',
            'Jawa Barat',
            'Jawa Tengah',
            'Jawa Timur',
            'Banten',
            'DI Yogyakarta',
            'Bali',
            'Sumatera Utara',
            'Sumatera Selatan',
            'Sulawesi Selatan',
            'Kalimantan Timur',
        ];

        foreach ($provinces as $province) {
            DB::table('locations')->updateOrInsert(
                ['name' => $province],
                [
                    'type' => 'province',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Common Cities
        $cities = [
            'Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Utara', 'Jakarta Timur',
            'Bandung', 'Bekasi', 'Depok', 'Tangerang', 'Bogor',
            'Semarang', 'Surakarta', 'Surabaya', 'Malang',
            'Yogyakarta', 'Denpasar', 'Medan', 'Palembang', 'Makassar', 'Balikpapan',
        ];

        foreach ($cities as $city) {
            DB::table('locations')->updateOrInsert(
                ['name' => $city],
                [
                    'type' => 'city',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
