<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\Category;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Airport Transfer', 'category' => 'Transportation', 'price' => 10000],
            ['name' => 'Hotel Booking', 'category' => 'Accommodation', 'price' => 25000],
            ['name' => 'City Tour', 'category' => 'Excursion', 'price' => 15000],
            ['name' => 'English Guide', 'category' => 'Guide', 'price' => 5000],
        ];
        foreach ($services as $service) {
            $category = Category::where('name', $service['category'])->first();
            if ($category) {
                Service::firstOrCreate([
                    'name' => $service['name'],
                    'category_id' => $category->id
                ], [
                    'price' => $service['price']
                ]);
            }
        }
    }
}
