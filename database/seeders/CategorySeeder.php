<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Transportation'],
            ['name' => 'Accommodation'],
            ['name' => 'Excursion'],
            ['name' => 'Guide'],
            ['name' => 'Other'],
        ];
        foreach ($categories as $category) {
            Category::firstOrCreate($category);
        }
    }
}
