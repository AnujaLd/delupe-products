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
        Product::updateOrCreate(
            [
                'merchant_id' => $this->item['merchant_id'] ?? '',
                'link'        => $this->item['link'] ?? '',
            ],
            [
                'name'           => $this->item['name'],
                'image_link'     => $this->item['image_link'] ?? null,
                'price'          => $this->item['price'],
                'original_price' => $this->item['original_price'] ?? null,
                'currency'       => strtoupper($this->item['currency']),
            ]
        );

        Log::info('Product processed', ['name' => $this->item['name']]);
    }
}
