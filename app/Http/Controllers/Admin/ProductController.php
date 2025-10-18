<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::select([
            'id',
            'name',
            'description',
            'price_before',
            'price_after',
            'quantity',
            'is_active',
            'created_at',
            'updated_at'
        ])->orderBy('created_at', 'desc')->get();

        return Inertia::render('Products', [
            'products' => $products
        ]);
    }

    public function create()
    {
        return Inertia::render('ProductCreate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'main_image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'secondary_images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'features' => 'nullable|array',
            'colors' => 'nullable|array',
            'quantity' => 'required|integer|min:0',
            'price_before' => 'required|numeric|min:0',
            'price_after' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $mainImage = $request->file('main_image');
            $mainImageName = time() . '_main_' . $mainImage->getClientOriginalName();
            $mainImage->move(public_path('images'), $mainImageName);
            $validated['main_image'] = '/images/' . $mainImageName;
        }

        // Handle secondary images upload
        $secondaryImages = [];
        if ($request->hasFile('secondary_images')) {
            foreach ($request->file('secondary_images') as $index => $image) {
                $imageName = time() . '_secondary_' . $index . '_' . $image->getClientOriginalName();
                $image->move(public_path('images'), $imageName);
                $secondaryImages[] = '/images/' . $imageName;
            }
        }
        $validated['secondary_images'] = $secondaryImages;

        Product::create($validated);

        return redirect()->route('dashboard.products')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('ProductEdit', [
            'product' => $product
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'main_image' => 'required|string',
            'secondary_images' => 'nullable|array',
            'features' => 'nullable|array',
            'colors' => 'nullable|array',
            'quantity' => 'required|integer|min:0',
            'price_before' => 'required|numeric|min:0',
            'price_after' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $product->update($validated);

        return redirect()->route('dashboard.products')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->back()->with('success', 'Product deleted successfully.');
    }
}
