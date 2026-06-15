<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'my-secret-api-key-12345';

    public function test_import_valid_product(): void
    {
        $data = [[
            'merchant_id' => 'M001',
            'name'        => 'Test Shoes',
            'link'        => 'https://example.com/shoes',
            'image_link'  => 'https://example.com/shoes.jpg',
            'price'       => 99.99,
            'currency'    => 'USD',
        ]];

        $file = sys_get_temp_dir() . '/test_products.json';
        file_put_contents($file, json_encode($data));

        $this->artisan('app:import-products', ['file' => $file])
             ->assertExitCode(0);

        unlink($file);
    }

    public function test_import_rejects_invalid_product(): void
    {
        $data = [[
            'merchant_id' => 'M999',
            'name'        => '',
            'link'        => 'https://example.com/bad',
            'price'       => -5,
            'currency'    => 'XYZ',
        ]];

        $file = sys_get_temp_dir() . '/test_invalid.json';
        file_put_contents($file, json_encode($data));

        $this->artisan('app:import-products', ['file' => $file])
             ->assertExitCode(0);

        $this->assertDatabaseMissing('products', ['merchant_id' => 'M999']);
        unlink($file);
    }

    public function test_summary_endpoint(): void
    {
        Product::create([
            'merchant_id' => 'M1',
            'name'        => 'Product A',
            'link'        => 'https://example.com/a',
            'price'       => 100.00,
            'currency'    => 'USD',
        ]);

        $this->withHeader('X-API-Key', $this->apiKey)
             ->getJson('/api/products/summary')
             ->assertStatus(200)
             ->assertJsonStructure(['count', 'total_price', 'average_price', 'currencies']);
    }

    public function test_unauthorized_without_api_key(): void
    {
        $this->getJson('/api/products')
             ->assertStatus(401);
    }

    public function test_health_endpoint(): void
    {
        $this->getJson('/api/health')
             ->assertStatus(200)
             ->assertJson(['status' => 'ok']);
    }
}
