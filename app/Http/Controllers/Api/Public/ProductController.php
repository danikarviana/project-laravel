<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Product;
use App\Http\Controllers\Controller;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::query()
                ->with('category')
                ->when(request()->q, function ($query) {
                    $query->where('title', 'like', '%' . request()->q . '%');
                })
                ->latest()
                ->get();
        } catch (\Throwable $e) {
            $products = Product::query()->latest()->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'List Produk',
            'products' => $products,
        ]);
    }

    public function productsPopular()
    {
        try {
            $products = Product::query()
                ->with('category')
                ->take(4)
                ->get();
        } catch (\Throwable $e) {
            $products = Product::query()->take(4)->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'List Produk Populer',
            'products' => $products,
        ]);
    }

    public function ProductPopular()
    {
        return $this->productsPopular();
    }

    public function show($slug)
    {
        try {
            $product = Product::query()
                ->with('category')
                ->where('slug', $slug)
                ->first();
        } catch (\Throwable $e) {
            $product = Product::query()->where('slug', $slug)->first();
        }

        if ($product) {
            return response()->json([
                'success' => true,
                'message' => 'Detail Produk',
                'product' => $product,
                'reviews_count' => 0,
                'reviews_avg' => 0,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Detail Produk Tidak Ditemukan',
            'product' => null,
        ], 404);
    }
}