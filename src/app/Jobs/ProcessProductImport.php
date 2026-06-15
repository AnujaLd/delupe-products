<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private array $item) {}

    public function handle(): void
    {
        try {
            $attributes = [
                'merchant_id' => $this->item['merchant_id'] ?? '',
                'link'        => $this->item['link'] ?? '',
            ];

            $values = [
                'name'           => $this->item['name'],
                'image_link'     => $this->item['image_link'] ?? null,
                'price'          => $this->item['price'],
                'original_price' => $this->item['original_price'] ?? null,
                'currency'       => strtoupper($this->item['currency']),
            ];

            $product = Product::updateOrCreate($attributes, $values);

            $action = $product->wasRecentlyCreated ? 'created' : 'updated';
            Log::info("Product {$action}", [
                'name' => $product->name,
                'merchant_id' => $product->merchant_id,
                'link' => $product->link,
            ]);
        } catch (\Throwable $e) {
            // Log exception with context so issues can be investigated
            Log::error('Failed to process product', [
                'error' => $e->getMessage(),
                'record' => $this->item,
                'trace' => $e->getTraceAsString(),
            ]);
            // rethrow so queue worker can handle retries according to config
            throw $e;
        }
    }
}
