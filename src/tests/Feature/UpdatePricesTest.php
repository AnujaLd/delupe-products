<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdatePricesTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_prices_command_updates_prices_and_preserves_original(): void
    {
        $p1 = Product::create([
            'merchant_id' => 'M1',
            'name'        => 'Product One',
            'link'        => 'https://example.com/one',
            'price'       => 100.00,
            'currency'    => 'USD',
        ]);

        $p2 = Product::create([
            'merchant_id' => 'M2',
            'name'        => 'Product Two',
            'link'        => 'https://example.com/two',
            'price'       => 50.00,
            'currency'    => 'USD',
        ]);

        $this->artisan('app:update-prices', ['percentage' => 10])
             ->assertExitCode(0);

        $p1->refresh();
        $p2->refresh();

        $this->assertEquals(100.00, (float) $p1->original_price);
        $this->assertEquals(110.00, (float) $p1->price);

        $this->assertEquals(50.00, (float) $p2->original_price);
        $this->assertEquals(55.00, (float) $p2->price);
    }
}
