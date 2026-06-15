<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class UpdatePrices extends Command
{
    protected $signature = 'app:update-prices {percentage}';
    protected $description = 'Adjust all product prices by a percentage';

    public function handle(): void
    {
        $pct = (float) $this->argument('percentage');

        if ($pct == 0) {
            $this->error("Percentage cannot be zero.");
            return;
        }

        $products = Product::all();
        $count = 0;

        $this->info("Updating prices by {$pct}%...");

        foreach ($products as $product) {
            $oldPrice = $product->price;
            $product->original_price = $product->original_price ?? $product->price;
            $product->price = round($product->price * (1 + $pct / 100), 2);
            $product->save();
            $this->line("  {$product->name}: {$oldPrice} → {$product->price} {$product->currency}");
            $count++;
        }

        $this->info("Done! Updated $count products.");
    }
}
