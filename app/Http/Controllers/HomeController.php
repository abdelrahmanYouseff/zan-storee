<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
    {
        // Get active products, limit to 8 for featured products section (2 rows of 4)
        $products = Product::active()
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => 'Electronics', // You can add category field to products table if needed
                    'price' => $product->price_after,
                    'originalPrice' => $product->price_before,
                    'rating' => 4.8, // You can add rating field to products table if needed
                    'reviews' => 324, // You can add reviews count if needed
                    'image' => $product->main_image,
                    'badge' => $product->discount_percentage > 30 ? 'Hot Deal' : 'New',
                    'discount' => $product->discount_percentage,
                ];
            });

        return Inertia::render('Home', [
            'products' => $products,
        ]);
    }
}
