<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate statistics
        $totalOrders = Order::count();
        $totalRevenue = Order::sum('total_amount');

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'stats' => [
                'totalOrders' => $totalOrders,
                'totalRevenue' => $totalRevenue,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'product_name' => 'required|string|max:255',
            'product_color' => 'nullable|string|max:50',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'payment_id' => 'nullable|string|max:255',
        ]);

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => auth()->id(),
            'customer_name' => $request->customer_name,
            'customer_email' => $request->customer_email,
            'customer_phone' => $request->customer_phone,
            'shipping_address' => $request->shipping_address,
            'product_name' => $request->product_name,
            'product_color' => $request->product_color,
            'quantity' => $request->quantity,
            'unit_price' => $request->unit_price,
            'total_amount' => $request->total_amount,
            'currency' => 'USD',
            'payment_method' => 'paypal',
            'payment_status' => 'completed',
            'payment_id' => $request->payment_id,
            'order_status' => 'pending',
        ]);

            return response()->json([
                'success' => true,
                'order' => $order,
                'message' => 'Order created successfully'
            ]);
        }

        public function searchByEmail(Request $request)
        {
            $request->validate([
                'email' => 'required|email'
            ]);

            $order = Order::where('customer_email', $request->email)
                ->orderBy('created_at', 'desc')
                ->first();

            if ($order) {
                return response()->json([
                    'success' => true,
                    'order' => $order,
                    'message' => 'Order found'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No orders found for this email address'
                ]);
            }
        }
    }
