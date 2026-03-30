<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public const string DEMO_SEED_IMAGE_PATH = 'demo/product-pic.png';

    public const string DEMO_SEED_RECEIPT_IMAGE_PATH = 'demo/receipt-pic.jpg';

    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(rand(2, 4), true).' '.fake()->randomElement(['Pro', 'Lite', 'Max', 'Eco', 'Plus']),
            'product_image' => self::DEMO_SEED_IMAGE_PATH,
            'sku' => fake()->unique()->bothify('SKU-####-??'),
            'price' => number_format(fake()->randomFloat(4, 9.99, 899.99), 2, '.', ''),
            'active' => true,
        ];
    }

    public function seedSku(int $index): static
    {
        return $this->state(fn (array $_attributes): array => [
            'sku' => sprintf('SEED-P-%05d', $index),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $_attributes): array => [
            'active' => false,
        ]);
    }
}
