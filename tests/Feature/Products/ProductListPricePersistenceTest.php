<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Models\Product;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProductListPricePersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_price_to_two_fractional_digits_on_create(): void
    {
        $product = Product::query()->create([
            'name' => 'Test',
            'product_image' => ProductFactory::DEMO_SEED_IMAGE_PATH,
            'sku' => 'SKU-T-1',
            'price' => 461.1,
            'active' => true,
        ]);

        $product->refresh();

        $this->assertSame('461.10', (string) $product->price);
    }

    public function test_normalizes_price_on_update(): void
    {
        $product = Product::factory()->createOne([
            'sku' => 'SKU-T-2',
            'price' => '100.00',
        ]);

        $product->update(['price' => '99.9']);

        $product->refresh();

        $this->assertSame('99.90', (string) $product->price);
    }
}
