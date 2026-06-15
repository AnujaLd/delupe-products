<?php

namespace App\Console\Commands;

use App\Jobs\ProcessProductImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class ImportProducts extends Command
{
    protected $signature = 'app:import-products {file}';
    protected $description = 'Import products from a JSON file';

    private array $validCurrencies = [
        'USD','EUR','GBP','AED','INR','AUD','CAD','JPY','CHF','SGD'
    ];

    public function handle(): void
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File not found: $file");
            return;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            $this->error("Invalid JSON file.");
            return;
        }

        Log::info('Import started', ['file' => $file, 'total' => count($data)]);
        $this->info("Starting import of " . count($data) . " records...");

        $imported = $updated = $failed = 0;

        foreach ($data as $index => $item) {
            $errors = $this->validate($item);

            if (!empty($errors)) {
                $failed++;
                Log::warning("Validation failed for record $index", [
                    'errors' => $errors,
                    'record' => $item
                ]);
                continue;
            }

            $exists = Product::where('merchant_id', $item['merchant_id'] ?? '')
                             ->where('link', $item['link'] ?? '')
                             ->exists();

            ProcessProductImport::dispatch($item);

            $exists ? $updated++ : $imported++;
        }

        Log::info('Import completed', [
            'imported' => $imported,
            'updated'  => $updated,
            'failed'   => $failed,
        ]);

        $this->table(
            ['Imported', 'Updated', 'Failed'],
            [[$imported, $updated, $failed]]
        );
    }

    private function validate(array $item): array
    {
        $errors = [];

        if (empty($item['name'])) {
            $errors[] = 'Product name cannot be empty';
        }

        if (!isset($item['price']) || (float)$item['price'] <= 0) {
            $errors[] = 'Price must be greater than zero';
        }

        if (empty($item['currency']) || !in_array(strtoupper($item['currency']), $this->validCurrencies)) {
            $errors[] = 'Invalid ISO currency code: ' . ($item['currency'] ?? 'missing');
        }

        return $errors;
    }
}