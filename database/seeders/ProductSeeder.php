<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'main_image' => '/images/orange_2.png',
            'secondary_images' => [
                '/images/orange_1.png',
                '/images/blue_1.png',
                '/images/blue_2.png',
                '/images/white_1.png',
            ],
            'name' => 'Apple iPhone 17 Pro Max 512 GB',
            'description' => 'Experience the future of mobile technology with the iPhone 17 Pro Max. Featuring the revolutionary A18 Pro chip, advanced camera system with 5x optical zoom, and titanium design crafted for ultimate durability and premium feel.',
            'features' => [
                'A18 Pro chip with 6-core CPU',
                '6.9-inch Super Retina XDR display',
                'Pro camera system with 5x optical zoom',
                'Titanium design',
                'Up to 40 hours battery life',
                'USB-C charging',
                'Face ID',
                '5G connectivity'
            ],
            'colors' => ['Orange', 'Gray', 'White'],
            'quantity' => 50,
            'price_before' => 1119.00,
            'price_after' => 559.00,
            'is_active' => true,
        ]);

        // يمكن إضافة منتجات أخرى هنا
        Product::create([
            'main_image' => '/images/blue_1.png',
            'secondary_images' => [
                '/images/blue_2.png',
                '/images/orange_1.png',
            ],
            'name' => 'Apple iPhone 17 Pro 256 GB',
            'description' => 'The perfect balance of performance and size. iPhone 17 Pro features the powerful A18 Pro chip and advanced camera system in a compact design.',
            'features' => [
                'A18 Pro chip with 6-core CPU',
                '6.3-inch Super Retina XDR display',
                'Pro camera system with 3x optical zoom',
                'Titanium design',
                'Up to 30 hours battery life',
                'USB-C charging',
                'Face ID',
                '5G connectivity'
            ],
            'colors' => ['Space Gray', 'Silver', 'Gold'],
            'quantity' => 30,
            'price_before' => 899.00,
            'price_after' => 449.00,
            'is_active' => true,
        ]);
    }
}
