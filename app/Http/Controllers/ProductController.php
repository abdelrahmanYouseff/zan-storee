<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function show($id)
    {
        // Get product by ID
        $product = Product::findOrFail($id);

        // Format product data for the Product.vue page
        $productData = [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'mainImage' => $product->main_image,
            'secondaryImages' => $product->secondary_images ?? [],
            'features' => $product->features ?? [],
            'colors' => $product->colors ?? [],
            'quantity' => $product->quantity,
            'priceBefore' => (float) $product->price_before,
            'priceAfter' => (float) $product->price_after,
            'discount' => $product->discount_percentage ?? 0,
            'paypal_full_payment_url' => $product->paypal_full_payment_url,
            'paypal_cod_payment_url' => $product->paypal_cod_payment_url,
        ];

        return Inertia::render('Product', [
            'product' => $productData,
        ]);
    }
}
