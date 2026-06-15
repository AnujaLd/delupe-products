<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicatesTest extends TestCase
{
    use RefreshDatabase;

    private string $apiKey = 'my-secret-api-key-12345';

    public function test_duplicates_endpoint_returns_products_sharing_name_or_link(): void
    {
        Product::create([
            'merchant_id' => 'M1',
            'name'        => 'Dup Name',
            'link'        => 'https://example.com/a',
            'price'       => 10.00,
            'currency'    => 'USD',
        ]);

        Product::create([
            'merchant_id' => 'M2',
            'name'        => 'Dup Name',
            'link'        => 'https://example.com/b',
            'price'       => 15.00,
            'currency'    => 'USD',
        ]);

        Product::create([
            'merchant_id' => 'M3',
            'name'        => 'Unique',
            'link'        => 'https://example.com/a',
            'price'       => 20.00,
            'currency'    => 'USD',
        ]);

        $resp = $this->withHeader('X-API-Key', $this->apiKey)
                     ->getJson('/api/products/duplicates')
                     ->assertStatus(200)
                     ->json();

        $this->assertIsArray($resp);
        $this->assertCount(3, $resp); // all three share either name or link
    }
}
