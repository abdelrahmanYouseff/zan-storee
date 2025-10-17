<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        $orders = [
            [
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_name' => 'Ahmed Hassan',
                'customer_email' => 'ahmed.hassan@example.com',
                'customer_phone' => '+1234567890',
                'shipping_address' => '123 Main St, Cairo, Egypt',
                'product_name' => 'Apple iPhone 17 Pro Max 512 GB',
                'product_color' => 'Orange',
                'quantity' => 1,
                'unit_price' => 559.00,
                'total_amount' => 559.00,
                'currency' => 'USD',
                'payment_method' => 'paypal',
                'payment_status' => 'completed',
                'payment_id' => 'PAY-123456789',
                'order_status' => 'shipped',
                'notes' => 'Customer requested express shipping',
            ],
            [
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_name' => 'Sarah Johnson',
                'customer_email' => 'sarah.johnson@example.com',
                'customer_phone' => '+1987654321',
                'shipping_address' => '456 Oak Ave, New York, NY 10001',
                'product_name' => 'Apple iPhone 17 Pro Max 512 GB',
                'product_color' => 'Gray',
                'quantity' => 2,
                'unit_price' => 559.00,
                'total_amount' => 1118.00,
                'currency' => 'USD',
                'payment_method' => 'paypal',
                'payment_status' => 'completed',
                'payment_id' => 'PAY-987654321',
                'order_status' => 'delivered',
                'notes' => 'Gift order for family members',
            ],
            [
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'customer_name' => 'Mohamed Ali',
                'customer_email' => 'mohamed.ali@example.com',
                'customer_phone' => '+201234567890',
                'shipping_address' => '789 Palm St, Dubai, UAE',
                'product_name' => 'Apple iPhone 17 Pro Max 512 GB',
                'product_color' => 'White',
                'quantity' => 1,
                'unit_price' => 559.00,
                'total_amount' => 559.00,
                'currency' => 'USD',
                'payment_method' => 'paypal',
                'payment_status' => 'completed',
                'payment_id' => 'PAY-456789123',
                'order_status' => 'processing',
                'notes' => 'International shipping to UAE',
            ],
        ];

        foreach ($orders as $orderData) {
            Order::create($orderData);
        }
    }
}
