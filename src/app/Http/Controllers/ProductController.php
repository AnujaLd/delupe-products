<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('currency')) {
            $query->where('currency', strtoupper($request->currency));
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        $limit = (int) $request->get('limit', 50);
        return response()->json($query->paginate($limit));
    }

    public function summary()
    {
        $data = Product::selectRaw('
            COUNT(*) as count,
            COALESCE(SUM(price), 0) as total_price,
            COALESCE(AVG(price), 0) as average_price
        ')->first();

        $currencies = Product::selectRaw('currency, COUNT(*) as count')
            ->groupBy('currency')
            ->pluck('count', 'currency');

        return response()->json([
            'count'         => (int) $data->count,
            'total_price'   => round((float) $data->total_price, 2),
            'average_price' => round((float) $data->average_price, 2),
            'currencies'    => $currencies,
        ]);
    }

    public function duplicates()
    {
        $dupNames = Product::selectRaw('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        $dupLinks = Product::selectRaw('link')
            ->groupBy('link')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('link');

        $results = Product::where(function ($q) use ($dupNames, $dupLinks) {
            $q->whereIn('name', $dupNames)
              ->orWhereIn('link', $dupLinks);
        })->get();

        return response()->json($results);
    }

    public function health()
    {
        try {
            DB::connection()->getPdo();
            $db = 'connected';
        } catch (\Exception $e) {
            $db = 'disconnected';
        }

        return response()->json(['status' => 'ok', 'database' => $db]);
    }
}
